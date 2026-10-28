<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use Carbon\Carbon;

/** Read-only allocation of the posted commercial ledger to calendar-date ranges. */
class ContractPaymentRangeRentalService
{
    public function amount(Contract $contract, Carbon $start, Carbon $end): float
    {
        if (! $contract->pickup_date || ! $contract->return_date) {
            return 0.0;
        }

        $pickup = $contract->pickup_date->copy()->startOfDay();
        $return = $contract->return_date->copy()->startOfDay();
        $originalEnd = $contract->amendments
            ->filter(fn ($amendment) => $amendment->type === 'extension'
                && in_array($amendment->status, ['approved', 'superseded', 'voided'], true)
                && $amendment->extension_start_at)
            ->min('extension_start_at') ?? $return;
        $originalEnd = $originalEnd->copy()->startOfDay();
        $amount = 0.0;

        foreach ($contract->charges as $charge) {
            $from = $pickup;
            $until = $originalEnd;
            $amendment = $contract->amendments->firstWhere('id', $charge->amendment_id);
            // Commercial edits adjust the original rental (including tax and
            // services), not the interval between old/new return timestamps.
            // Use the same current bounds as the original rows so later edits
            // reflow both together without changing any posted financial data.
            if ($charge->amendment_id && ! $this->correctsOriginalRental($contract, $charge, $amendment)) {
                $from = $charge->effective_from ?? $amendment?->extension_start_at;
                $until = $charge->effective_to ?? $amendment?->extension_end_at;
                // Legacy extension adjustments may have only old/new return dates.
                if ((! $from || ! $until) && $amendment?->old_return_at && $amendment?->new_return_at
                    && ! $amendment->old_return_at->equalTo($amendment->new_return_at)) {
                    $from = min($amendment->old_return_at, $amendment->new_return_at);
                    $until = max($amendment->old_return_at, $amendment->new_return_at);
                }
                $from ??= $pickup;
                $until ??= $return;
            }
            // Tax is already a separate posted row; tax_amount must not be added again.
            $amount += $this->slice((float) $charge->amount, $from, $until, $start, $end);
        }

        // Legacy contracts without a complete charge ledger still reconcile to total_price.
        $remainder = (float) $contract->total_price - (float) $contract->charges->sum('amount');
        $amount += $this->slice($remainder, $pickup, $return, $start, $end);

        return round($amount, 2);
    }

    private function correctsOriginalRental(Contract $contract, ContractCharges $charge, ?ContractAmendment $amendment): bool
    {
        $policies = [
            ContractCommercialCorrectionService::SCOPE_AUTHORIZED,
            ContractCommercialCorrectionService::SCOPE_OPERATIONAL_LOCATION,
        ];

        if (! in_array(data_get($charge->metadata, 'policy'), $policies, true)
            && ! in_array($amendment?->pricing_policy, $policies, true)) {
            return false;
        }

        // Before extension edits were protected, a commercial correction could
        // undo an already-posted extension. Preserve that historical reversal;
        // an extension created AFTER the correction must not redefine its scope.
        if ($amendment?->old_return_at && $amendment?->new_return_at
            && $amendment->new_return_at->lt($amendment->old_return_at)) {
            $reversesExtension = $contract->amendments->contains(function (ContractAmendment $extension) use ($amendment): bool {
                $from = $extension->extension_start_at ?? $extension->old_return_at;
                $until = $extension->extension_end_at ?? $extension->new_return_at;

                return $extension->type === 'extension' && $extension->id < $amendment->id
                    && in_array($extension->status, ['approved', 'superseded', 'voided'], true)
                    && $from?->equalTo($amendment->new_return_at)
                    && $until?->equalTo($amendment->old_return_at);
            });
            if ($reversesExtension) {
                return false;
            }
        }

        return true;
    }

    private function slice(float $amount, Carbon $from, Carbon $until, Carbon $start, Carbon $end): float
    {
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->startOfDay();
        if ($until->lte($from)) {
            return $start->lte($from) && $end->gt($from) ? $amount : 0.0;
        }

        $days = $from->diffInDays($until);
        // Difference of rounded cumulative allocations conserves cents at adjacent boundaries.
        $allocated = fn (Carbon $date) => round($amount * max(0, min($days, $from->diffInDays($date, false))) / $days, 2);

        return $allocated($end) - $allocated($start);
    }
}
