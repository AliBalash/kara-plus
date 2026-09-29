<?php

namespace Tests\Feature\Ai;

use App\AI\AiInsightService;
use App\Models\AiRun;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['model' => 'llama-3.3-70b-versatile', 'usage' => ['prompt_tokens' => 101, 'completion_tokens' => 32], 'choices' => [['message' => ['content' => json_encode(['headline' => 'Review required', 'summary' => 'Verified risk.', 'critical_alerts' => [['fact_id' => 'OVERDUE_RETURN:contract:'.$contract->id, 'title' => 'Late return', 'reason' => 'Past planned return', 'check_now' => 'Open contract']], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []])]]]], 200)]);

        $first = app(AiInsightService::class)->generate('contract_brief', $contract->id);
        $second = app(AiInsightService::class)->generate('contract_brief', $contract->id);

        $this->assertSame('ready', $first['state']);
        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
        $this->assertNotNull($first['generated_at']);
        $this->assertNotNull($first['expires_at']);
        $this->assertDatabaseCount('ai_insights', 1);
        $run = AiRun::where('status', 'success')->firstOrFail();
        $this->assertSame(101, $run->input_tokens);
        $this->assertSame(32, $run->output_tokens);
        $this->assertSame('fallback_chain', $run->strategy);
        $ajilCalls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(1, $ajilCalls);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/') && ! str_contains($request->body(), $contract->customer->phone));
        Http::assertSent(function ($request) use ($contract): bool {
            $requestBody = json_decode($request->body(), true);
            $context = json_decode($requestBody['messages'][1]['content'] ?? '', true)['context'] ?? [];

            return ($context['vehicle']['id'] ?? null) === $contract->car_id
                && array_key_exists('pickup_document_present', $context['documents'] ?? [])
                && ($context['payment_summary']['pending_count'] ?? null) === 1
                && ($context['customer_operational_profile']['total_contracts'] ?? null) === 1
                && array_key_exists('status_timeline', $context)
                && ! array_key_exists('notes', $context)
                && ! array_key_exists('phone', $context);
        });
    }

    public function test_new_analysis_bypasses_an_unchanged_valid_cache(): void
    {
        config()->set('ai.features.dashboard_operations', true);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response(['model' => 'test-model', 'choices' => [['message' => ['content' => json_encode(['headline' => 'Fresh', 'summary' => 'Regenerated.', 'critical_alerts' => [], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []])]]]], 200)]);

        $first = app(AiInsightService::class)->generate('dashboard_operations');
        $forced = app(AiInsightService::class)->generate('dashboard_operations', null, true);

        $this->assertSame('ready', $first['state']);
        $this->assertSame('ready', $forced['state']);
        $this->assertFalse($forced['cached']);
        $this->assertDatabaseCount('ai_insights', 2);
        $calls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(2, $calls);
    }

    public function test_repeated_failures_open_a_short_circuit(): void
    {
        config()->set('ai.circuit.failure_threshold', 1);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([], 429)]);
        $first = app(AiInsightService::class)->generate('dashboard_operations');
        $second = app(AiInsightService::class)->generate('dashboard_operations');
        $this->assertSame('unavailable', $first['state']);
        $this->assertSame('unavailable', $second['state']);
        $this->assertNotEmpty($first['facts']);
        $this->assertNotEmpty($second['facts']);
        $this->assertArrayNotHasKey('data', $second);
        $ajilCalls = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(1, $ajilCalls);
    }

    public function test_disabled_feature_never_calls_ajil(): void
    {
        config()->set('ai.features.dashboard_operations', false);

        $result = app(AiInsightService::class)->generate('dashboard_operations');

        $this->assertSame('disabled', $result['state']);
        Http::assertNothingSent();
    }

    public function test_malformed_ai_response_is_not_cached_or_displayed(): void
    {
        config()->set('ai.features.dashboard_operations', true);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([
            'model' => 'llama-3.3-70b-versatile',
            'choices' => [['message' => ['content' => '{not-json}']]],
        ], 200)]);

        $result = app(AiInsightService::class)->generate('dashboard_operations');

        $this->assertSame('unavailable', $result['state']);
        $this->assertDatabaseCount('ai_insights', 0);
        $this->assertDatabaseHas('ai_runs', ['feature' => 'dashboard_operations', 'status' => 'unavailable']);
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
        $engine = app(\App\AI\AiFactEngine::class);
        $facts = $engine->dashboard();
        $context = [];
        $version = app(\App\AI\PromptRegistry::class)->version('dashboard_operations');
        // Must match sanitization/compaction in AiInsightService: we use raw facts/context before sanitization but test uses compacted empty — use same as service computes with real facts
        // For empty DB, dashboard returns operations_clear fallback, so compute with actual facts
        $sanitizedContext = app(\App\AI\AiContextSanitizer::class)->sanitize($context);
        [$compactFacts, $compactContext] = app(\App\AI\AiTokenBudgeter::class)->compact($facts, $sanitizedContext, config('ai.max_facts'), config('ai.max_context_bytes'));
        $hash = hash('sha256', json_encode([$compactFacts, $compactContext, $version]));
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

    public function test_customer_and_vehicle_briefs_use_bounded_anonymized_context(): void
    {
        config()->set('ai.features.customer_brief', true);
        config()->set('ai.features.vehicle_brief', true);
        $customer = Customer::factory()->create(['gender' => 'female', 'passport_expiry_date' => now()->subDay()]);
        $car = Car::factory()->available()->create(['service_due_date' => now()->subDay()]);
        Insurance::create(['car_id' => $car->id, 'expiry_date' => now()->subDay(), 'status' => 'done']);
        Contract::factory()->for($customer)->for($car)->status('complete')->count(4)->create();
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([
            'model' => 'gemini-3.8-flash',
            'choices' => [['message' => ['content' => json_encode(['headline' => 'Review', 'summary' => 'Open verified records.', 'critical_alerts' => [], 'watchlist' => [], 'positive_signals' => [], 'data_quality_warnings' => [], 'insufficient_data' => []])]]],
        ], 200)]);

        $customerResult = app(AiInsightService::class)->generate('customer_brief', $customer->id);
        $vehicleResult = app(AiInsightService::class)->generate('vehicle_brief', $car->id);
        $dashboardFacts = app(\App\AI\AiFactEngine::class)->dashboard();

        $this->assertSame('ready', $customerResult['state']);
        $this->assertSame('ready', $vehicleResult['state']);
        $this->assertContains('CUSTOMER:passport_expired', array_column($customerResult['facts'], 'fact_id'));
        $this->assertContains('VEHICLE:insurance_expired', array_column($vehicleResult['facts'], 'fact_id'));
        $this->assertContains('DASHBOARD:expired_insurance', array_column($dashboardFacts, 'fact_id'));
        $requests = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'));
        $this->assertCount(2, $requests);
        foreach ($requests as [$request]) {
            $this->assertStringNotContainsString($customer->first_name, $request->body());
            $this->assertStringNotContainsString($customer->phone, $request->body());
            $this->assertStringNotContainsString($car->plate_number, $request->body());
            $payload = json_decode($request['messages'][1]['content'], true);
            $this->assertLessThanOrEqual(3, count($payload['context']['recent_contracts'] ?? []));
        }
    }

    public function test_renewed_insurance_does_not_raise_an_expiry_alert(): void
    {
        $car = Car::factory()->available()->create(['service_due_date' => now()->addMonths(2)]);
        Insurance::create(['car_id' => $car->id, 'expiry_date' => now()->subMonth(), 'status' => 'done']);
        Insurance::create(['car_id' => $car->id, 'expiry_date' => now()->addMonths(6), 'status' => 'done']);

        $engine = app(\App\AI\AiFactEngine::class);
        $vehicleFacts = $engine->vehicle($car->fresh());
        $dashboardFacts = $engine->dashboard();

        $this->assertNotContains('VEHICLE:insurance_expired', array_column($vehicleFacts, 'fact_id'));
        $this->assertNotContains('DASHBOARD:expired_insurance', array_column($dashboardFacts, 'fact_id'));
    }
}
