<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Integer minor-unit arithmetic, bounded by the cars DECIMAL(10,2) schema. */
final class FleetPriceMath
{
    public const FIELDS = [
        'price_per_day_short' => 'Rental · 1–6 days',
        'price_per_day_mid' => 'Rental · 7–27 days',
        'price_per_day_long' => 'Rental · 28+ days',
        'ldw_price_short' => 'LDW · 1–6 days',
        'ldw_price_mid' => 'LDW · 7–27 days',
        'ldw_price_long' => 'LDW · 28+ days',
        'scdw_price_short' => 'SCDW · 1–6 days',
        'scdw_price_mid' => 'SCDW · 7–27 days',
        'scdw_price_long' => 'SCDW · 28+ days',
    ];

    public const METHODS = [
        'set' => 'Set exact AED', 'increase_percent' => 'Increase %', 'decrease_percent' => 'Decrease %',
        'add' => 'Add AED', 'subtract' => 'Subtract AED',
    ];

    public const MAX_CENTS = 9999999999;

    public static function cents(mixed $value, string $field = 'pricing'): int
    {
        if (! is_scalar($value) || is_bool($value) || ! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $value)) {
            throw ValidationException::withMessages([$field => 'Enter a non-negative amount with at most two decimal places (maximum 99,999,999.99).']);
        }
        $parts = explode('.', (string) $value);

        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function isLargeChange(?string $before, string $after): bool
    {
        $base = $before === null ? 0 : self::cents($before);
        $delta = abs(self::cents($after) - $base);

        return $delta >= 100000 || ($delta > 0 && ($base === 0 || $delta * 2 >= $base));
    }

    public static function adjust(?string $before, string $method, mixed $value, string $field = 'pricing'): string
    {
        $operand = self::cents($value, $field);
        if (! array_key_exists($method, self::METHODS)) {
            throw ValidationException::withMessages([$field => 'Choose a valid adjustment method.']);
        }
        if ($before === null && $method !== 'set') {
            throw ValidationException::withMessages([$field => 'This legacy tariff is blank. Use Set exact AED to define it explicitly.']);
        }
        $base = $before === null ? 0 : self::cents($before, $field);
        if (str_contains($method, 'percent') && $operand > 1000000) {
            throw ValidationException::withMessages([$field => 'Percentage adjustments cannot exceed 10,000%.']);
        }
        if ($method === 'decrease_percent' && $operand > 10000) {
            throw ValidationException::withMessages([$field => 'A percentage decrease cannot exceed 100%.']);
        }
        $result = match ($method) {
            'set' => $operand,
            'add' => $base + $operand,
            'subtract' => $base - $operand,
            default => $base * ($method === 'increase_percent' ? 10000 + $operand : 10000 - $operand),
        };
        if ($result < 0) {
            throw ValidationException::withMessages([$field => 'The resulting price cannot be negative.']);
        }
        if (str_contains($method, 'percent')) {
            $result = intdiv($result + 5000, 10000);
        }
        if ($result > self::MAX_CENTS) {
            throw ValidationException::withMessages([$field => 'The resulting price exceeds the vehicle tariff limit.']);
        }

        return self::money($result);
    }
}
