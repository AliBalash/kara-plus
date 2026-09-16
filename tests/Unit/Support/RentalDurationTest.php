<?php

namespace Tests\Unit\Support;

use App\Support\RentalDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RentalDurationTest extends TestCase
{
    #[DataProvider('currentPolicyBoundaries')]
    public function test_current_policy_applies_one_grace_hour_at_each_daily_ceiling(
        string $returnAt,
        int $expectedDays
    ): void {
        $this->assertSame(
            $expectedDays,
            RentalDuration::billableDays('2026-09-15 10:00:00', $returnAt)
        );
    }

    public static function currentPolicyBoundaries(): array
    {
        return [
            'less than one day' => ['2026-09-16 09:59:59', 1],
            'exactly one day' => ['2026-09-16 10:00:00', 1],
            'one day plus exactly 60 minutes' => ['2026-09-16 11:00:00', 1],
            'one day plus 60 minutes and one second' => ['2026-09-16 11:00:01', 2],
            'two days plus exactly 60 minutes' => ['2026-09-17 11:00:00', 2],
            'two days plus 61 minutes' => ['2026-09-17 11:01:00', 3],
        ];
    }

    public function test_legacy_policy_keeps_existing_contract_rounding(): void
    {
        $this->assertSame(2, RentalDuration::billableDays(
            '2026-09-15 10:00:00',
            '2026-09-16 10:00:01',
            RentalDuration::POLICY_LEGACY_DAILY_CEILING
        ));
    }

    public function test_contracts_without_a_saved_policy_are_grandfathered_to_legacy_rounding(): void
    {
        $this->assertSame(
            RentalDuration::POLICY_LEGACY_DAILY_CEILING,
            RentalDuration::policyFromContractMeta(['pricing_tariffs' => ['base_days' => 1]])
        );
        $this->assertSame(
            RentalDuration::CURRENT_POLICY,
            RentalDuration::policyFromContractMeta([
                'pricing_tariffs' => RentalDuration::currentPolicySnapshot(),
            ])
        );
    }
}
