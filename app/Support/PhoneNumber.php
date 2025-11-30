<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a phone number into a unified international format.
     *
     * @param  string|null  $phone
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        // Remove international dialing prefix (00...)
        $digits = preg_replace('/^00+/', '', $digits) ?? $digits;

        if ($digits === '') {
            return null;
        }

        if ($normalized = self::normalizeIran($digits)) {
            return $normalized;
        }

        if ($normalized = self::normalizeUnitedArabEmirates($digits)) {
            return $normalized;
        }

        return '+' . $digits;
    }

    private static function normalizeIran(string $digits): ?string
    {
        $candidate = $digits;

        if (str_starts_with($candidate, '98')) {
            $candidate = substr($candidate, 2);
        }

        $candidate = ltrim($candidate, '0');

        if (strlen($candidate) === 10 && str_starts_with($candidate, '9')) {
            return '+98' . $candidate;
        }

        return null;
    }

    private static function normalizeUnitedArabEmirates(string $digits): ?string
    {
        foreach (self::expandWithTrimmedZeros($digits) as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (str_starts_with($candidate, '971')) {
                $subscriber = substr($candidate, 3);
                $subscriber = ltrim($subscriber, '0');

                if (self::isValidUaeSubscriber($subscriber)) {
                    return '+971' . $subscriber;
                }

                continue;
            }

            $subscriber = ltrim($candidate, '0');

            if (self::isValidUaeSubscriber($subscriber)) {
                return '+971' . $subscriber;
            }
        }

        return null;
    }

    private static function expandWithTrimmedZeros(string $digits): array
    {
        $candidates = [$digits];
        $trimmed = ltrim($digits, '0');

        if ($trimmed !== '' && $trimmed !== $digits) {
            $candidates[] = $trimmed;
        }

        return array_unique($candidates);
    }

    private static function isValidUaeSubscriber(?string $subscriber): bool
    {
        if ($subscriber === null || $subscriber === '') {
            return false;
        }

        $length = strlen($subscriber);

        if ($length === 9 && str_starts_with($subscriber, '5')) {
            return true;
        }

        if ($length >= 8 && $length <= 9 && preg_match('/^[2-9]\d+$/', $subscriber) === 1) {
            return true;
        }

        return false;
    }
}
