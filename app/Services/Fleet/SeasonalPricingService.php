<?php

namespace App\Services\Fleet;

use App\Models\Car;
use App\Models\FleetPriceWindowRate;
use App\Support\FleetPriceMath;
use App\Support\RentalDuration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Prices each billable anniversary day in Dubai; total duration chooses the tariff tier. */
class SeasonalPricingService
{
    public static function tier(int $days): string
    {
        return $days >= 28 ? 'long' : ($days >= 7 ? 'mid' : 'short');
    }

    public static function baseRate(Car $car, string $tier): string
    {
        return match ($tier) {
            'long' => $car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short,
            'mid' => $car->price_per_day_mid ?? $car->price_per_day_short,
            default => $car->price_per_day_short,
        };
    }

    public function rules(array $ids, Carbon $pickup, Carbon $return): Collection
    {
        return FleetPriceWindowRate::with('window')->whereIn('car_id', $ids)
            ->whereHas('window', fn ($q) => $q->where('active', true)
                ->where('starts_on', '<=', $return->copy()->timezone('Asia/Dubai')->toDateString())
                ->where('ends_on', '>=', $pickup->copy()->timezone('Asia/Dubai')->toDateString()))->get();
    }

    public function rental(Car $car, Carbon $pickup, Carbon $return, ?Collection $rules = null, bool $includeDailyRates = true): array
    {
        $days = RentalDuration::billableDays($pickup, $return);
        if ($days > 3660) {
            throw ValidationException::withMessages(['return_date' => 'Reservation duration cannot exceed ten years.']);
        }
        $tier = self::tier($days);
        $base = self::baseRate($car, $tier);
        $rules ??= $this->rules([$car->id], $pickup, $return);
        $rules = $rules->where('car_id', $car->id)->where('tier', $tier);
        $total = 0;
        $daily = [];
        $seasonal = false;
        for ($day = 0; $day < $days; $day++) {
            $date = $pickup->copy()->timezone('Asia/Dubai')->addDays($day)->toDateString();
            $matches = $rules->filter(fn ($rate) => $rate->window->starts_on->toDateString() <= $date && $rate->window->ends_on->toDateString() >= $date);
            if ($matches->count() > 1) {
                throw ValidationException::withMessages(['pricing' => 'Conflicting vehicle tariffs require staff review.']);
            }
            $rule = $matches->first();
            $amount = $rule ? FleetPriceMath::adjust($base, $rule->method, $rule->value) : $base;
            $seasonal = $seasonal || $rule !== null;
            $total += FleetPriceMath::cents($amount);
            if ($includeDailyRates) {
                $daily[] = ['date' => $date, 'rate' => $amount, 'window_id' => $rule?->fleet_price_window_id, 'event' => $rule?->window->name];
            }
        }
        if ($total > FleetPriceMath::MAX_CENTS) {
            throw ValidationException::withMessages(['pricing' => 'The rental total exceeds the supported booking limit.']);
        }

        return [
            'timezone' => 'Asia/Dubai', 'tier' => $tier, 'days' => $days, 'has_seasonal_prices' => $seasonal,
            'base_daily_rate' => $base, 'base_total' => FleetPriceMath::money($total),
            'effective_daily_rate' => FleetPriceMath::money(intdiv($total + intdiv($days, 2), $days)),
            'daily_rates' => $daily,
            'extension_rate_basis' => 'captured_regular_tariff',
        ];
    }
}
