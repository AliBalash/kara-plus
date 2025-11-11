<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest\Concerns;

use Illuminate\Support\Str;

trait HandlesServicePricing
{
    protected function normalizeServiceId(string $serviceId): string
    {
        $prepared = str_replace([' ', '-'], '_', trim($serviceId));

        return Str::snake($prepared);
    }

    protected function resolveServiceId(string $serviceId): ?string
    {
        if (isset($this->services[$serviceId])) {
            return $serviceId;
        }

        $normalized = $this->normalizeServiceId($serviceId);

        return isset($this->services[$normalized]) ? $normalized : null;
    }

    protected function resolveServiceDefinition(string $serviceId): ?array
    {
        $resolvedId = $this->resolveServiceId($serviceId);

        return $resolvedId !== null ? $this->services[$resolvedId] : null;
    }

    protected function calculateServiceAmount(array $service, int $days): float
    {
        $amount = (float) ($service['amount'] ?? 0);

        $value = !empty($service['per_day']) ? $amount * max($days, 1) : $amount;

        return round($value, 2);
    }

    protected function buildServiceDescription(array $service, int $days): string
    {
        if (!empty($service['per_day'])) {
            $dailyAmount = number_format((float) ($service['amount'] ?? 0), 2);

            return sprintf('%d روز × %s درهم', max($days, 1), $dailyAmount);
        }

        return 'یک‌بار هزینه';
    }

    protected function canonicalizeSelectedServices(): void
    {
        $selected = is_array($this->selected_services) ? $this->selected_services : [];

        $canonical = [];

        foreach ($selected as $serviceId) {
            $resolved = $this->resolveServiceId((string) $serviceId);

            if ($resolved !== null && !in_array($resolved, $canonical, true)) {
                $canonical[] = $resolved;
            }
        }

        $this->selected_services = $canonical;
    }
}
