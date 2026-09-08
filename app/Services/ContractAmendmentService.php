<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Services\Audit\Contracts\AuditWriterContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContractAmendmentService
{
    public function __construct(private readonly RentalPricingService $pricing, private readonly VehicleAvailabilityService $availability, private readonly AuditWriterContract $audit) {}

    public function requestExtension(Contract|int $contract, Carbon|string $newReturnAt, ?int $requestedBy, ?string $idempotencyKey = null, string $pricingPolicy = RentalPricingService::DEFAULT_POLICY, ?string $reason = null, ?string $notes = null): ContractAmendment
    {
        $contractId = $contract instanceof Contract ? $contract->id : $contract;
        $idempotencyKey ??= (string) Str::uuid();

        if (! Str::isUuid($idempotencyKey)) {
            throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key must be a valid UUID.']);
        }

        return DB::transaction(function () use ($contractId, $newReturnAt, $requestedBy, $idempotencyKey, $pricingPolicy, $reason, $notes) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);

            if ($existing = ContractAmendment::where('idempotency_key', $idempotencyKey)->first()) {
                $sameRequest = (int) $existing->contract_id === (int) $contract->id
                    && $existing->type === ContractAmendment::TYPE_EXTENSION
                    && Carbon::parse($existing->new_return_at)->equalTo(Carbon::parse($newReturnAt))
                    && $existing->pricing_policy === $pricingPolicy;

                if (! $sameRequest) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This idempotency key belongs to a different amendment request.']);
                }

                return $existing;
            }

            $this->assertExtendable($contract, $newReturnAt);

            $pending = $contract->amendments()
                ->where('type', ContractAmendment::TYPE_EXTENSION)
                ->where('status', 'pending_approval')
                ->first();
            if ($pending) {
                throw ValidationException::withMessages(['amendment' => 'Resolve the existing pending extension before requesting another one.']);
            }

            $quote = $this->pricing->quoteExtension($contract, $newReturnAt, $pricingPolicy);
            $amendment = ContractAmendment::create([
                'contract_id' => $contract->id, 'sequence_no' => ((int) $contract->amendments()->max('sequence_no')) + 1,
                'type' => ContractAmendment::TYPE_EXTENSION, 'status' => 'pending_approval', 'requested_by' => $requestedBy,
                'requested_at' => now(), 'old_return_at' => $contract->return_date, 'new_return_at' => Carbon::parse($newReturnAt),
                'extension_start_at' => $contract->return_date, 'extension_end_at' => Carbon::parse($newReturnAt),
                'currency' => $quote['currency'], 'pricing_policy' => $pricingPolicy, 'before_snapshot' => $this->contractSnapshot($contract),
                'subtotal' => $quote['subtotal'], 'tax_amount' => $quote['tax'], 'total_amount' => $quote['total'],
                'pricing_snapshot' => $quote['snapshot'], 'reason' => $reason, 'notes' => $notes, 'idempotency_key' => $idempotencyKey,
            ]);
            $this->event('contract_extension_requested', $contract, $amendment, $requestedBy);

            return $amendment;
        }, 3);
    }

    public function approve(ContractAmendment|int $amendment, ?int $approvedBy): ContractAmendment
    {
        $id = $amendment instanceof ContractAmendment ? $amendment->id : $amendment;
        $conflictContext = null;

        try {
            return DB::transaction(function () use ($id, $approvedBy, &$conflictContext) {
                $amendment = ContractAmendment::query()->lockForUpdate()->findOrFail($id);
                if ($amendment->isApproved()) {
                    return $amendment;
                }
                if (! $amendment->isPending() || $amendment->type !== ContractAmendment::TYPE_EXTENSION) {
                    throw ValidationException::withMessages(['amendment' => 'Only a pending extension can be approved.']);
                }
                $contract = Contract::query()->lockForUpdate()->findOrFail($amendment->contract_id);
                $car = Car::query()->lockForUpdate()->findOrFail($contract->car_id);
                $this->assertExtendable($contract, $amendment->new_return_at);
                if (Carbon::parse($contract->return_date)->notEqualTo($amendment->old_return_at)) {
                    throw ValidationException::withMessages(['amendment' => 'The planned return changed; reject this request and create a new extension quote.']);
                }
                $conflicts = $this->availability->conflicts($car, $contract->return_date, $amendment->new_return_at, $contract->id);
                if ($conflicts !== []) {
                    $conflictContext = compact('contract', 'amendment', 'conflicts');
                    throw ValidationException::withMessages(['new_return_at' => $conflicts[0]['message']]);
                }
                $quote = $this->pricing->quoteExtension($contract, $amendment->new_return_at, $amendment->pricing_policy ?: RentalPricingService::DEFAULT_POLICY);
                $amendment->fill(['status' => 'approved', 'approved_by' => $approvedBy, 'approved_at' => now(), 'effective_at' => now(), 'subtotal' => $quote['subtotal'], 'tax_amount' => $quote['tax'], 'total_amount' => $quote['total'], 'pricing_snapshot' => $quote['snapshot']]);
                $this->createCharges($contract, $amendment, $quote);
                $contract->applyApprovedExtension(
                    $amendment->new_return_at,
                    (float) $contract->total_price + (float) $quote['total']
                );
                $amendment->after_snapshot = $this->contractSnapshot($contract);
                $amendment->save();
                $this->event('contract_extension_approved', $contract, $amendment, $approvedBy, ['pricing_snapshot' => $quote['snapshot']]);

                return $amendment->fresh();
            }, 3);
        } catch (ValidationException $exception) {
            if ($conflictContext !== null) {
                $this->event(
                    'contract_extension_conflict_detected',
                    $conflictContext['contract'],
                    $conflictContext['amendment'],
                    $approvedBy,
                    ['conflicts' => $conflictContext['conflicts']]
                );
            }

            throw $exception;
        }
    }

    public function reject(ContractAmendment|int $amendment, ?int $actorId, ?string $notes = null): ContractAmendment
    {
        return $this->close($amendment, 'rejected', $actorId, $notes);
    }

    public function cancel(ContractAmendment|int $amendment, ?int $actorId, ?string $notes = null): ContractAmendment
    {
        return $this->close($amendment, 'cancelled', $actorId, $notes);
    }

    private function close(ContractAmendment|int $amendment, string $status, ?int $actorId, ?string $notes): ContractAmendment
    {
        $id = $amendment instanceof ContractAmendment ? $amendment->id : $amendment;

        return DB::transaction(function () use ($id, $status, $actorId, $notes) {
            $amendment = ContractAmendment::query()->lockForUpdate()->findOrFail($id);
            if (! $amendment->isPending()) {
                throw ValidationException::withMessages(['amendment' => 'Only a pending extension can be '.$status.'.']);
            }
            $amendment->fill(['status' => $status, 'notes' => $notes ?? $amendment->notes, 'after_snapshot' => $this->contractSnapshot($amendment->contract)])->save();
            $this->event('contract_extension_'.$status, $amendment->contract, $amendment, $actorId);

            return $amendment->fresh();
        });
    }

    private function assertExtendable(Contract $contract, Carbon|string $newReturnAt): void
    {
        if (! in_array($contract->current_status, Contract::AMENDABLE_STATUSES, true)) {
            throw ValidationException::withMessages(['contract' => 'Only a delivered rental that has not yet been returned may be extended.']);
        }
        if (! $contract->return_date) {
            throw ValidationException::withMessages(['contract' => 'Contract has no planned return date.']);
        }
        if (Carbon::parse($newReturnAt)->lessThanOrEqualTo($contract->return_date)) {
            throw ValidationException::withMessages(['new_return_at' => 'New return must be after the planned return.']);
        }
    }

    private function createCharges(Contract $contract, ContractAmendment $amendment, array $quote): void
    {
        foreach ($quote['items'] as $item) {
            ContractCharges::create(['contract_id' => $contract->id, 'amendment_id' => $amendment->id, 'title' => $item['title'], 'type' => $item['code'], 'amount' => $item['amount'], 'source_type' => 'amendment', 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'unit_price' => $item['unit_price'], 'tax_rate' => $item['tax_rate'], 'tax_amount' => round($item['amount'] * $item['tax_rate'], 2), 'effective_from' => $amendment->extension_start_at, 'effective_to' => $amendment->extension_end_at, 'metadata' => ['pricing_snapshot' => $quote['snapshot'], ...($item['metadata'] ?? [])]]);
        }

        if ((float) $quote['tax'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'amendment_id' => $amendment->id,
                'title' => 'extension_tax',
                'type' => 'tax',
                'amount' => $quote['tax'],
                'source_type' => 'amendment',
                'quantity' => 1,
                'unit' => 'flat',
                'unit_price' => $quote['tax'],
                'tax_rate' => 0,
                'tax_amount' => 0,
                'effective_from' => $amendment->extension_start_at,
                'effective_to' => $amendment->extension_end_at,
                'metadata' => ['pricing_snapshot' => $quote['snapshot']],
            ]);
        }
    }

    private function contractSnapshot(Contract $contract): array
    {
        return ['contract_id' => $contract->id, 'car_id' => $contract->car_id, 'planned_return_at' => $contract->return_date?->toIso8601String(), 'total_price' => (float) $contract->total_price];
    }

    private function event(string $action, Contract $contract, ContractAmendment $amendment, ?int $actorId, array $meta = []): void
    {
        $this->audit->log($action, ['actor_user_id' => $actorId, 'entity_type' => ContractAmendment::class, 'entity_id' => $amendment->id, 'before' => $amendment->before_snapshot, 'after' => $amendment->after_snapshot, 'meta' => ['contract_id' => $contract->id, 'amendment_id' => $amendment->id, 'vehicle_id' => $contract->car_id, ...$meta]]);
    }
}
