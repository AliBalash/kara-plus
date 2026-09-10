<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/** Stateless pricing boundary. Quotes are converted to snapshots before approval. */
class RentalPricingService
{
    public const POLICY_DAILY_CEILING = 'daily_ceiling';

    public const POLICY_HOURLY = 'hourly';

    public const POLICY_PRORATED_DAILY = 'prorated_daily';

    public const POLICY_GRACE_THEN_DAILY = 'grace_then_daily';

    public const DEFAULT_POLICY = self::POLICY_DAILY_CEILING;

    private const TAX_RATE = 0.05;

    public function quoteExtension(Contract $contract, Carbon|string $newReturnAt, string $policy = self::DEFAULT_POLICY): array
    {
        $start = Carbon::parse($contract->return_date);
        $end = Carbon::parse($newReturnAt);
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['new_return_at' => 'The extension return must be after the current planned return.']);
        }
        if (! in_array($policy, [self::POLICY_DAILY_CEILING, self::POLICY_HOURLY, self::POLICY_PRORATED_DAILY, self::POLICY_GRACE_THEN_DAILY], true)) {
            throw ValidationException::withMessages(['pricing_policy' => 'Unsupported billing policy.']);
        }

        $minutes = $start->diffInMinutes($end);
        $quantity = $this->billableQuantity($minutes, $policy);
        $car = $contract->car()->firstOrFail();
        $dailyRate = $this->dailyRate($car, $minutes);
        if ($dailyRate <= 0) {
            throw ValidationException::withMessages(['pricing' => 'No valid current rental tariff is configured for this vehicle.']);
        }
        $rentalUnitPrice = $policy === self::POLICY_HOURLY ? round($dailyRate / 24, 2) : $dailyRate;
        $items = [[
            'code' => 'extension_rental', 'title' => 'Rental extension', 'quantity' => $quantity,
            'unit' => $policy === self::POLICY_HOURLY ? 'hour' : 'day', 'unit_price' => $rentalUnitPrice,
            'amount' => round($quantity * $rentalUnitPrice, 2), 'tax_rate' => self::TAX_RATE,
        ]];

        // Preserve the selected insurance product, but price only the added period
        // using its tariff at approval time.
        $insuranceCode = $this->selectedInsuranceCode($contract);
        if ($insuranceCode !== null) {
            $price = $this->insuranceDailyRate($car, $insuranceCode, $minutes);
            if ($price > 0) {
                $unitPrice = $policy === self::POLICY_HOURLY ? round($price / 24, 2) : $price;
                $items[] = ['code' => $insuranceCode, 'title' => strtoupper(str_replace('_', ' ', $insuranceCode)), 'quantity' => $quantity, 'unit' => $policy === self::POLICY_HOURLY ? 'hour' : 'day', 'unit_price' => $unitPrice, 'amount' => round($quantity * $unitPrice, 2), 'tax_rate' => self::TAX_RATE];
            }
        }

        foreach ($this->selectedPerDayAddOns($contract) as $addOn) {
            $unitPrice = $policy === self::POLICY_HOURLY
                ? round($addOn['daily_price'] / 24, 2)
                : $addOn['daily_price'];
            $itemQuantity = round($quantity * $addOn['selected_quantity'], 3);
            $items[] = [
                'code' => $addOn['code'],
                'title' => $addOn['title'],
                'quantity' => $itemQuantity,
                'unit' => $policy === self::POLICY_HOURLY ? 'item_hour' : 'item_day',
                'unit_price' => $unitPrice,
                'amount' => round($itemQuantity * $unitPrice, 2),
                'tax_rate' => self::TAX_RATE,
                'metadata' => ['selected_quantity' => $addOn['selected_quantity']],
            ];
        }

        $subtotal = round(collect($items)->sum('amount'), 2);
        $tax = round($subtotal * self::TAX_RATE, 2);
        $quote = [
            'duration_minutes' => $minutes, 'billable_days' => $quantity, 'pricing_policy' => $policy,
            'currency' => 'AED', 'items' => $items, 'subtotal' => $subtotal, 'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
        $quote['snapshot'] = [
            'quoted_at' => now()->toIso8601String(),
            'policy' => $policy,
            'tax_rate' => self::TAX_RATE,
            'currency' => $quote['currency'],
            'vehicle_id' => $car->id,
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
            'duration_minutes' => $minutes,
            'billable_quantity' => $quantity,
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $quote['total'],
        ];

        return $quote;
    }

    private function billableQuantity(int $minutes, string $policy): float
    {
        return match ($policy) {
            self::POLICY_HOURLY => (float) ceil($minutes / 60),
            self::POLICY_PRORATED_DAILY => round($minutes / 1440, 3),
            self::POLICY_GRACE_THEN_DAILY => $minutes <= 120 ? 0.0 : (float) ceil(($minutes - 120) / 1440),
            default => (float) ceil($minutes / 1440),
        };
    }

    private function dailyRate(Car $car, int $minutes): float
    {
        $days = (int) ceil($minutes / 1440);
        if ($days >= 28) {
            return (float) ($car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short);
        }

        if ($days >= 7) {
            return (float) ($car->price_per_day_mid ?? $car->price_per_day_short);
        }

        return (float) $car->price_per_day_short;
    }

    private function selectedInsuranceCode(Contract $contract): ?string
    {
        $meta = is_array($contract->meta) ? $contract->meta : [];
        if (array_key_exists('selected_insurance', $meta)) {
            $selected = $meta['selected_insurance'];

            return in_array($selected, ['ldw_insurance', 'scdw_insurance'], true) ? $selected : null;
        }

        $title = $contract->charges()
            ->where('type', 'insurance')
            ->whereIn('title', ['ldw_insurance', 'scdw_insurance'])
            ->latest('id')
            ->value('title');

        return in_array($title, ['ldw_insurance', 'scdw_insurance'], true) ? $title : null;
    }

    private function insuranceDailyRate(Car $car, string $code, int $minutes): float
    {
        $days = (int) ceil($minutes / 1440);
        $prefix = $code === 'scdw_insurance' ? 'scdw_price_' : 'ldw_price_';

        if ($days >= 28) {
            return (float) ($car->{$prefix.'long'} ?? $car->{$prefix.'mid'} ?? $car->{$prefix.'short'} ?? 0);
        }

        if ($days >= 7) {
            return (float) ($car->{$prefix.'mid'} ?? $car->{$prefix.'short'} ?? 0);
        }

        return (float) ($car->{$prefix.'short'} ?? 0);
    }

    /** @return array<int, array{code:string,title:string,daily_price:float,selected_quantity:int}> */
    private function selectedPerDayAddOns(Contract $contract): array
    {
        $definitions = config('carservices', []);
        $meta = is_array($contract->meta) ? $contract->meta : [];
        $selectedCodes = array_key_exists('selected_services', $meta)
            ? (array) $meta['selected_services']
            : $contract->charges()->where('type', 'addon')->pluck('title')->all();
        $selected = collect($selectedCodes)
            ->map(fn ($code) => (string) $code)
            ->unique();
        $quantities = (array) data_get($contract->meta, 'service_quantities', []);

        return $selected
            ->filter(fn (string $code) => ! empty($definitions[$code]['per_day']) && is_numeric($definitions[$code]['amount'] ?? null))
            ->map(fn (string $code) => [
                'code' => $code,
                'title' => (string) ($definitions[$code]['label_en'] ?? $code),
                'daily_price' => (float) $definitions[$code]['amount'],
                'selected_quantity' => max(1, (int) ($quantities[$code] ?? 1)),
            ])
            ->values()
            ->all();
    }
}
