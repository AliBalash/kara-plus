<?php

namespace Tests\Unit;

use App\AI\AiContextSanitizer;
use App\AI\AiResponseValidator;
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
}
