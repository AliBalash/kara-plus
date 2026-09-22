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

    public function requestExtension(
        Contract|int $contract,
        Carbon|string $newReturnAt,
        ?int $requestedBy,
        ?string $idempotencyKey = null,
        string $pricingPolicy = RentalPricingService::DEFAULT_POLICY,
        ?string $reason = null,
        ?string $notes = null,
        string $rateSource = RentalPricingService::DEFAULT_RATE_SOURCE
    ): ContractAmendment {
        $contractId = $contract instanceof Contract ? $contract->id : $contract;
        $idempotencyKey ??= (string) Str::uuid();

        if (! Str::isUuid($idempotencyKey)) {
            throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key must be a valid UUID.']);
        }

        return DB::transaction(function () use ($contractId, $newReturnAt, $requestedBy, $idempotencyKey, $pricingPolicy, $reason, $notes, $rateSource) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);

            if ($existing = ContractAmendment::where('idempotency_key', $idempotencyKey)->first()) {
                $sameRequest = (int) $existing->contract_id === (int) $contract->id
                    && $existing->type === ContractAmendment::TYPE_EXTENSION
                    && Carbon::parse($existing->new_return_at)->equalTo(Carbon::parse($newReturnAt))
                    && $existing->pricing_policy === $pricingPolicy
                    && data_get(
                        $existing->pricing_snapshot,
                        'requested_rate_source',
                        data_get($existing->pricing_snapshot, 'rate_source', RentalPricingService::RATE_SOURCE_CURRENT)
                    ) === $rateSource;

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

            $quote = $this->pricing->quoteExtension($contract, $newReturnAt, $pricingPolicy, $rateSource);
            $amendment = ContractAmendment::create([
                'contract_id' => $contract->id, 'sequence_no' => $this->nextSequenceNo($contract->id),
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
                $rateSource = (string) data_get(
                    $amendment->pricing_snapshot,
                    'rate_source',
                    RentalPricingService::RATE_SOURCE_CURRENT
                );
                $quote = $this->pricing->quoteExtension(
                    $contract,
                    $amendment->new_return_at,
                    $amendment->pricing_policy ?: RentalPricingService::DEFAULT_POLICY,
                    $rateSource
                );
                if (! $this->pricingStillMatches($amendment, $quote)) {
                    throw ValidationException::withMessages([
                        'pricing' => 'The selected rental rate or extension price changed after this request. Cancel it, review a fresh quote, and submit a new extension.',
                    ]);
                }
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

    public function updatePendingExtension(
        ContractAmendment|int $amendment,
        Carbon|string $newReturnAt,
        ?int $actorId,
        string $pricingPolicy,
        string $rateSource,
        ?string $reason = null,
        ?string $notes = null,
    ): ContractAmendment {
        $id = $amendment instanceof ContractAmendment ? $amendment->id : $amendment;

        return DB::transaction(function () use ($id, $newReturnAt, $actorId, $pricingPolicy, $rateSource, $reason, $notes): ContractAmendment {
            $amendment = ContractAmendment::query()->lockForUpdate()->findOrFail($id);
            if (! $amendment->isPending() || $amendment->type !== ContractAmendment::TYPE_EXTENSION) {
                throw ValidationException::withMessages(['amendment' => 'Only a pending extension request can be edited in place.']);
            }

            $contract = Contract::query()->lockForUpdate()->findOrFail($amendment->contract_id);
            $this->assertExtendable($contract, $newReturnAt);
            if (Carbon::parse($contract->return_date)->notEqualTo($amendment->old_return_at)) {
                throw ValidationException::withMessages(['amendment' => 'The contract return changed. Delete this stale request and create a fresh extension.']);
            }

            $previous = $amendment->only([
                'new_return_at', 'pricing_policy', 'subtotal', 'tax_amount', 'total_amount',
                'pricing_snapshot', 'reason', 'notes',
            ]);
            $quote = $this->pricing->quoteExtension($contract, $newReturnAt, $pricingPolicy, $rateSource);
            $amendment->fill([
                'new_return_at' => Carbon::parse($newReturnAt),
                'extension_end_at' => Carbon::parse($newReturnAt),
                'currency' => $quote['currency'],
                'pricing_policy' => $pricingPolicy,
                'subtotal' => $quote['subtotal'],
                'tax_amount' => $quote['tax'],
                'total_amount' => $quote['total'],
                'pricing_snapshot' => $quote['snapshot'],
                'reason' => $reason,
                'notes' => $notes,
            ])->save();
            $this->event('contract_extension_updated', $contract, $amendment, $actorId, [
                'previous_request' => $previous,
                'updated_request' => $amendment->only(['new_return_at', 'pricing_policy', 'subtotal', 'tax_amount', 'total_amount', 'reason', 'notes']),
            ]);

            return $amendment->fresh();
        }, 3);
    }

    /**
     * Replace the latest effective extension through compensating ledger rows.
     * The old approved row and charges remain as immutable business history.
     */
    public function reviseApprovedExtension(
        ContractAmendment|int $amendment,
        Carbon|string $newReturnAt,
        ?int $actorId,
        string $pricingPolicy,
        string $rateSource,
        ?string $reason = null,
        ?string $notes = null,
    ): ContractAmendment {
        $id = $amendment instanceof ContractAmendment ? $amendment->id : $amendment;

        return DB::transaction(function () use ($id, $newReturnAt, $actorId, $pricingPolicy, $rateSource, $reason, $notes): ContractAmendment {
            $amendment = ContractAmendment::query()->lockForUpdate()->findOrFail($id);
            $contract = Contract::query()->lockForUpdate()->findOrFail($amendment->contract_id);
            $this->assertLatestEffectiveExtension($contract, $amendment);

            $newReturn = Carbon::parse($newReturnAt);
            if ($newReturn->lessThanOrEqualTo($amendment->old_return_at)) {
                throw ValidationException::withMessages(['new_return_at' => 'The revised return must remain after this extension start.']);
            }
            $conflicts = $this->availability->conflicts(
                $contract->car()->firstOrFail(),
                $amendment->old_return_at,
                $newReturn,
                $contract->id,
            );
            if ($conflicts !== []) {
                throw ValidationException::withMessages(['new_return_at' => $conflicts[0]['message']]);
            }

            $quote = $this->pricing->quoteExtension(
                $contract,
                $newReturn,
                $pricingPolicy,
                $rateSource,
                $amendment->old_return_at,
            );
            $before = $this->contractSnapshot($contract);
            $reversal = $this->createReversalAmendment($contract, $amendment, $actorId, 'extension_revision_reversal');
            $replacement = ContractAmendment::create([
                'contract_id' => $contract->id,
                'sequence_no' => $this->nextSequenceNo($contract->id),
                'type' => ContractAmendment::TYPE_EXTENSION,
                'status' => 'pending_approval',
                'requested_by' => $actorId,
                'requested_at' => now(),
                'approved_by' => $actorId,
                'approved_at' => now(),
                'effective_at' => now(),
                'old_return_at' => $amendment->old_return_at,
                'new_return_at' => $newReturn,
                'extension_start_at' => $amendment->old_return_at,
                'extension_end_at' => $newReturn,
                'currency' => $quote['currency'],
                'pricing_policy' => $pricingPolicy,
                'before_snapshot' => $before,
                'subtotal' => $quote['subtotal'],
                'tax_amount' => $quote['tax'],
                'total_amount' => $quote['total'],
                'pricing_snapshot' => [
                    ...$quote['snapshot'],
                    'replaces_amendment_id' => $amendment->id,
                    'reversal_amendment_id' => $reversal->id,
                ],
                'reason' => $reason,
                'notes' => $notes,
                'idempotency_key' => (string) Str::uuid(),
            ]);
            $this->createCharges($contract, $replacement, $quote);

            $newContractTotal = round((float) $contract->total_price - (float) $amendment->total_amount + (float) $quote['total'], 2);
            $contract->applyApprovedExtension($newReturn, $newContractTotal);
            $after = $this->contractSnapshot($contract);
            $replacement->fill(['status' => 'approved', 'after_snapshot' => $after])->save();
            $amendment->applyManagedLifecycleChange(['status' => 'superseded', 'after_snapshot' => $after]);
            $reversal->fill(['status' => 'approved', 'after_snapshot' => $after])->save();

            $this->event('contract_extension_superseded', $contract, $amendment, $actorId, ['replacement_amendment_id' => $replacement->id]);
            $this->event('contract_extension_revision_approved', $contract, $replacement, $actorId, [
                'replaced_amendment_id' => $amendment->id,
                'reversal_amendment_id' => $reversal->id,
                'old_extension_total' => (float) $amendment->total_amount,
                'new_extension_total' => (float) $quote['total'],
            ]);

            return $replacement->fresh('charges');
        }, 3);
    }

    /** Delete inactive requests softly, or void the latest approved extension with a full reversal. */
    public function deleteExtension(ContractAmendment|int $amendment, ?int $actorId, ?string $notes = null): void
    {
        $id = $amendment instanceof ContractAmendment ? $amendment->id : $amendment;

        DB::transaction(function () use ($id, $actorId, $notes): void {
            $amendment = ContractAmendment::query()->lockForUpdate()->findOrFail($id);
            if ($amendment->type !== ContractAmendment::TYPE_EXTENSION) {
                throw ValidationException::withMessages(['amendment' => 'Only extension records can be removed here.']);
            }
            $contract = Contract::query()->lockForUpdate()->findOrFail($amendment->contract_id);

            if ($amendment->isApproved()) {
                $this->assertLatestEffectiveExtension($contract, $amendment);
                $before = $this->contractSnapshot($contract);
                $reversal = $this->createReversalAmendment($contract, $amendment, $actorId, 'extension_void_reversal');
                $newTotal = round((float) $contract->total_price - (float) $amendment->total_amount, 2);
                $contract->applyApprovedExtension($amendment->old_return_at, $newTotal);
                $after = $this->contractSnapshot($contract);
                $amendment->applyManagedLifecycleChange([
                    'status' => 'voided',
                    'after_snapshot' => $after,
                    'notes' => $notes ?? $amendment->notes,
                ]);
                $reversal->fill(['status' => 'approved', 'after_snapshot' => $after])->save();
                $this->event('contract_extension_voided', $contract, $amendment, $actorId, [
                    'reversal_amendment_id' => $reversal->id,
                    'reversed_total' => (float) $amendment->total_amount,
                    'reason' => $notes,
                ]);

                return;
            }

            if (in_array($amendment->status, ['superseded', 'voided'], true) || $amendment->charges()->exists()) {
                throw ValidationException::withMessages(['amendment' => 'Historical extensions with financial ledger rows cannot be deleted.']);
            }

            $this->event('contract_extension_deleted', $contract, $amendment, $actorId, ['reason' => $notes]);
            $amendment->softDeleteManaged();
        }, 3);
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
        if ($contract->actual_return_at !== null) {
            throw ValidationException::withMessages(['contract' => 'This vehicle has already been returned. The extension was not saved.']);
        }
        if (! $contract->return_date) {
            throw ValidationException::withMessages(['contract' => 'Contract has no planned return date.']);
        }
        if (Carbon::parse($newReturnAt)->lessThanOrEqualTo($contract->return_date)) {
            throw ValidationException::withMessages(['new_return_at' => 'New return must be after the planned return.']);
        }
    }

    private function assertLatestEffectiveExtension(Contract $contract, ContractAmendment $amendment): void
    {
        if (! $amendment->isApproved() || $amendment->type !== ContractAmendment::TYPE_EXTENSION) {
            throw ValidationException::withMessages(['amendment' => 'This record is not an approved extension. Select the current extension that ends on the contract return date.']);
        }
        if (! in_array($contract->current_status, Contract::AMENDABLE_STATUSES, true) || $contract->actual_return_at !== null) {
            $reason = $contract->actual_return_at !== null
                ? 'The vehicle was returned on '.$contract->actual_return_at->format('d M Y H:i').', so its extension can no longer be changed.'
                : 'This contract is '.$contract->current_status.' and is no longer open for extension changes.';
            throw ValidationException::withMessages(['contract' => $reason]);
        }
        $latestId = $contract->amendments()
            ->where('type', ContractAmendment::TYPE_EXTENSION)
            ->where('status', 'approved')
            ->reorder()
            ->latest('sequence_no')
            ->value('id');
        if ((int) $latestId !== (int) $amendment->id) {
            $latest = $contract->amendments()->find($latestId);
            throw ValidationException::withMessages([
                'amendment' => 'This is an older extension. Edit the latest extension instead'
                    .($latest ? ' (#'.$latest->sequence_no.', ending '.$latest->new_return_at?->format('d M Y H:i').').' : '.'),
            ]);
        }
        if (Carbon::parse($contract->return_date)->notEqualTo($amendment->new_return_at)) {
            throw ValidationException::withMessages([
                'amendment' => 'The contract return date is '.$contract->return_date->format('d M Y H:i')
                    .' but this extension ends '.$amendment->new_return_at->format('d M Y H:i')
                    .'. Refresh the page and select the extension ending on the current contract return date.',
            ]);
        }
        if ($contract->amendments()->where('status', 'pending_approval')->exists()) {
            throw ValidationException::withMessages(['amendment' => 'There is an extension waiting for approval. Approve, reject, or cancel that request first, then edit the latest approved extension.']);
        }
    }

    private function createReversalAmendment(
        Contract $contract,
        ContractAmendment $source,
        ?int $actorId,
        string $policy,
    ): ContractAmendment {
        $reversal = ContractAmendment::create([
            'contract_id' => $contract->id,
            'sequence_no' => $this->nextSequenceNo($contract->id),
            'type' => 'adjustment',
            'status' => 'pending_approval',
            'requested_by' => $actorId,
            'requested_at' => now(),
            'approved_by' => $actorId,
            'approved_at' => now(),
            'effective_at' => now(),
            'old_return_at' => $contract->return_date,
            'new_return_at' => $source->old_return_at,
            'currency' => $source->currency,
            'pricing_policy' => $policy,
            'subtotal' => -1 * (float) $source->subtotal,
            'tax_amount' => -1 * (float) $source->tax_amount,
            'total_amount' => -1 * (float) $source->total_amount,
            'before_snapshot' => $this->contractSnapshot($contract),
            'pricing_snapshot' => [
                'policy' => $policy,
                'reverses_amendment_id' => $source->id,
                'original_pricing_snapshot' => $source->pricing_snapshot,
            ],
            'reason' => 'Automatic financial reversal for extension #'.$source->sequence_no,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        foreach ($source->charges()->lockForUpdate()->get() as $charge) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'amendment_id' => $reversal->id,
                'title' => 'reversal_'.$charge->title,
                'type' => $charge->type,
                'description' => 'Reversal of charge #'.$charge->id.' from extension #'.$source->sequence_no,
                'amount' => -1 * (float) $charge->amount,
                'source_type' => 'amendment',
                'quantity' => $charge->quantity === null ? null : -1 * (float) $charge->quantity,
                'unit' => $charge->unit,
                'unit_price' => $charge->unit_price,
                'tax_rate' => $charge->tax_rate,
                'tax_amount' => $charge->tax_amount === null ? null : -1 * (float) $charge->tax_amount,
                'effective_from' => $charge->effective_from,
                'effective_to' => $charge->effective_to,
                'metadata' => [
                    ...(array) $charge->metadata,
                    'reversal' => true,
                    'reverses_charge_id' => $charge->id,
                    'reverses_amendment_id' => $source->id,
                ],
            ]);
        }

        return $reversal;
    }

    private function nextSequenceNo(int $contractId): int
    {
        return ((int) ContractAmendment::withTrashed()->where('contract_id', $contractId)->max('sequence_no')) + 1;
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

    private function pricingStillMatches(ContractAmendment $amendment, array $quote): bool
    {
        foreach (['subtotal' => 'subtotal', 'tax_amount' => 'tax', 'total_amount' => 'total'] as $stored => $quoted) {
            if (abs((float) $amendment->{$stored} - (float) $quote[$quoted]) > 0.005) {
                return false;
            }
        }

        $normalizeItems = fn (array $items): array => collect($items)
            ->map(fn (array $item): array => [
                'code' => (string) ($item['code'] ?? ''),
                'quantity' => round((float) ($item['quantity'] ?? 0), 3),
                'unit' => (string) ($item['unit'] ?? ''),
                'unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                'amount' => round((float) ($item['amount'] ?? 0), 2),
            ])
            ->values()
            ->all();

        return $normalizeItems((array) data_get($amendment->pricing_snapshot, 'items', []))
            === $normalizeItems((array) ($quote['items'] ?? []));
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
