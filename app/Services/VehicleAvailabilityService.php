<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use App\Models\Contract;
use Carbon\Carbon;

class VehicleAvailabilityService
{
    /** @return array<int, array{type:string,id:int|null,message:string}> */
    public function conflicts(Car|int $car, Carbon|string $start, Carbon|string $end, ?int $exceptContractId = null): array
    {
        $car = $car instanceof Car ? $car : Car::findOrFail($car);
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $conflicts = [];

        if (! $start->lt($end)) {
            return [['type' => 'invalid_window', 'id' => null, 'message' => 'Pickup must be before the planned return.']];
        }

        // A car can be operationally marked reserved because of the contract
        // being extended (or overdue/needs-action because of that contract).
        // Those derived states are not conflicts with itself; explicit holds
        // and sold states remain blocking.
        $blockReason = $car->reservationSelectionBlockReason();
        $isOwnOverdueState = $exceptContractId !== null
            && $car->resolvedManualStatus() === Car::MANUAL_STATUS_AVAILABLE
            && $car->unavailability_reason === Car::UNAVAILABILITY_REASON_NEED_ACTION;
        if ($blockReason !== null && ! $isOwnOverdueState) {
            $conflicts[] = ['type' => 'vehicle_status', 'id' => $car->id, 'message' => $blockReason];
        }

        $contracts = Contract::query()
            ->where('car_id', $car->id)
            ->whereIn('current_status', Car::reservingStatuses())
            ->when($exceptContractId, fn ($q) => $q->where('id', '!=', $exceptContractId))
            ->where('pickup_date', '<', $end)
            ->where(fn ($q) => $q->whereNull('return_date')->orWhere('return_date', '>', $start))
            ->get(['id']);
        foreach ($contracts as $contract) {
            $conflicts[] = ['type' => 'contract', 'id' => $contract->id, 'message' => 'Vehicle conflicts with an existing reservation.'];
        }

        if (Car::supportsScheduledUnavailabilityPeriods()) {
            foreach (CarUnavailabilityPeriod::query()->where('car_id', $car->id)->overlappingWindow($start, $end)->get(['id']) as $period) {
                $conflicts[] = ['type' => 'unavailability', 'id' => $period->id, 'message' => 'Vehicle has a scheduled unavailable period.'];
            }
        }

        return $conflicts;
    }

    public function isAvailable(Car|int $car, Carbon|string $start, Carbon|string $end, ?int $exceptContractId = null): bool
    {
        return $this->conflicts($car, $start, $end, $exceptContractId) === [];
    }
}
