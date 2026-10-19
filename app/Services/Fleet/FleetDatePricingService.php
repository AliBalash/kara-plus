<?php

namespace App\Services\Fleet;

use App\Models\Car;
use App\Models\FleetPriceOperation;
use App\Models\FleetPriceWindow;
use App\Models\FleetPriceWindowRate;
use App\Models\User;
use App\Support\FleetPriceMath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FleetDatePricingService
{
    public function preview(User $actor, array $ids, array $operations, array $window): FleetPriceOperation
    {
        FleetBulkPricingService::authorize($actor);
        $bulk = app(FleetBulkPricingService::class);
        $ids = $bulk->ids($ids);
        $enabled = $bulk->operations($operations);
        $window = $this->validateWindow($window);
        foreach ($enabled as $field => $operation) {
            if (! str_starts_with($field, 'price_per_day_') || ! in_array($operation['method'], ['set', 'increase_percent', 'decrease_percent'], true)) {
                throw ValidationException::withMessages(['operations' => 'Date-based pricing supports rental tiers with exact rates or percentages only.']);
            }
        }
        $cars = Car::with('carModel')->whereKey($ids)->orderBy('id')->get();
        if ($cars->count() !== count($ids)) {
            throw ValidationException::withMessages(['selection' => 'A selected vehicle no longer exists.']);
        }
        $rows = [];
        foreach ($cars as $car) {
            $before = $after = [];
            foreach ($enabled as $field => $adjustment) {
                $tier = str_replace('price_per_day_', '', $field);
                $before[$field] = SeasonalPricingService::baseRate($car, $tier);
                $after[$field] = FleetPriceMath::adjust($before[$field], $adjustment['method'], $adjustment['value']);
            }
            $rows[] = [
                'id' => $car->id, 'label' => $car->nameWithPlate(), 'before' => $before, 'after' => $after,
                'snapshot' => $car->only(['price_per_day_short', 'price_per_day_mid', 'price_per_day_long']),
                'large' => collect($after)->contains(fn ($value, $field) => FleetPriceMath::isLargeChange($before[$field], $value)),
                'changed' => $before !== $after,
            ];
        }
        $this->assertNoOverlap($ids, array_keys($enabled), $window);

        return FleetPriceOperation::create([
            'id' => (string) Str::uuid(), 'actor_id' => $actor->id, 'mode' => 'date', 'expires_at' => now()->addMinutes(30),
            'payload' => ['ids' => $ids, 'operations' => $enabled, 'rows' => $rows, 'window' => $window],
        ]);
    }

    public function previewState(User $actor, int $id, bool $active): FleetPriceOperation
    {
        FleetBulkPricingService::authorize($actor);
        $window = FleetPriceWindow::with(['rates' => fn ($query) => $query->orderBy('id')])->findOrFail($id);
        $ids = $window->rates->pluck('car_id')->unique()->sort()->values()->all();
        if ($ids === []) {
            throw ValidationException::withMessages(['preview' => 'This event has no remaining vehicles.']);
        }

        if ($active && $window->ends_on->toDateString() < now('Asia/Dubai')->toDateString()) {
            throw ValidationException::withMessages(['window' => 'An expired event cannot be activated.']);
        }
        $rows = [];
        $operations = [];
        foreach (Car::with('carModel')->whereKey($ids)->orderBy('id')->get() as $car) {
            $before = $after = [];
            foreach ($window->rates->where('car_id', $car->id) as $rate) {
                $field = 'price_per_day_'.$rate->tier;
                $base = SeasonalPricingService::baseRate($car, $rate->tier);
                $adjusted = FleetPriceMath::adjust($base, $rate->method, $rate->value);
                $before[$field] = $window->active ? $adjusted : $base;
                $after[$field] = $active ? $adjusted : $base;
                $operations[$field] = ['method' => $rate->method, 'value' => $rate->value];
            }
            $rows[] = ['id' => $car->id, 'label' => $car->nameWithPlate(), 'before' => $before, 'after' => $after,
                'snapshot' => $car->only(['price_per_day_short', 'price_per_day_mid', 'price_per_day_long']),
                'large' => collect($after)->contains(fn ($value, $field) => FleetPriceMath::isLargeChange($before[$field], $value)), 'changed' => $before !== $after];
        }

        return FleetPriceOperation::create([
            'id' => (string) Str::uuid(), 'actor_id' => $actor->id, 'mode' => 'date_state', 'expires_at' => now()->addMinutes(30),
            'payload' => [
                'ids' => $ids, 'rows' => $rows, 'operations' => $operations, 'window_id' => $id,
                'window_fingerprint' => $this->windowFingerprint($window),
                'previous_active' => $window->active, 'window' => ['name' => $window->name, 'starts_on' => $window->starts_on->toDateString(), 'ends_on' => $window->ends_on->toDateString(), 'active' => $active],
            ],
        ]);
    }

    /** Called only inside the bulk service's transaction, after locking all selected cars. */
    public function applyPrepared(User $actor, FleetPriceOperation $operation, Collection $cars): void
    {
        FleetBulkPricingService::authorize($actor);
        abort_unless($operation->actor_id === $actor->id, 403);
        $payload = $operation->payload;
        if ($operation->mode === 'date_state') {
            $window = FleetPriceWindow::with(['rates' => fn ($query) => $query->orderBy('id')->lockForUpdate()])->lockForUpdate()->findOrFail($payload['window_id']);
            if (! hash_equals($payload['window_fingerprint'], $this->windowFingerprint($window))) {
                throw ValidationException::withMessages(['preview' => 'This event changed. Generate a fresh preview.']);
            }
            if ($payload['window']['active'] && $window->ends_on->toDateString() < now('Asia/Dubai')->toDateString()) {
                throw ValidationException::withMessages(['window' => 'This event expired after preview.']);
            }
            $fields = $window->rates->pluck('tier')->unique()->map(fn ($tier) => 'price_per_day_'.$tier)->all();
            $this->assertNoOverlap($payload['ids'], $fields, $payload['window'], $window->id);
            $window->active = $payload['window']['active'];
            $window->save();

            return;
        }
        $config = $this->validateWindow($payload['window']);
        $this->assertNoOverlap($payload['ids'], array_keys($payload['operations']), $config);
        $window = FleetPriceWindow::create([...$config, 'operation_id' => $operation->id, 'created_by' => $actor->id]);
        foreach ($cars as $car) {
            foreach ($payload['operations'] as $field => $adjustment) {
                $tier = str_replace('price_per_day_', '', $field);
                FleetPriceMath::adjust(SeasonalPricingService::baseRate($car, $tier), $adjustment['method'], $adjustment['value']);
                $window->rates()->create(['car_id' => $car->id, 'tier' => $tier, 'method' => $adjustment['method'], 'value' => $adjustment['value']]);
            }
        }
    }

    private function windowFingerprint(FleetPriceWindow $window): string
    {
        return hash('sha256', json_encode([
            'window' => $window->getAttributes(),
            'rates' => $window->rates->map(fn ($rate) => $rate->getAttributes())->all(),
        ], JSON_THROW_ON_ERROR));
    }

    private function validateWindow(array $window): array
    {
        $rules = [
            'name' => 'required|string|max:120',
            'starts_on' => ['bail', 'required', 'string', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Dubai')->toDateString()],
            'ends_on' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'active' => 'required|boolean',
        ];
        if (is_string($window['starts_on'] ?? null)) {
            $rules['ends_on'][] = 'after_or_equal:starts_on';
        }

        return Validator::make($window, $rules, [], ['name' => 'event name', 'starts_on' => 'start date (Dubai)', 'ends_on' => 'end date (Dubai)'])->validate();
    }

    private function assertNoOverlap(array $ids, array $fields, array $window, ?int $except = null): void
    {
        if (! $window['active']) {
            return;
        }
        $tiers = array_map(fn ($field) => str_replace('price_per_day_', '', $field), $fields);
        $conflict = FleetPriceWindowRate::whereIn('car_id', $ids)->whereIn('tier', $tiers)
            ->whereHas('window', fn ($q) => $q->where('active', true)->when($except, fn ($q) => $q->where('id', '!=', $except))
                ->where('starts_on', '<=', $window['ends_on'])->where('ends_on', '>=', $window['starts_on']))->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['window' => 'An active event already covers a selected vehicle, tier and date. No overlapping rules are allowed.']);
        }
    }
}
