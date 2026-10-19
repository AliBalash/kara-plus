<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\FleetPriceManagement;
use App\Models\AuditEvent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use App\Services\Fleet\FleetBulkPricingService;
use App\Support\FleetPriceMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FleetPriceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.export.enabled' => false]);
        URL::forceRootUrl('http://localhost');
        Role::findOrCreate('super-admin');
        $this->actor = User::factory()->create();
        $this->actor->assignRole('super-admin');
        $this->actingAs($this->actor);
    }

    private function operations(string $field, string $method = 'set', string $value = '125.00'): array
    {
        return [$field => ['enabled' => true, 'method' => $method, 'value' => $value]];
    }

    public function test_only_explicit_mixed_vehicle_ids_and_enabled_fields_change_with_auditing_and_retry_safety(): void
    {
        $toyota = CarModel::factory()->create(['brand' => 'Toyota', 'model' => 'Corolla']);
        $bmw = CarModel::factory()->create(['brand' => 'BMW', 'model' => '320i']);
        $first = Car::factory()->create(['car_model_id' => $toyota->id, 'manufacturing_year' => 2026, 'price_per_day_short' => 100]);
        $second = Car::factory()->create(['car_model_id' => $bmw->id, 'manufacturing_year' => 2018, 'ownership_type' => 'other', 'price_per_day_short' => 200]);
        $unselected = Car::factory()->create(['car_model_id' => $toyota->id, 'manufacturing_year' => 2026, 'price_per_day_short' => 300]);
        $attributes = $first->getAttributes();
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($this->actor, [$second->id, $first->id], $this->operations('price_per_day_short', 'increase_percent', '10'));
        $this->assertSame('100.00', $first->fresh()->price_per_day_short);
        $this->assertSame('110.00', $preview->payload['rows'][0]['after']['price_per_day_short']);
        $service->apply($this->actor, $preview->id);
        $service->apply($this->actor, $preview->id);
        $this->assertSame('110.00', $first->fresh()->price_per_day_short);
        $this->assertSame('220.00', $second->fresh()->price_per_day_short);
        $this->assertSame('300.00', $unselected->fresh()->price_per_day_short);
        foreach ($attributes as $key => $value) {
            if (! in_array($key, ['price_per_day_short', 'updated_at'], true)) {
                $this->assertEquals($value, $first->fresh()->getRawOriginal($key), $key);
            }
        }
        $this->assertSame(1, AuditEvent::where('action', 'fleet_base_prices_applied')->where('entity_id', $preview->id)->count());
        $this->assertTrue(AuditEvent::where('action', 'model_updated')->where('entity_type', Car::class)->where('entity_id', $first->id)->exists());
    }

    public function test_all_nine_fields_support_exact_zero_without_materializing_disabled_nullable_prices(): void
    {
        $car = Car::factory()->create(['price_per_day_mid' => null, 'price_per_day_long' => null]);
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($this->actor, [$car->id], $this->operations('price_per_day_short', 'set', '0'));
        $service->apply($this->actor, $preview->id);
        $this->assertNull($car->fresh()->price_per_day_mid);
        $this->assertNull($car->fresh()->price_per_day_long);
        $operations = [];
        foreach (FleetPriceMath::FIELDS as $field => $label) {
            $operations[$field] = ['enabled' => true, 'method' => 'set', 'value' => '12.34'];
        }
        $service->apply($this->actor, $service->preview($this->actor, [$car->id], $operations)->id);
        foreach (array_keys($operations) as $field) {
            $this->assertSame('12.34', $car->fresh()->{$field});
        }
    }

    public function test_decimal_operations_and_invalid_values(): void
    {
        foreach ([['set', '0', '0.00'], ['increase_percent', '12.5', '112.50'], ['decrease_percent', '12.5', '87.50'], ['add', '0.01', '100.01'], ['subtract', '0.01', '99.99']] as [$method, $value, $expected]) {
            $this->assertSame($expected, FleetPriceMath::adjust('100.00', $method, $value));
        }
        $this->assertSame('0.01', FleetPriceMath::adjust('0.01', 'decrease_percent', '50'));
        foreach ([['100.00', 'set', ''], ['100.00', 'set', 'NaN'], ['100.00', 'set', '1e3'], ['100.00', 'set', '-1'], ['100.00', 'set', '1.234'], ['0.00', 'subtract', '1'], ['99999999.99', 'add', '0.01'], [null, 'add', '1'], ['100.00', 'decrease_percent', '101']] as [$before, $method, $value]) {
            try {
                FleetPriceMath::adjust($before, $method, $value);
                $this->fail('Invalid adjustment accepted');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_conflicting_or_deleted_vehicle_aborts_the_entire_batch(): void
    {
        $first = Car::factory()->create(['price_per_day_short' => 100]);
        $second = Car::factory()->create(['price_per_day_short' => 100]);
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($this->actor, [$first->id, $second->id], $this->operations('price_per_day_short'));
        $second->update(['price_per_day_short' => 101]);
        try {
            $service->apply($this->actor, $preview->id);
            $this->fail('Conflict accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('preview', $exception->errors());
        }
        $this->assertSame('100.00', $first->fresh()->price_per_day_short);
        $this->assertNull($preview->fresh()->applied_at);
        $preview = $service->preview($this->actor, [$first->id, $second->id], $this->operations('price_per_day_short'));
        $second->delete();
        try {
            $service->apply($this->actor, $preview->id);
            $this->fail('Deleted selection accepted');
        } catch (ValidationException) {
            $this->assertSame('100.00', $first->fresh()->price_per_day_short);
        }
    }

    public function test_selection_is_a_snapshot_across_pages_and_filters_never_expand_it(): void
    {
        $cars = Car::factory()->count(12)->create();
        $special = CarModel::factory()->create(['brand' => 'Special', 'model' => 'Target']);
        $cars[11]->update(['car_model_id' => $special->id, 'manufacturing_year' => 2020, 'ownership_type' => 'other']);
        $test = Livewire::test(FleetPriceManagement::class)
            ->call('toggleSelection', $cars[0]->id)->call('setPage', 2)
            ->call('toggleSelection', $cars[11]->id)
            ->assertSet('selectedIds', [$cars[0]->id, $cars[11]->id])
            ->set('filters.brand', 'Special')->set('filters.year', '2020')->call('applyFilters')
            ->assertSet('selectedIds', [$cars[0]->id, $cars[11]->id])
            ->assertViewHas('cars', fn ($rows) => $rows->total() === 1)
            ->call('clearSelection')->call('resetFilters')->call('selectPage')
            ->assertSet('selectedIds', $cars->take(10)->pluck('id')->all())
            ->call('selectAllFiltered')->assertSet('selectedIds', $cars->pluck('id')->all());
        Car::factory()->create();
        $test->call('applyFilters')->assertSet('selectedIds', $cars->pluck('id')->all())
            ->call('toggleSelection', $cars[3]->id)->assertSet('selectedIds', $cars->reject(fn ($car) => $car->id === $cars[3]->id)->pluck('id')->values()->all());
    }

    public function test_filtered_out_selection_remains_explicit_and_filtered_unselected_vehicles_are_not_updated(): void
    {
        $hidden = Car::factory()->create(['price_per_day_short' => 100]);
        $model = CarModel::factory()->create(['brand' => 'TargetBrand', 'model' => 'TargetModel']);
        $visible = Car::factory()->sold()->create(['car_model_id' => $model->id, 'manufacturing_year' => 2020, 'ownership_type' => 'other', 'price_per_day_short' => 200]);
        Livewire::test(FleetPriceManagement::class)->call('toggleSelection', $hidden->id)
            ->set('filters', ['search' => $visible->plate_number, 'brand' => 'TargetBrand', 'model' => $model->id,
                'year' => '2020', 'ownership' => 'other', 'status' => 'sold', 'min' => '199', 'max' => '201'])
            ->call('applyFilters')->assertHasNoErrors()->assertViewHas('cars', fn ($rows) => $rows->total() === 1)
            ->set('operations.price_per_day_short.enabled', true)->set('operations.price_per_day_short.value', '125')
            ->call('preview')->set('confirmed', true)->call('apply')->assertHasNoErrors();
        $this->assertSame('125.00', $hidden->fresh()->price_per_day_short);
        $this->assertSame('200.00', $visible->fresh()->price_per_day_short);
    }

    public function test_pricing_permission_without_admin_role_is_sufficient_but_expired_previews_are_rejected(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage fleet prices');
        $car = Car::factory()->create();
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($user, [$car->id], $this->operations('price_per_day_short'));
        $this->travel(31)->minutes();
        $this->expectException(ValidationException::class);
        $service->apply($user, $preview->id);
    }

    public function test_confirmation_and_fresh_permission_are_required(): void
    {
        $car = Car::factory()->create();
        Livewire::test(FleetPriceManagement::class)->call('toggleSelection', $car->id)
            ->set('operations.price_per_day_short.enabled', true)->set('operations.price_per_day_short.value', '99.99')
            ->call('preview')->call('apply')->assertHasErrors('confirmed');
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($this->actor, [$car->id], $this->operations('price_per_day_short'));
        $this->actor->removeRole('super-admin');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->apply($this->actor, $preview->id);
    }

    public function test_failure_after_model_saves_rolls_back_prices_and_operation_receipt(): void
    {
        $car = Car::factory()->create(['price_per_day_short' => 100]);
        $service = app(FleetBulkPricingService::class);
        $preview = $service->preview($this->actor, [$car->id], $this->operations('price_per_day_short'));
        $this->mock(\App\Services\Audit\Contracts\AuditWriterContract::class)
            ->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        try {
            $service->apply($this->actor, $preview->id);
            $this->fail('Audit failure must abort the batch');
        } catch (\RuntimeException) {
            $this->assertSame('100.00', $car->fresh()->price_per_day_short);
            $this->assertNull($preview->fresh()->applied_at);
        }
    }

    public function test_unauthorized_page_is_blocked(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(FleetPriceManagement::class)->assertForbidden();
    }
}
