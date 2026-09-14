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

    public const RATE_SOURCE_CONTRACT = 'contract_rate';

    public const RATE_SOURCE_CURRENT = 'current_tariff';

    public const DEFAULT_RATE_SOURCE = self::RATE_SOURCE_CONTRACT;

    private const TAX_RATE = 0.05;

    public function quoteExtension(
        Contract $contract,
        Carbon|string $newReturnAt,
        string $policy = self::DEFAULT_POLICY,
        string $rateSource = self::DEFAULT_RATE_SOURCE
    ): array
    {
        $start = Carbon::parse($contract->return_date);
        $end = Carbon::parse($newReturnAt);
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['new_return_at' => 'The extension return must be after the current planned return.']);
        }
        if (! in_array($policy, [self::POLICY_DAILY_CEILING, self::POLICY_HOURLY, self::POLICY_PRORATED_DAILY, self::POLICY_GRACE_THEN_DAILY], true)) {
            throw ValidationException::withMessages(['pricing_policy' => 'Unsupported billing policy.']);
        }
        if (! in_array($rateSource, [self::RATE_SOURCE_CONTRACT, self::RATE_SOURCE_CURRENT], true)) {
            throw ValidationException::withMessages(['rate_source' => 'Unsupported extension rate source.']);
        }

        $minutes = $start->diffInMinutes($end);
        $quantity = $this->billableQuantity($minutes, $policy);
        $car = $contract->car()->firstOrFail();
        $currentDailyRate = $this->dailyRate($car, $minutes);
        $contractDailyRate = is_numeric($contract->used_daily_rate) && (float) $contract->used_daily_rate > 0
            ? (float) $contract->used_daily_rate
            : null;
        $effectiveRateSource = $rateSource;
        if ($rateSource === self::RATE_SOURCE_CONTRACT && $contractDailyRate === null) {
            $effectiveRateSource = self::RATE_SOURCE_CURRENT;
        }
        $dailyRate = $effectiveRateSource === self::RATE_SOURCE_CONTRACT
            ? $contractDailyRate
            : $currentDailyRate;
        if ($dailyRate <= 0) {
            throw ValidationException::withMessages(['pricing' => 'No valid rental rate is available for this extension.']);
        }
        $rentalUnitPrice = $policy === self::POLICY_HOURLY ? round($dailyRate / 24, 2) : $dailyRate;
        $contractTariffs = (array) data_get($contract->meta, 'pricing_tariffs', []);
        $taxRate = $effectiveRateSource === self::RATE_SOURCE_CONTRACT
            ? (float) ($contractTariffs['tax_rate'] ?? self::TAX_RATE)
            : self::TAX_RATE;
        $items = [[
            'code' => 'extension_rental', 'title' => 'Rental extension', 'quantity' => $quantity,
            'unit' => $policy === self::POLICY_HOURLY ? 'hour' : 'day', 'unit_price' => $rentalUnitPrice,
            'amount' => round($quantity * $rentalUnitPrice, 2), 'tax_rate' => $taxRate,
        ]];

        // Preserve the selected insurance product and the tariff captured on
        // this contract. Legacy contracts without a snapshot fall back to the
        // current vehicle value because no historical alternative exists.
        $insuranceCode = $this->selectedInsuranceCode($contract);
        if ($insuranceCode !== null) {
            $contractInsuranceTariffs = (array) ($contractTariffs['insurance'] ?? []);
            $hasContractInsuranceTariff = $effectiveRateSource === self::RATE_SOURCE_CONTRACT
                && array_key_exists($insuranceCode, $contractInsuranceTariffs);
            $price = $hasContractInsuranceTariff
                ? (float) $contractInsuranceTariffs[$insuranceCode]
                : ($effectiveRateSource === self::RATE_SOURCE_CONTRACT
                    ? $this->storedInsuranceDailyRate($contract, $insuranceCode)
                    : $this->insuranceDailyRate($car, $insuranceCode, $minutes));
            if ($price === null) {
                $price = $this->insuranceDailyRate($car, $insuranceCode, $minutes);
            }
            if ($price > 0) {
                $unitPrice = $policy === self::POLICY_HOURLY ? round($price / 24, 2) : $price;
                $items[] = ['code' => $insuranceCode, 'title' => strtoupper(str_replace('_', ' ', $insuranceCode)), 'quantity' => $quantity, 'unit' => $policy === self::POLICY_HOURLY ? 'hour' : 'day', 'unit_price' => $unitPrice, 'amount' => round($quantity * $unitPrice, 2), 'tax_rate' => $taxRate];
            }
        }

        foreach ($this->selectedPerDayAddOns($contract, $effectiveRateSource === self::RATE_SOURCE_CONTRACT) as $addOn) {
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
                'tax_rate' => $taxRate,
                'metadata' => ['selected_quantity' => $addOn['selected_quantity']],
            ];
        }

        $subtotal = round(collect($items)->sum('amount'), 2);
        $tax = round($subtotal * $taxRate, 2);
        $quote = [
            'duration_minutes' => $minutes, 'billable_days' => $quantity, 'pricing_policy' => $policy,
            'currency' => 'AED', 'items' => $items, 'subtotal' => $subtotal, 'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'rate_source' => $effectiveRateSource,
            'requested_rate_source' => $rateSource,
            'contract_daily_rate' => $contractDailyRate,
            'current_daily_rate' => $currentDailyRate,
            'effective_daily_rate' => (float) $dailyRate,
            'rate_changed' => $contractDailyRate !== null && abs($contractDailyRate - $currentDailyRate) > 0.005,
        ];
        $quote['snapshot'] = [
            'quoted_at' => now()->toIso8601String(),
            'policy' => $policy,
            'tax_rate' => $taxRate,
            'currency' => $quote['currency'],
            'vehicle_id' => $car->id,
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
            'duration_minutes' => $minutes,
            'billable_quantity' => $quantity,
            'rate_source' => $effectiveRateSource,
            'requested_rate_source' => $rateSource,
            'contract_daily_rate' => $contractDailyRate,
            'current_daily_rate' => $currentDailyRate,
            'effective_daily_rate' => (float) $dailyRate,
            'rate_changed' => $quote['rate_changed'],
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
    private function selectedPerDayAddOns(Contract $contract, bool $useContractTariffs = true): array
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
        $contractServices = (array) data_get($contract->meta, 'pricing_tariffs.services', []);

        return $selected
            ->filter(function (string $code) use ($definitions, $contractServices, $useContractTariffs): bool {
                $definition = $useContractTariffs && isset($contractServices[$code])
                    ? $contractServices[$code]
                    : ($definitions[$code] ?? []);

                return ! empty($definition['per_day'])
                    && is_numeric($definition[$useContractTariffs && isset($contractServices[$code]) ? 'unit_rate' : 'amount'] ?? null);
            })
            ->map(function (string $code) use ($contract, $definitions, $contractServices, $quantities, $useContractTariffs): array {
                $selectedQuantity = max(1, (int) ($quantities[$code] ?? 1));
                $dailyPrice = $useContractTariffs && isset($contractServices[$code])
                    ? (float) ($contractServices[$code]['unit_rate'] ?? 0)
                    : null;
                if ($useContractTariffs && $dailyPrice === null) {
                    $dailyPrice = $this->storedAddOnDailyRate($contract, $code, $selectedQuantity);
                }

                return [
                    'code' => $code,
                    'title' => (string) ($definitions[$code]['label_en'] ?? $code),
                    'daily_price' => (float) ($dailyPrice ?? ($definitions[$code]['amount'] ?? 0)),
                    'selected_quantity' => $selectedQuantity,
                ];
            })
            ->values()
            ->all();
    }

    private function storedInsuranceDailyRate(Contract $contract, string $code): ?float
    {
        $amount = $contract->charges()
            ->where('source_type', 'original')
            ->where('type', 'insurance')
            ->where('title', $code)
            ->sum('amount');
        $days = $this->contractBaseRentalDays($contract);

        return $amount > 0 && $days > 0 ? round((float) $amount / $days, 2) : null;
    }

    private function storedAddOnDailyRate(Contract $contract, string $code, int $selectedQuantity): ?float
    {
        $amount = $contract->charges()
            ->where('source_type', 'original')
            ->where('type', 'addon')
            ->where('title', $code)
            ->sum('amount');
        $days = $this->contractBaseRentalDays($contract);

        return $amount > 0 && $days > 0
            ? round((float) $amount / ($days * max(1, $selectedQuantity)), 2)
            : null;
    }

    private function contractBaseRentalDays(Contract $contract): float
    {
        $snapshotDays = (float) data_get($contract->meta, 'pricing_tariffs.base_days', 0);
        if ($snapshotDays > 0) {
            return $snapshotDays;
        }

        $charge = $contract->charges()
            ->where('source_type', 'original')
            ->where(fn ($query) => $query->where('title', 'base_rental')->orWhere('type', 'base'))
            ->first();
        if ($charge === null) {
            return 0.0;
        }
        if ($charge->unit === 'day' && (float) $charge->quantity > 0) {
            return (float) $charge->quantity;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s+days?/i', (string) $charge->description, $matches)) {
            return (float) $matches[1];
        }

        $dailyRate = (float) ($contract->used_daily_rate ?? 0);
        $amountDays = $dailyRate > 0 ? (float) $charge->amount / $dailyRate : 0.0;
        if ($amountDays > 0 && abs($amountDays - round($amountDays)) < 0.01) {
            return (float) round($amountDays);
        }

        $start = $contract->pickup_date;
        $end = $contract->original_return_date ?? $contract->return_date;
        if ($start !== null && $end !== null && Carbon::parse($end)->greaterThan(Carbon::parse($start))) {
            return (float) max(1, (int) ceil(
                (Carbon::parse($end)->getTimestamp() - Carbon::parse($start)->getTimestamp()) / 86400
            ));
        }

        return $amountDays > 0 ? round($amountDays, 3) : 0.0;
    }
}
