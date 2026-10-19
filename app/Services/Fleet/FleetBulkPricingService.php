<?php

namespace App\Services\Fleet;

use App\Models\Car;
use App\Models\FleetPriceOperation;
use App\Models\User;
use App\Services\Audit\Contracts\AuditWriterContract;
use App\Support\FleetPriceMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FleetBulkPricingService
{
    public static function authorized(?User $actor): bool
    {
        $actor = $actor?->fresh();

        return $actor && ($actor->hasRole('super-admin') || $actor->can('manage fleet prices'));
    }

    public static function authorize(?User $actor): void
    {
        abort_unless(self::authorized($actor), 403);
    }

    public function preview(User $actor, array $ids, array $operations): FleetPriceOperation
    {
        self::authorize($actor);
        $ids = $this->ids($ids);
        $enabled = $this->operations($operations);
        $cars = Car::with('carModel')->whereKey($ids)->orderBy('id')->get();
        if ($cars->count() !== count($ids)) {
            throw ValidationException::withMessages(['selection' => 'A selected vehicle was deleted. Refresh your selection.']);
        }
        $rows = [];
        foreach ($cars as $car) {
            $before = $after = [];
            $large = false;
            foreach ($enabled as $field => $operation) {
                $before[$field] = $car->{$field};
                $after[$field] = FleetPriceMath::adjust($car->{$field}, $operation['method'], $operation['value'], 'operations.'.$field.'.value');
                $large = $large || FleetPriceMath::isLargeChange($before[$field], $after[$field]);
            }
            $rows[] = ['id' => $car->id, 'label' => $car->nameWithPlate(), 'before' => $before, 'after' => $after, 'large' => $large, 'changed' => $before !== $after];
        }

        return FleetPriceOperation::create([
            'id' => (string) Str::uuid(), 'actor_id' => $actor->id, 'mode' => 'base',
            'payload' => ['ids' => $ids, 'operations' => $enabled, 'rows' => $rows], 'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function apply(User $actor, string $operationId): FleetPriceOperation
    {
        self::authorize($actor);

        return DB::transaction(function () use ($actor, $operationId) {
            $operation = FleetPriceOperation::whereKey($operationId)->lockForUpdate()->firstOrFail();
            abort_unless($operation->actor_id === $actor->id, 403);
            if ($operation->applied_at) {
                return $operation;
            }
            if ($operation->expires_at->isPast()) {
                throw ValidationException::withMessages(['preview' => 'This preview expired. Generate a fresh preview.']);
            }
            $payload = $operation->payload;
            $cars = Car::whereKey($payload['ids'])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($cars->count() !== count($payload['ids'])) {
                throw ValidationException::withMessages(['preview' => 'A selected vehicle was deleted. Generate a fresh preview.']);
            }
            foreach ($payload['rows'] as $row) {
                $car = $cars[$row['id']];
                foreach (($row['snapshot'] ?? $row['before']) as $field => $before) {
                    if ($car->{$field} !== $before) {
                        throw ValidationException::withMessages(['preview' => "Vehicle #{$car->id} changed after preview. No prices were applied; preview again."]);
                    }
                }
                if ($operation->mode === 'base') {
                    foreach ($payload['operations'] as $field => $adjustment) {
                        $car->{$field} = FleetPriceMath::adjust($car->{$field}, $adjustment['method'], $adjustment['value']);
                    }
                    if ($car->isDirty()) {
                        $car->save();
                    }
                }
            }
            if ($operation->mode !== 'base') {
                app(FleetDatePricingService::class)->applyPrepared($actor, $operation, $cars);
            }
            self::authorize($actor);
            $operation->applied_at = now();
            $operation->save();
            app(AuditWriterContract::class)->log('fleet_'.$operation->mode.'_prices_applied', [
                'actor_user_id' => $actor->id, 'entity_type' => FleetPriceOperation::class, 'entity_id' => $operation->id,
                'meta' => ['operation_id' => $operation->id, 'selected_ids' => $payload['ids'], 'rows' => $payload['rows'], 'window' => $payload['window'] ?? null, 'window_id' => $payload['window_id'] ?? null],
                'changed_fields' => $operation->mode === 'date_state' ? ['active'] : array_keys($payload['operations']),
            ]);

            return $operation;
        });
    }

    public function ids(array $ids): array
    {
        Validator::make(['selection' => $ids], ['selection' => 'required|array|min:1', 'selection.*' => 'required|integer|min:1'])->validate();
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    public function operations(array $operations): array
    {
        Validator::make(['operations' => $operations], ['operations' => 'array', 'operations.*' => 'array', 'operations.*.enabled' => 'required|boolean'])->validate();
        $enabled = [];
        foreach ($operations as $field => $operation) {
            if (! is_array($operation) || ! ($operation['enabled'] ?? false)) {
                continue;
            }
            if (! array_key_exists($field, FleetPriceMath::FIELDS)) {
                throw ValidationException::withMessages(['operations' => 'Unsupported price field.']);
            }
            if (! is_string($operation['method'] ?? null) || ! array_key_exists($operation['method'], FleetPriceMath::METHODS)) {
                throw ValidationException::withMessages(['operations.'.$field.'.method' => 'Choose an adjustment method.']);
            }
            FleetPriceMath::cents($operation['value'] ?? '', 'operations.'.$field.'.value');
            $enabled[$field] = ['method' => $operation['method'], 'value' => (string) $operation['value']];
        }
        if ($enabled === []) {
            throw ValidationException::withMessages(['operations' => 'Enable at least one price field.']);
        }

        return $enabled;
    }
}
