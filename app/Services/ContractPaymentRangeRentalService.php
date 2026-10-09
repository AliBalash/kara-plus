<?php

namespace App\Services;

use App\Models\Contract;
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
            if ($charge->amendment_id) {
                $amendment = $contract->amendments->firstWhere('id', $charge->amendment_id);
                $from = $charge->effective_from ?? $amendment?->extension_start_at;
                $until = $charge->effective_to ?? $amendment?->extension_end_at;
                // Legacy commercial corrections may have only old/new return dates.
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
