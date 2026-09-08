<?php

namespace Tests\Feature\Ai;

use App\AI\AiInsightService;
use App\Models\AiInsight;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.enabled', true);
        config()->set('ai.features.contract_brief', true);
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        config()->set('ai.ajil.token', 'test-token');
    }

    public function test_contract_insight_is_cached_and_never_sends_customer_pii(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $contract = Contract::factory()->status('awaiting_return')->create(['return_date' => now()->subHour()]);
        Payment::factory()->create(['contract_id' => $contract->id, 'customer_id' => $contract->customer_id, 'car_id' => $contract->car_id, 'approval_status' => 'pending', 'amount_in_aed' => 900]);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['model' => 'llama-3.3-70b-versatile', 'choices' => [['message' => ['content' => json_encode(['headline' => 'Review required', 'summary' => 'Verified risk.', 'critical_alerts' => [['fact_id' => 'OVERDUE_RETURN:contract:'.$contract->id, 'title' => 'Late return', 'reason' => 'Past planned return', 'check_now' => 'Open contract']], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []])]]]], 200)]);

        $first = app(AiInsightService::class)->generate('contract_brief', $contract->id);
        $second = app(AiInsightService::class)->generate('contract_brief', $contract->id);

        $this->assertSame('ready', $first['state']);
        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
        $this->assertDatabaseCount('ai_insights', 1);
        $ajilCalls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(1, $ajilCalls);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/') && !str_contains($request->body(), $contract->customer->phone));
    }

    public function test_repeated_failures_open_a_short_circuit(): void
    {
        config()->set('ai.circuit.failure_threshold', 1);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([], 503)]);
        $first = app(AiInsightService::class)->generate('dashboard_operations');
        $second = app(AiInsightService::class)->generate('dashboard_operations');
        $this->assertSame('unavailable', $first['state']);
        $this->assertSame('unavailable', $second['state']);
        $ajilCalls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(1, $ajilCalls);
    }
}
