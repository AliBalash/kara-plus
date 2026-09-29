<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The single source of truth for the base rental's billable day count.
 *
 * Policies are versioned and saved with each contract. Contracts created
 * before the grace policy was introduced deliberately keep legacy rounding.
 */
final class RentalDuration
{
    public const POLICY_LEGACY_DAILY_CEILING = 'daily_ceiling_v1';

    public const POLICY_60_MINUTE_GRACE = 'daily_ceiling_60_minute_grace_v2';

    public const CURRENT_POLICY = self::POLICY_60_MINUTE_GRACE;

    public const GRACE_MINUTES = 60;

    private const SECONDS_PER_DAY = 86400;

    public static function billableDays(
        CarbonInterface|string $pickupAt,
        CarbonInterface|string $returnAt,
        string $policy = self::CURRENT_POLICY
    ): int {
        $pickup = $pickupAt instanceof CarbonInterface ? $pickupAt : Carbon::parse($pickupAt);
        $return = $returnAt instanceof CarbonInterface ? $returnAt : Carbon::parse($returnAt);

        return self::billableDaysFromSeconds(
            $return->getTimestamp() - $pickup->getTimestamp(),
            $policy
        );
    }

    public static function billableDaysFromSeconds(
        int $durationSeconds,
        string $policy = self::CURRENT_POLICY
    ): int {
        if ($durationSeconds <= 0) {
            return 1;
        }

        $graceSeconds = $policy === self::POLICY_60_MINUTE_GRACE
            ? self::GRACE_MINUTES * 60
            : 0;

        return max(1, (int) ceil(($durationSeconds - $graceSeconds) / self::SECONDS_PER_DAY));
    }

    public static function policyFromContractMeta(?array $meta): string
    {
        $policy = data_get($meta, 'pricing_tariffs.rental_duration_policy');

        return $policy === self::POLICY_60_MINUTE_GRACE
            ? self::POLICY_60_MINUTE_GRACE
            : self::POLICY_LEGACY_DAILY_CEILING;
    }

    /** @return array{rental_duration_policy: string, rental_duration_grace_minutes: int} */
    public static function currentPolicySnapshot(): array
    {
        return [
            'rental_duration_policy' => self::CURRENT_POLICY,
            'rental_duration_grace_minutes' => self::GRACE_MINUTES,
        ];
    }
}
