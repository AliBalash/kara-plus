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

    protected function calculateServiceAmount(array $service, int $days, int $quantity = 1): float
    {
        $amount = (float) ($service['amount'] ?? 0);
        $quantity = max(0, (int) $quantity);

        if ($quantity === 0) {
            return 0.0;
        }

        $value = !empty($service['per_day']) ? $amount * max($days, 1) : $amount;

        return round($value * $quantity, 2);
    }

    protected function buildServiceDescription(array $service, int $days, int $quantity = 1): string
    {
        $quantity = max(1, (int) $quantity);

        if (!empty($service['per_day'])) {
            $dailyAmount = number_format((float) ($service['amount'] ?? 0), 2);
            $quantityPrefix = $quantity > 1 ? ($quantity . ' × ') : '';
            $dayCount = max($days, 1);
            $dayLabel = $dayCount === 1 ? 'day' : 'days';

            return sprintf('%s%d %s × %s AED', $quantityPrefix, $dayCount, $dayLabel, $dailyAmount);
        }

        $oneTime = 'One-time fee';

        return $quantity > 1 ? sprintf('%d × %s', $quantity, $oneTime) : $oneTime;
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

    protected function syncServiceSelectionWithQuantities(): void
    {
        if (!property_exists($this, 'service_quantities')) {
            return;
        }

        $quantities = is_array($this->service_quantities) ? $this->service_quantities : [];
        $selected = is_array($this->selected_services) ? $this->selected_services : [];

        $childSeatQuantity = max(0, (int) ($quantities['child_seat'] ?? 0));
        $quantities['child_seat'] = $childSeatQuantity;

        if ($childSeatQuantity > 0) {
            $selected[] = 'child_seat';
        } else {
            $selected = array_filter($selected, fn ($id) => $id !== 'child_seat');
        }

        $this->service_quantities = $quantities;
        $this->selected_services = array_values(array_unique(array_map('strval', $selected)));
    }

    protected function getServiceQuantity(string $serviceId): int
    {
        if (!property_exists($this, 'service_quantities') || !is_array($this->service_quantities)) {
            return 1;
        }

        $resolvedId = $this->resolveServiceId($serviceId);

        if ($resolvedId === null) {
            return 1;
        }

        $quantity = (int) ($this->service_quantities[$resolvedId] ?? ($resolvedId === 'child_seat' ? 0 : 1));

        return max(0, $quantity);
    }
}
