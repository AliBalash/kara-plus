<?php

namespace App\Services\Reservations;

use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Promotes a public website request to a real reservation only after the
 * expert has reviewed it. Keeping this transition here makes the final
 * availability check atomic and prevents two experts from accepting the same
 * vehicle window at the same time.
 */
class ReviewReservationApprovalService
{
    /**
     * Claim the review without creating a reservation.  This is intentionally
     * separate from approve(): a colleague can see that an expert is already
     * working on an invalid website request, while the car remains unreserved
     * until the final availability check succeeds.
     */
    public function claim(int $contractId, int $expertId): Contract
    {
        return DB::transaction(function () use ($contractId, $expertId): Contract {
            $contract = Contract::query()
                ->lockForUpdate()
                ->findOrFail($contractId);

            if (! $contract->isReviewPending()) {
                throw ValidationException::withMessages([
                    'contract' => ['This request has already been reviewed. Please reload the page.'],
                ]);
            }

            if ($contract->user_id !== null && (int) $contract->user_id !== $expertId) {
                throw ValidationException::withMessages([
                    'contract' => ['This request is already being reviewed by another expert.'],
                ]);
            }

            if ($contract->user_id === null) {
                $contract->update(['user_id' => $expertId]);
            }

            return $contract->fresh(['user']);
        });
    }

    public function approve(int $contractId, int $expertId): Contract
    {
        return DB::transaction(function () use ($contractId, $expertId): Contract {
            $contract = Contract::query()
                ->lockForUpdate()
                ->findOrFail($contractId);

            if (! $contract->isReviewPending()) {
                throw ValidationException::withMessages([
                    'contract' => ['This request has already been reviewed. Please reload the page.'],
                ]);
            }

            if ($contract->user_id !== null && (int) $contract->user_id !== $expertId) {
                throw ValidationException::withMessages([
                    'contract' => ['This request is already being reviewed by another expert.'],
                ]);
            }

            /** @var Car $car */
            $car = Car::query()
                ->lockForUpdate()
                ->findOrFail($contract->car_id);
            $car->syncOperationalState();

            $this->ensureReservable($contract, $car);

            if ($contract->user_id === null) {
                $contract->update(['user_id' => $expertId]);
            }

            $contract->changeStatus(
                'assigned',
                $expertId,
                'Website reservation request reviewed and approved.'
            );

            $car->syncOperationalState();

            return $contract->fresh(['car.carModel', 'customer', 'user']);
        });
    }

    /**
     * Read-only, human-friendly diagnostics for the website review queue.
     * approve() repeats the same decisive checks inside a transaction, so a
     * queue that looks ready can never bypass a concurrent availability change.
     *
     * @return array{ready: bool, issues: array<int, array{code: string, title: string, detail: string}>}
     */
    public function diagnostics(Contract $contract): array
    {
        $issues = [];
        $pickup = $contract->pickup_date ? Carbon::parse($contract->pickup_date) : null;
        $return = $contract->return_date ? Carbon::parse($contract->return_date) : null;

        if (! $pickup || ! $return || ! $pickup->lt($return)) {
            $issues[] = [
                'code' => 'invalid_dates',
                'title' => 'Rental dates need correction',
                'detail' => 'Pickup must be set before the return date.',
            ];
        }

        /** @var Car|null $car */
        $car = $contract->relationLoaded('car') ? $contract->car : $contract->car()->with('carModel')->first();

        if (! $car) {
            $issues[] = [
                'code' => 'missing_vehicle',
                'title' => 'Vehicle needs to be selected',
                'detail' => 'Choose an active fleet vehicle before approving this request.',
            ];

            return ['ready' => false, 'issues' => $issues];
        }

        if ($blockReason = $car->reservationSelectionBlockReason()) {
            $issues[] = [
                'code' => 'vehicle_blocked',
                'title' => 'Vehicle is not available for reservations',
                'detail' => $blockReason,
            ];
        }

        if (! $pickup || ! $return || ! $pickup->lt($return)) {
            return ['ready' => $issues === [], 'issues' => $issues];
        }

        $conflictingContract = Contract::query()
            ->where('car_id', $car->id)
            ->whereKeyNot($contract->id)
            ->whereIn('current_status', Car::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<', $return)
            ->where(function ($query) use ($pickup): void {
                $query->whereNull('return_date')
                    ->orWhere('return_date', '>', $pickup);
            })
            ->orderBy('pickup_date')
            ->first(['id', 'pickup_date', 'return_date']);

        if ($conflictingContract) {
            $issues[] = [
                'code' => 'reservation_conflict',
                'title' => 'Vehicle already has a reservation',
                'detail' => sprintf(
                    'It is reserved from %s to %s.',
                    optional($conflictingContract->pickup_date)->format('d M Y, H:i'),
                    optional($conflictingContract->return_date)->format('d M Y, H:i')
                ),
            ];
        }

        if (Car::supportsScheduledUnavailabilityPeriods()) {
            $unavailability = CarUnavailabilityPeriod::query()
                ->where('car_id', $car->id)
                ->overlappingWindow($pickup, $return)
                ->orderBy('start_date')
                ->first();

            if ($unavailability) {
                $reason = $unavailability->reasonLabel();
                $issues[] = [
                    'code' => 'scheduled_unavailability',
                    'title' => 'Vehicle has a scheduled unavailable period',
                    'detail' => sprintf(
                        'Unavailable from %s to %s%s.',
                        optional($unavailability->start_date)->format('d M Y'),
                        optional($unavailability->end_date)->format('d M Y'),
                        $reason ? ' (' . $reason . ')' : ''
                    ),
                ];
            }
        }

        return ['ready' => $issues === [], 'issues' => $issues];
    }

    private function ensureReservable(Contract $contract, Car $car): void
    {
        $blockReason = $car->reservationSelectionBlockReason();
        if ($blockReason !== null) {
            throw ValidationException::withMessages([
                'selectedCarId' => [$blockReason],
            ]);
        }

        $pickup = Carbon::parse($contract->pickup_date);
        $return = Carbon::parse($contract->return_date);

        $conflictingContract = Contract::query()
            ->where('car_id', $car->id)
            ->where('id', '!=', $contract->id)
            ->whereIn('current_status', Car::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<', $return)
            ->where(function ($query) use ($pickup): void {
                $query->whereNull('return_date')
                    ->orWhere('return_date', '>', $pickup);
            })
            ->lockForUpdate()
            ->orderBy('pickup_date')
            ->first(['id', 'pickup_date', 'return_date']);

        if ($conflictingContract) {
            throw ValidationException::withMessages([
                'selectedCarId' => [sprintf(
                    'The selected car is already reserved from %s to %s.',
                    optional($conflictingContract->pickup_date)->format('Y-m-d H:i'),
                    optional($conflictingContract->return_date)->format('Y-m-d H:i')
                )],
            ]);
        }

        if (! Car::supportsScheduledUnavailabilityPeriods()) {
            return;
        }

        $unavailability = CarUnavailabilityPeriod::query()
            ->where('car_id', $car->id)
            ->overlappingWindow($pickup, $return)
            ->lockForUpdate()
            ->orderBy('start_date')
            ->first();

        if ($unavailability) {
            throw ValidationException::withMessages([
                'selectedCarId' => [sprintf(
                    'The selected car is unavailable from %s to %s%s.',
                    optional($unavailability->start_date)->format('Y-m-d'),
                    optional($unavailability->end_date)->format('Y-m-d'),
                    $unavailability->reasonLabel() ? ' due to ' . $unavailability->reasonLabel() : ''
                )],
            ]);
        }
    }
}
