<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Models\LocationCost;
use App\Models\Payment;
use App\Services\Audit\Contracts\AuditWriterContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Applies exceptional operational-contract corrections as an append-only
 * ledger amendment. Original charges and payments are never rewritten.
 */
class ContractCommercialCorrectionService
{
    public const AUTHORIZED_USER_IDS = [11];

    public const SCOPE_AUTHORIZED = 'authorized_correction';

    public const SCOPE_OPERATIONAL_LOCATION = 'operational_location_correction';

    public function __construct(
        private readonly VehicleAvailabilityService $availability,
        private readonly AuditWriterContract $audit,
    ) {}

    public static function userIsAuthorized(?int $userId): bool
    {
        return $userId !== null && in_array($userId, self::AUTHORIZED_USER_IDS, true);
    }

    /**
     * @param  array<string, mixed>  $contractAttributes
     * @param  array<string, float|int>  $currentBreakdown
     * @param  array<string, float|int>  $correctedBreakdown
     */
    public function apply(
        Contract|int $contract,
        int $actorId,
        array $contractAttributes,
        array $currentBreakdown,
        array $correctedBreakdown,
        string $scope = self::SCOPE_AUTHORIZED,
    ): ContractAmendment {
        if (! in_array($scope, [self::SCOPE_AUTHORIZED, self::SCOPE_OPERATIONAL_LOCATION], true)) {
            throw ValidationException::withMessages([
                'contract' => 'The requested correction scope is invalid.',
            ]);
        }

        if ($scope === self::SCOPE_AUTHORIZED && ! self::userIsAuthorized($actorId)) {
            throw ValidationException::withMessages([
                'contract' => 'You are not authorized to change locked commercial terms.',
            ]);
        }

        $contractId = $contract instanceof Contract ? $contract->id : $contract;

        return DB::transaction(function () use ($contractId, $actorId, $contractAttributes, $currentBreakdown, $correctedBreakdown, $scope): ContractAmendment {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);

            if (! in_array($contract->current_status, Contract::FINANCIALLY_IMMUTABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'contract' => 'Only an operational contract may use the correction ledger.',
                ]);
            }

            if ($contract->amendments()->where('status', 'pending_approval')->exists()) {
                throw ValidationException::withMessages([
                    'contract' => 'Resolve the pending amendment before correcting this contract.',
                ]);
            }

            if ($scope === self::SCOPE_OPERATIONAL_LOCATION) {
                $this->assertLocationOnlyCorrection($contract, $contractAttributes, $currentBreakdown, $correctedBreakdown);
            }

            $lockedCharges = ContractCharges::query()
                ->where('contract_id', $contract->id)
                ->lockForUpdate()
                ->get(['id', 'amount']);
            $ledgerTotal = round((float) $lockedCharges->sum('amount'), 2);
            $oldTotal = round((float) $contract->total_price, 2);
            if (abs($ledgerTotal - $oldTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'contract' => 'The contract ledger is already out of balance. Review it before applying a correction.',
                ]);
            }

            $newCarId = (int) ($contractAttributes['car_id'] ?? $contract->car_id);
            $carIds = collect([(int) $contract->car_id, $newCarId])->unique()->sort()->values();
            $lockedCars = Car::query()->whereKey($carIds)->lockForUpdate()->get();
            $newCar = Car::query()->findOrFail($newCarId);

            $conflicts = $this->availability->conflicts(
                $newCar,
                $contractAttributes['pickup_date'] ?? $contract->pickup_date,
                $contractAttributes['return_date'] ?? $contract->return_date,
                $contract->id,
            );
            if ($conflicts !== []) {
                throw ValidationException::withMessages(['selectedCarId' => $conflicts[0]['message']]);
            }

            $oldCarId = (int) $contract->car_id;
            $newTotal = round((float) ($contractAttributes['total_price'] ?? $oldTotal), 2);
            $categoryDeltas = $this->categoryDeltas($currentBreakdown, $correctedBreakdown);
            if (abs(round(array_sum($categoryDeltas), 2) - round($newTotal - $oldTotal, 2)) > 0.01) {
                throw new LogicException('Commercial correction breakdown does not match the contract total delta.');
            }
            $before = $this->snapshot($contract, $currentBreakdown);
            $amendment = ContractAmendment::create([
                'contract_id' => $contract->id,
                'sequence_no' => ((int) $contract->amendments()->max('sequence_no')) + 1,
                'type' => 'adjustment',
                'status' => 'pending_approval',
                'requested_by' => $actorId,
                'requested_at' => now(),
                'approved_by' => $actorId,
                'approved_at' => now(),
                'effective_at' => now(),
                'old_return_at' => $contract->return_date,
                'new_return_at' => Carbon::parse($contractAttributes['return_date'] ?? $contract->return_date),
                'currency' => 'AED',
                'pricing_policy' => $scope,
                'subtotal' => round($newTotal - $oldTotal, 2),
                'tax_amount' => round((float) ($correctedBreakdown['vat'] ?? 0) - (float) ($currentBreakdown['vat'] ?? 0), 2),
                'total_amount' => round($newTotal - $oldTotal, 2),
                'before_snapshot' => $before,
                'pricing_snapshot' => [
                    'policy' => $scope,
                    'currency' => 'AED',
                    'corrected_breakdown' => $correctedBreakdown,
                ],
                'reason' => $scope === self::SCOPE_OPERATIONAL_LOCATION
                    ? 'Location fee correction from contract edit page'
                    : 'Authorized correction from contract edit page',
                'idempotency_key' => (string) Str::uuid(),
            ]);

