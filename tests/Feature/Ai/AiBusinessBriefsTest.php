<?php

namespace Tests\Feature\Ai;

use App\AI\AiBusinessBriefs;
use App\AI\AiInsightService;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractBalanceTransfer;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use App\Livewire\Ai\InsightCard;
use Tests\TestCase;

class AiBusinessBriefsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.enabled', true);
        config()->set('ai.ajil.base_url', 'http://ajil.test');
        config()->set('ai.ajil.token', 'test-token');
        config()->set('audit.export.enabled', false);
        config()->set('audit.elasticsearch.enabled', false);
        Http::fake(['ajil.test/v1/chat/completions' => Http::response([
            'model' => 'gemini-3.5-flash-lite',
            'choices' => [['message' => ['content' => json_encode([
                'headline' => 'Review verified records', 'summary' => 'Open the linked records before acting.',
                'critical_alerts' => [], 'watchlist' => [], 'positive_signals' => [],
                'data_quality_warnings' => [], 'insufficient_data' => [],
            ])]]],
        ], 200)]);
    }

    public function test_website_reservation_triage_uses_live_approval_checks_without_customer_identity(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create(['gender' => 'female']);
        $car = Car::factory()->available()->create();
        $pickup = now()->addDays(3)->startOfHour();
        Contract::factory()->for($customer)->for($car)->status('assigned')->create([
            'pickup_date' => $pickup,
            'return_date' => $pickup->copy()->addDays(3),
        ]);
        $request = Contract::factory()->for($customer)->for($car)->status('review_pending')->create([
            'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
            'requested_car_id' => $car->id,
            'pickup_date' => $pickup->copy()->addDay(),
            'return_date' => $pickup->copy()->addDays(4),
            'total_price' => 120,
            'meta' => ['quote_snapshot' => ['final_total' => 100]],
        ]);

        $result = app(AiInsightService::class)->generate('reservation_triage', $request->id);

        $this->assertSame('ready', $result['state']);
        $ids = array_column($result['facts'], 'fact_id');
        $this->assertContains('RESERVATION:reservation_conflict:'.$request->id, $ids);
        $this->assertContains('RESERVATION:quote_changed:'.$request->id, $ids);
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
        Http::assertSent(function ($httpRequest) use ($customer, $car): bool {
            $payload = json_decode($httpRequest['messages'][1]['content'], true);

            return str_starts_with($httpRequest->url(), 'http://ajil.test/')
                && ($payload['context']['checks_clear_now'] ?? true) === false
                && ! str_contains($httpRequest->body(), $customer->phone)
                && ! str_contains($httpRequest->body(), $customer->first_name)
                && ! str_contains($httpRequest->body(), $car->plate_number);
        });
    }

    public function test_finance_brief_uses_contract_balance_and_separates_pending_ledger_types(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $customer = Customer::factory()->create(['gender' => 'female']);
        $contract = Contract::factory()->for($customer)->create(['total_price' => 1000]);
        foreach ([['rental_fee', 300, 'approved'], ['discount', 50, 'pending'], ['payment_back', 20, 'pending']] as [$type, $amount, $status]) {
            Payment::factory()->create([
                'contract_id' => $contract->id,
                'customer_id' => $customer->id,
                'car_id' => $contract->car_id,
                'user_id' => $user->id,
                'payment_type' => $type,
                'amount' => $amount,
                'amount_in_aed' => $amount,
                'currency' => 'AED',
                'approval_status' => $status,
                'is_paid' => $status === 'approved',
                'payment_date' => now()->toDateString(),
            ]);
        }
        $other = Contract::factory()->for($customer)->create();
        ContractBalanceTransfer::create([
            'from_contract_id' => $other->id, 'to_contract_id' => $contract->id,
            'customer_id' => $customer->id, 'created_by' => $user->id,
            'amount' => 25, 'currency' => 'AED', 'transferred_at' => now(),
        ]);
        ContractBalanceTransfer::create([
            'from_contract_id' => $contract->id, 'to_contract_id' => $other->id,
            'customer_id' => $customer->id, 'created_by' => $user->id,
            'amount' => 10, 'currency' => 'AED', 'transferred_at' => now(),
        ]);

        $result = app(AiInsightService::class)->generate('contract_finance', $contract->id);

        $this->assertSame('ready', $result['state']);
        $this->assertContains('FINANCE:pending_approval:'.$contract->id, array_column($result['facts'], 'fact_id'));
        $request = Http::recorded(fn ($request) => str_starts_with($request->url(), 'http://ajil.test/'))[0][0];
        $payload = json_decode($request['messages'][1]['content'], true);
        $this->assertSame((float) $contract->calculateRemainingBalance(), (float) $payload['context']['operational_balance_aed']);
        $this->assertTrue($payload['context']['ledger_includes_pending_entries']);
        $this->assertSame(2, collect($payload['facts'])->firstWhere('type', 'pending_approval')['metrics']['count']);
        $this->assertSame(25.0, (float) $payload['context']['incoming_transfer_aed']);
        $this->assertSame(10.0, (float) $payload['context']['outgoing_transfer_aed']);
        $this->assertCount(2, $payload['context']['recent_balance_transfers']);
        $this->assertArrayNotHasKey('pending_amount_aed', $payload['context']);
        $this->assertStringNotContainsString($customer->phone, $request->body());
        $card = Livewire::test(InsightCard::class, ['feature' => 'contract_finance', 'entityId' => $contract->id])
            ->call('load')->assertSet('state', 'ready');
        $this->assertNotEmpty($card->get('facts'));
        $card
            ->assertSee('Verified panel facts')
            ->assertSee('Calculated operational contract balance');
    }

    public function test_queue_and_fleet_outlook_use_bounded_aggregate_facts(): void
    {
        $expert = User::factory()->create();
        $this->actingAs($expert);
        $customer = Customer::factory()->create(['gender' => 'female']);
        $car = Car::factory()->available()->create(['service_due_date' => now()->subDay()]);
        Insurance::create(['car_id' => $car->id, 'expiry_date' => now()->subDay(), 'status' => 'done']);
        $websiteRequest = Contract::factory()->for($customer)->for($car)->status('review_pending')->create([
            'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
            'user_id' => null,
            'created_at' => now()->subDays(2),
        ]);
        $overdue = Contract::factory()->for($customer)->for($car)->status('awaiting_return')->create([
            'return_date' => now()->subDay(),
        ]);

        [$queueFacts, $queueContext] = app(AiBusinessBriefs::class)->reservationQueue();
        [$fleetFacts, $fleetContext] = app(AiBusinessBriefs::class)->fleetOutlook();

        $this->assertSame(1, $queueContext['open_count']);
        $this->assertSame(1, $queueContext['unassigned_count']);
        $this->assertContains('RESERVATION_QUEUE:older_than_day', array_column($queueFacts, 'fact_id'));
        $this->assertContains('RESERVATION_QUEUE:request_waiting:'.$websiteRequest->id, array_column($queueFacts, 'fact_id'));
        $this->assertSame(1, $fleetContext['overdue_returns_total']);
        $this->assertContains('FLEET:overdue_return:'.$overdue->id, array_column($fleetFacts, 'fact_id'));
        $this->assertContains('FLEET:insurance_past', array_column($fleetFacts, 'fact_id'));
        $this->assertLessThanOrEqual(13, count($fleetFacts));
    }
}
