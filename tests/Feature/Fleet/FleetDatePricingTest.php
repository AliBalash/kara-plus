<?php

namespace Tests\Feature\Fleet;

use App\Livewire\Pages\Panel\Expert\Car\FleetPriceManagement;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\FleetPriceWindow;
use App\Models\User;
use App\Models\VehicleCatalogItem;
use App\Services\Fleet\FleetBulkPricingService;
use App\Services\Fleet\FleetDatePricingService;
use App\Services\Fleet\SeasonalPricingService;
use App\Services\RentalPricingService;
use App\Services\Reservations\PublicReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FleetDatePricingTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-01 09:00:00', 'Asia/Dubai'));
        config(['audit.export.enabled' => false, 'app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
        Role::findOrCreate('super-admin');
        $this->actor = User::factory()->create();
        $this->actor->assignRole('super-admin');
        $this->actingAs($this->actor);
        \App\Models\LocationCost::updateOrCreate(['location' => 'Dubai'], ['is_active' => true, 'under_3_fee' => 0, 'over_3_fee' => 0]);
        $this->car = Car::factory()->create([
            'ownership_type' => 'company', 'is_company_car' => true, 'manufacturing_year' => 2026,
            'price_per_day_short' => 100, 'price_per_day_mid' => 80, 'price_per_day_long' => 60,
            'ldw_price_short' => 10, 'ldw_price_mid' => 8, 'ldw_price_long' => 6,
        ]);
    }

    private function adjustment(string $tier = 'short', string $method = 'set', string $value = '200'): array
    {
        return ['price_per_day_'.$tier => ['enabled' => true, 'method' => $method, 'value' => $value]];
    }

    private function window(bool $active = true, string $start = '2026-11-02', string $end = '2026-11-03'): array
    {
        return ['name' => 'Peak season', 'starts_on' => $start, 'ends_on' => $end, 'active' => $active];
    }

    private function applyWindow(?array $adjustment = null, ?array $window = null, ?array $ids = null): FleetPriceWindow
    {
        $preview = app(FleetDatePricingService::class)->preview($this->actor, $ids ?? [$this->car->id], $adjustment ?? $this->adjustment(), $window ?? $this->window());
        app(FleetBulkPricingService::class)->apply($this->actor, $preview->id);

        return FleetPriceWindow::where('operation_id', $preview->id)->firstOrFail();
    }

    private function payload(): array
    {
        return [
            'selected_car_id' => $this->car->id, 'pickup_date' => '2026-11-01T10:00:00+04:00', 'return_date' => '2026-11-04T10:00:00+04:00',
            'pickup_location' => 'Dubai', 'return_location' => 'Dubai', 'selected_services' => [], 'selected_insurance' => 'ldw_insurance',
            'first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'fleet-test@example.com', 'phone' => '+971501234567', 'messenger_phone' => '+971501234567',
            'public_request_uuid' => (string) Str::uuid(),
        ];
    }

    public function test_mixed_normal_seasonal_days_and_insurance_use_exact_vehicle_associations(): void
    {
        $sameModel = Car::factory()->create(['car_model_id' => $this->car->car_model_id, 'manufacturing_year' => 2026, 'price_per_day_short' => 100]);
        $event = $this->applyWindow();
        $this->assertSame([$this->car->id], $event->rates->pluck('car_id')->all());
        $quote = app(PublicReservationService::class)->quote($this->payload());
        $this->assertSame(500.0, $quote['base_price']);
        $this->assertSame(['100.00', '200.00', '200.00'], array_column($quote['rental_pricing']['daily_rates'], 'rate'));
        $this->assertSame(30.0, $quote['insurance_total']);
        $this->assertSame(556.5, $quote['final_total']);
        $this->assertSame('100.00', $this->car->fresh()->price_per_day_short);
        $other = app(SeasonalPricingService::class)->rental($sameModel, Carbon::parse('2026-11-01 10:00', 'Asia/Dubai'), Carbon::parse('2026-11-04 10:00', 'Asia/Dubai'));
        $this->assertSame('300.00', $other['base_total']);
    }

    public function test_all_duration_tiers_and_grace_boundaries_keep_total_length_tier_semantics(): void
    {
        $operations = $this->adjustment('short', 'increase_percent', '10') + $this->adjustment('mid', 'decrease_percent', '25') + $this->adjustment('long', 'set', '50');
        $this->applyWindow($operations, $this->window(true, '2026-11-01', '2026-12-31'));
        $service = app(SeasonalPricingService::class);
        $pickup = Carbon::parse('2026-11-01 23:30', 'Asia/Dubai');
        foreach ([1 => 110, 6 => 110, 7 => 60, 27 => 60, 28 => 50] as $days => $rate) {
            $quote = $service->rental($this->car, $pickup, $pickup->copy()->addDays($days)->addHour());
            $this->assertSame($days, $quote['days']);
            $this->assertSame(number_format($days * $rate, 2, '.', ''), $quote['base_total']);
        }
        $this->assertSame(7, $service->rental($this->car, $pickup, $pickup->copy()->addDays(6)->addMinutes(61))['days']);
    }

    public function test_overlapping_rules_are_blocked_per_car_and_tier_including_concurrent_previews(): void
    {
        $date = app(FleetDatePricingService::class);
        $bulk = app(FleetBulkPricingService::class);
        $a = $date->preview($this->actor, [$this->car->id], $this->adjustment(), $this->window());
        $b = $date->preview($this->actor, [$this->car->id], $this->adjustment(), $this->window());
        $bulk->apply($this->actor, $a->id);
        $bulk->apply($this->actor, $a->id);
        try {
            $bulk->apply($this->actor, $b->id);
            $this->fail('Concurrent overlapping preview accepted');
        } catch (ValidationException) {
            $this->assertSame(1, FleetPriceWindow::count());
        }
        $this->applyWindow($this->adjustment('mid'));
        $this->assertSame(2, FleetPriceWindow::count());
        $inactive = $this->applyWindow(null, $this->window(false));
        $preview = $date->previewState($this->actor, $inactive->id, true);
        try {
            $bulk->apply($this->actor, $preview->id);
            $this->fail('Overlapping activation accepted');
        } catch (ValidationException) {
            $this->assertFalse($inactive->fresh()->active);
        }
    }

    public function test_stale_quotes_require_acknowledgement_before_creation_and_retries_remain_idempotent(): void
    {
        $payload = $this->payload();
        $quote = $this->postJson('/api/public/reservations/quote', $payload)->assertOk()->json('data');
        $event = $this->applyWindow();
        $response = $this->postJson('/api/public/reservations/submit', [...$payload, 'quote_fingerprint' => $quote['quote_fingerprint']]);
        $response->assertStatus(409)->assertJsonPath('code', 'price_changed')->assertJsonPath('data.quote.base_price', 500);
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Contract::count());
        $newQuote = $response->json('data.quote');
        $created = $this->postJson('/api/public/reservations/submit', [...$payload, 'quote_fingerprint' => $newQuote['quote_fingerprint']])->assertCreated();
        $contract = Contract::findOrFail($created->json('data.contract_id'));
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $contract->current_status);
        $this->assertSame('500.00', $contract->charges()->where('type', 'base')->first()->amount);
        $this->assertSame($newQuote['rental_pricing'], $contract->meta['quote_snapshot']['rental_pricing']);
        $snapshot = $contract->meta;
        $disable = app(FleetDatePricingService::class)->previewState($this->actor, $event->id, false);
        app(FleetBulkPricingService::class)->apply($this->actor, $disable->id);
        $this->car->update(['price_per_day_short' => 600]);
        $this->assertSame($snapshot, $contract->fresh()->meta);
        $this->assertSame('556.50', $contract->fresh()->total_price);
        $this->postJson('/api/public/reservations/submit', [...$payload, 'quote_fingerprint' => $quote['quote_fingerprint']])
            ->assertCreated()->assertJsonPath('data.duplicate_submission', true);
        $this->assertSame(1, Contract::count());
        $extension = app(RentalPricingService::class)->quoteExtension($contract, $contract->return_date->copy()->addDay());
        $this->assertSame(100.0, $extension['effective_daily_rate']);
    }

    public function test_missing_seasonal_fingerprint_and_client_price_overrides_are_rejected(): void
    {
        $this->applyWindow();
        $this->postJson('/api/public/reservations/submit', $this->payload())->assertStatus(409);
        foreach ([['custom_daily_rate' => 1], ['apply_discount' => true], ['daily_rate' => 1], ['base_price' => 1], ['total_price' => 1]] as $override) {
            $this->postJson('/api/public/reservations/quote', [...$this->payload(), ...$override])->assertUnprocessable();
        }
        $this->assertSame(0, Contract::count());
    }

    public function test_date_aware_cards_and_deep_links_do_not_group_differently_priced_individuals(): void
    {
        $model = $this->car->carModel;
        VehicleCatalogItem::create([
            'code' => 'TEST-2026', 'website_slug' => 'test-2026', 'display_name' => 'Test vehicle',
            'brand' => $model->brand, 'model' => $model->model, 'match_brand' => $model->brand, 'match_model' => $model->model,
            'manufacturing_year' => 2026, 'is_active' => true,
        ]);
        $other = $this->car->replicate();
        $other->plate_number = 'OTHER';
        $other->chassis_number = 'OTHER-CHASSIS';
        $other->save();
        $this->applyWindow();
        $service = app(PublicReservationService::class);
        $cards = $service->cars(null, null, '2026-11-01 10:00', '2026-11-04 10:00');
        $this->assertCount(2, $cards);
        $this->assertSame(['300.00', '500.00'], collect($cards)->pluck('date_pricing.base_total')->sort()->values()->all());
        $linked = $service->catalogSelection('TEST-2026', '2026-11-01 10:00', '2026-11-04 10:00');
        $this->assertCount(2, $linked['cars']);
        $this->assertSame('exact_year', $linked['selection_mode']);
    }

    public function test_bulk_base_tariffs_reach_cards_deep_links_quotes_and_new_contracts(): void
    {
        $model = $this->car->carModel;
        VehicleCatalogItem::create([
            'code' => 'BASE-2026', 'website_slug' => 'base-2026', 'display_name' => 'Base vehicle',
            'brand' => $model->brand, 'model' => $model->model, 'match_brand' => $model->brand, 'match_model' => $model->model,
            'manufacturing_year' => 2026, 'is_active' => true,
        ]);
        $operations = $this->adjustment('short', 'set', '125') + $this->adjustment('mid', 'set', '110') + $this->adjustment('long', 'set', '90');
        $operations['ldw_price_short'] = ['enabled' => true, 'method' => 'set', 'value' => '20'];
        $bulk = app(FleetBulkPricingService::class);
        $bulk->apply($this->actor, $bulk->preview($this->actor, [$this->car->id], $operations)->id);
        $service = app(PublicReservationService::class);
        $this->assertSame(['short' => 125.0, 'mid' => 110.0, 'long' => 90.0], $service->cars()[0]['pricing']);
        $this->assertSame(125.0, $service->catalogSelection('BASE-2026')['cars'][0]['pricing']['short']);
        foreach ([1 => 125.0, 6 => 125.0, 7 => 110.0, 27 => 110.0, 28 => 90.0] as $days => $rate) {
            $payload = $this->payload();
            $payload['return_date'] = Carbon::parse($payload['pickup_date'])->addDays($days)->toIso8601String();
            $this->assertSame($rate * $days, $service->quote($payload)['base_price']);
        }
        $payload = $this->payload();
        $quote = $service->quote($payload);
        $this->assertSame(60.0, $quote['insurance_total']);
        $result = $service->createReservation([...$payload, 'quote_fingerprint' => $quote['quote_fingerprint']]);
        $this->assertSame(456.75, $result['total_price']);
        $contract = Contract::findOrFail($result['contract_id']);
        $before = $contract->getAttributes();
        $bulk->apply($this->actor, $bulk->preview($this->actor, [$this->car->id], $this->adjustment('short', 'set', '999'))->id);
        $this->assertSame($before, $contract->fresh()->getAttributes());
    }

    public function test_dubai_calendar_dates_work_with_utc_input_and_nullable_tier_fallbacks(): void
    {
        $this->assertSame('2026-10-01 09:30:00', app(PublicReservationService::class)->bootstrapData()['min_pickup_at']);
        $this->car->update(['price_per_day_mid' => null]);
        $this->applyWindow($this->adjustment('mid', 'increase_percent', '10'), $this->window(true, '2026-11-02', '2026-11-08'));
        $quote = app(SeasonalPricingService::class)->rental($this->car->fresh(), Carbon::parse('2026-11-01T21:00:00Z'), Carbon::parse('2026-11-08T21:00:00Z'));
        $this->assertSame('2026-11-02', $quote['daily_rates'][0]['date']);
        $this->assertSame('770.00', $quote['base_total']);
        $this->assertSame('mid', $quote['tier']);
        $this->assertNull($this->car->fresh()->price_per_day_mid);
    }

    public function test_date_preview_rejects_base_conflicts_and_insurance_adjustments(): void
    {
        $service = app(FleetDatePricingService::class);
        $preview = $service->preview($this->actor, [$this->car->id], $this->adjustment(), $this->window());
        $this->car->update(['price_per_day_short' => 101]);
        try {
            app(FleetBulkPricingService::class)->apply($this->actor, $preview->id);
            $this->fail('Changed base price accepted');
        } catch (ValidationException) {
            $this->assertSame(0, FleetPriceWindow::count());
        }
        $this->expectException(ValidationException::class);
        $service->preview($this->actor, [$this->car->id], ['ldw_price_short' => ['enabled' => true, 'method' => 'set', 'value' => '5']], $this->window());
    }

    public function test_event_activation_detects_changed_rule_values_after_preview(): void
    {
        $event = $this->applyWindow(null, $this->window(false));
        $preview = app(FleetDatePricingService::class)->previewState($this->actor, $event->id, true);
        $event->rates->first()->update(['value' => '250']);
        try {
            app(FleetBulkPricingService::class)->apply($this->actor, $preview->id);
            $this->fail('Changed rule accepted');
        } catch (ValidationException) {
            $this->assertFalse($event->fresh()->active);
            $this->assertNull($preview->fresh()->applied_at);
        }
    }

    public function test_daily_price_changes_require_new_acknowledgement_even_when_the_total_is_the_same(): void
    {
        $event = $this->applyWindow(null, $this->window(true, '2026-11-02', '2026-11-02'));
        $payload = $this->payload();
        $old = app(PublicReservationService::class)->quote($payload);
        $disable = app(FleetDatePricingService::class)->previewState($this->actor, $event->id, false);
        app(FleetBulkPricingService::class)->apply($this->actor, $disable->id);
        $this->applyWindow(null, $this->window(true, '2026-11-03', '2026-11-03'));
        $response = $this->postJson('/api/public/reservations/submit', [...$payload, 'quote_fingerprint' => $old['quote_fingerprint']]);
        $this->assertSame(400.0, $old['base_price']);
        $response->assertStatus(409)->assertJsonPath('data.quote.base_price', 400);
        $this->assertSame(0, Contract::count());
    }

    public function test_malformed_date_input_is_a_validation_error(): void
    {
        $this->expectException(ValidationException::class);
        app(FleetDatePricingService::class)->preview($this->actor, [$this->car->id], $this->adjustment(), [...$this->window(), 'starts_on' => ['invalid']]);
    }

    public function test_same_page_creates_date_events_only_for_selected_ids(): void
    {
        Livewire::test(FleetPriceManagement::class)->call('toggleSelection', $this->car->id)->set('mode', 'date')
            ->set('window', $this->window())->set('operations.price_per_day_short.enabled', true)
            ->set('operations.price_per_day_short.value', '250')->call('preview')->assertHasNoErrors()
            ->set('confirmed', true)->call('apply')->assertHasNoErrors();
        $this->assertSame(1, FleetPriceWindow::count());
        $this->assertSame('100.00', $this->car->fresh()->price_per_day_short);
    }
}