            foreach ($categoryDeltas as $category => $delta) {
                if (abs($delta) < 0.005) {
                    continue;
                }

                ContractCharges::create([
                    'contract_id' => $contract->id,
                    'amendment_id' => $amendment->id,
                    'title' => 'correction_'.$category,
                    'type' => $category === 'vat' ? 'tax' : 'adjustment',
                    'amount' => $delta,
                    'source_type' => 'amendment',
                    'quantity' => 1,
                    'unit' => 'flat',
                    'unit_price' => $delta,
                    'metadata' => [
                        'category' => $category,
                        'policy' => $scope,
                    ],
                ]);
            }

            $contract->applyApprovedCommercialCorrection($contractAttributes);

            $updatedPayments = 0;
            if ($newCarId !== $oldCarId) {
                $updatedPayments = Payment::query()->where('contract_id', $contract->id)->update(['car_id' => $newCarId]);
                $lockedCars->each(fn (Car $car) => $car->syncOperationalState());
            }

            $correctedLedgerTotal = round((float) ContractCharges::query()
                ->where('contract_id', $contract->id)
                ->sum('amount'), 2);
            if (abs($correctedLedgerTotal - $newTotal) > 0.01) {
                throw new LogicException('Commercial correction left the contract ledger out of balance.');
            }

            $amendment->fill([
                'status' => 'approved',
                'after_snapshot' => $this->snapshot($contract->fresh(), $correctedBreakdown),
            ])->save();

            $this->audit->log('contract_commercial_correction_approved', [
                'actor_user_id' => $actorId,
                'entity_type' => ContractAmendment::class,
                'entity_id' => $amendment->id,
                'before' => $before,
                'after' => $amendment->after_snapshot,
                'meta' => [
                    'contract_id' => $contract->id,
                    'amendment_id' => $amendment->id,
                    'old_total' => $oldTotal,
                    'new_total' => $newTotal,
                    'payments_synced_to_car' => $updatedPayments,
                    'correction_scope' => $scope,
                ],
            ]);

            return $amendment->fresh('charges');
        }, 3);
    }

    private function assertLocationOnlyCorrection(Contract $contract, array $attributes, array $current, array $corrected): void
    {
        $allowedAttributes = ['pickup_date', 'return_date', 'pickup_location', 'return_location', 'total_price'];
        if (array_diff(array_keys($attributes), $allowedAttributes) !== []) {
            throw ValidationException::withMessages([
                'contract' => 'A location correction cannot change any other commercial term.',
            ]);
        }

        if (($attributes['pickup_location'] ?? $contract->pickup_location) === $contract->pickup_location
            && ($attributes['return_location'] ?? $contract->return_location) === $contract->return_location) {
            throw ValidationException::withMessages([
                'contract' => 'No pickup or return location change was detected.',
            ]);
        }

        foreach (['rental_days', 'daily_rate', 'base_rental', 'services', 'insurance', 'driver_service', 'driving_license', 'other'] as $category) {
            if (abs((float) ($corrected[$category] ?? 0) - (float) ($current[$category] ?? 0)) > 0.005) {
                throw ValidationException::withMessages([
                    'contract' => 'A location correction cannot change any other commercial amount.',
                ]);
            }
        }

        $durationSeconds = Carbon::parse($attributes['return_date'] ?? $contract->return_date)->getTimestamp()
            - Carbon::parse($attributes['pickup_date'] ?? $contract->pickup_date)->getTimestamp();
        $rentalDays = max(1, (int) ceil($durationSeconds / 86400));
        $feeColumn = $rentalDays < 3 ? 'under_3_fee' : 'over_3_fee';

        foreach (['pickup' => 'pickup_location', 'return' => 'return_location'] as $category => $attribute) {
            $newLocation = (string) ($attributes[$attribute] ?? $contract->{$attribute});
            $oldLocation = (string) $contract->{$attribute};
            $expectedFee = (float) ($current[$category.'_transfer'] ?? 0);

            if ($newLocation !== $oldLocation) {
                $locationCost = LocationCost::query()->where('location', $newLocation)->first();
                if ($locationCost === null) {
                    throw ValidationException::withMessages([
                        $attribute => 'The selected location has no configured transfer price.',
                    ]);
                }
                $expectedFee = (float) $locationCost->{$feeColumn};
            }

            if (abs((float) ($corrected[$category.'_transfer'] ?? 0) - $expectedFee) > 0.005) {
                throw ValidationException::withMessages([
                    $attribute => 'The location transfer price changed. Review the latest amount and submit again.',
                ]);
            }
        }
    }

    /** @return array<string, float> */
    private function categoryDeltas(array $current, array $corrected): array
    {
        $categories = [
            'base_rental',
            'pickup_transfer',
            'return_transfer',
            'services',
            'insurance',
            'driver_service',
            'driving_license',
            'other',
            'vat',
        ];

        return collect($categories)->mapWithKeys(fn (string $category) => [
            $category => round((float) ($corrected[$category] ?? 0) - (float) ($current[$category] ?? 0), 2),
        ])->all();
    }

    private function snapshot(Contract $contract, array $breakdown): array
    {
        return [
            'contract_id' => $contract->id,
            'car_id' => $contract->car_id,
            'pickup_at' => $contract->pickup_date?->toIso8601String(),
            'planned_return_at' => $contract->return_date?->toIso8601String(),
            'total_price' => (float) $contract->total_price,
            'used_daily_rate' => (float) $contract->used_daily_rate,
            'breakdown' => $breakdown,
        ];
    }
}
