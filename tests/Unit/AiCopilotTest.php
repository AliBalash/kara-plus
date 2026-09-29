<?php

namespace Tests\Unit;

use App\AI\AiContextSanitizer;
use App\AI\AiFactEngine;
use App\AI\AiResponseValidator;
use App\AI\AiTokenBudgeter;
use Tests\TestCase;

class AiCopilotTest extends TestCase
{
    public function test_sensitive_context_fields_are_removed_recursively(): void
    {
        $result = app(AiContextSanitizer::class)->sanitize(['phone' => 'secret', 'customer_id' => 7, 'nested' => ['email' => 'secret', 'safe' => true]]);
        $this->assertSame(['customer_id' => 7, 'nested' => ['safe' => true]], $result);
    }

    public function test_response_validator_drops_unknown_fact_ids(): void
    {
        $result = app(AiResponseValidator::class)->validate(['headline' => 'Review', 'summary' => 'One item', 'critical_alerts' => [['fact_id' => 'known', 'title' => 'Known', 'reason' => 'Reason', 'check_now' => 'Open'], ['fact_id' => 'invented', 'title' => 'Bad']], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []], [['fact_id' => 'known']]);
        $this->assertCount(1, $result['critical_alerts']);
        $this->assertSame('known', $result['critical_alerts'][0]['fact_id']);
    }

    public function test_response_validator_only_keeps_bounded_display_fields(): void
    {
        $result = app(AiResponseValidator::class)->validate([
            'headline' => str_repeat('H', 300),
            'summary' => 'Verified summary',
            'raw_customer_data' => 'must not be cached',
            'watchlist' => [['fact_id' => 'known', 'title' => str_repeat('T', 200), 'reason' => 'Review', 'check_now' => 'Open', 'extra' => 'discard']],
        ], [['fact_id' => 'known']]);

        $this->assertArrayNotHasKey('raw_customer_data', $result);
        $this->assertSame(120, mb_strlen($result['headline']));
        $this->assertSame(100, mb_strlen($result['watchlist'][0]['title']));
        $this->assertArrayNotHasKey('extra', $result['watchlist'][0]);
    }

    public function test_budgeter_keeps_highest_severity_facts_inside_the_budget(): void
    {
        $facts = [
            ['fact_id' => 'low', 'severity' => 10],
            ['fact_id' => 'critical', 'severity' => 100],
            ['fact_id' => 'high', 'severity' => 70],
        ];
        [$facts] = app(AiTokenBudgeter::class)->compact($facts, [], 2, 1024);
        $this->assertSame(['critical', 'high'], array_column($facts, 'fact_id'));
    }

    public function test_contract_pulse_is_deterministic_and_not_model_calculated(): void
    {
        $pulse = app(AiFactEngine::class)->contractPulse([['severity' => 100], ['severity' => 70]]);
        $this->assertSame(['score' => 55, 'label' => 'Needs attention', 'issues_count' => 2], $pulse);
    }
}
