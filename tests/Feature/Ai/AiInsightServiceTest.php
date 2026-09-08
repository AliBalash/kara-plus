<?php

namespace Tests\Feature\Ai;

use App\AI\AiInsightService;
use App\Models\AiInsight;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
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
        config()->set('audit.export.enabled', false);
        config()->set('audit.elasticsearch.enabled', false);
    }

    public function test_contract_insight_is_cached_and_never_sends_customer_pii(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        // Supplying gender prevents the Customer model's unrelated legacy
        // Genderize hook from making a real network call in this AI test.
        $customer = Customer::factory()->create(['gender' => 'female']);
        $contract = Contract::factory()->for($customer)->status('awaiting_return')->create(['return_date' => now()->subHour()]);
        Payment::create([
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'car_id' => $contract->car_id,
            'user_id' => $user->id,
            'amount' => 900,
            'amount_in_aed' => 900,
            'currency' => 'AED',
            'payment_type' => 'rental_fee',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'is_paid' => false,
            'approval_status' => 'pending',
        ]);
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

    public function test_dashboard_context_is_cacheable_when_the_verified_facts_are_unchanged(): void
    {
        config()->set('ai.features.dashboard_operations', true);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['model' => 'llama-3.3-70b-versatile', 'choices' => [['message' => ['content' => json_encode(['headline' => 'Stable', 'summary' => 'No new risks.', 'critical_alerts' => [], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []])]]]], 200)]);

        $first = app(AiInsightService::class)->generate('dashboard_operations');
        $second = app(AiInsightService::class)->generate('dashboard_operations');

        $this->assertSame('ready', $first['state']);
        $this->assertTrue($second['cached']);
        $calls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(1, $calls);
    }

    public function test_identical_in_flight_request_does_not_call_ajil_twice(): void
    {
        config()->set('ai.features.dashboard_operations', true);
        $hash = hash('sha256', json_encode([[], [], 'dashboard_operations:v1']));
        $lock = Cache::lock('kara-ai:inflight:'.$hash, 30);
        $this->assertTrue($lock->get());
        try {
            $result = app(AiInsightService::class)->generate('dashboard_operations');
            $this->assertSame('busy', $result['state']);
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }
}
