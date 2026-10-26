<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarList;
use App\Models\Car;
use App\Models\CarOption;
use App\Models\Contract;
use App\Models\User;
use App\Services\ContractAmendmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CarListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('car_pics');
    }

    public function test_deletecar_removes_image_record_options_and_file(): void
    {
        $car = Car::factory()->create();

        CarOption::create([
            'car_id' => $car->id,
            'option_key' => 'gear',
            'option_value' => 'automatic',
        ]);

        $image = $car->image()->create([
            'file_path' => 'car-pics/',
            'file_name' => 'car-list-delete.webp',
        ]);

        Storage::disk('car_pics')->put('car-list-delete.webp', 'car-image');

        app(CarList::class)->deletecar($car->id);

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('car_options', ['car_id' => $car->id]);
        Storage::disk('car_pics')->assertMissing('car-list-delete.webp');
    }

    public function test_available_status_with_false_availability_is_shown_as_unavailable(): void
    {
        Car::factory()->create([
            'plate_number' => '51004-V',
            'status' => 'available',
            'availability' => false,
        ]);

        $car = Car::where('plate_number', '51004-V')->firstOrFail();

        $this->assertSame('unavailable', $car->operationalStatus());
        $this->assertSame('Unavailable', $car->operationalStatusLabel());
    }

    public function test_status_filter_uses_operational_availability_not_only_status_column(): void
    {
        $unavailable = Car::factory()->create([
            'status' => 'available',
            'availability' => false,
        ]);

        $available = Car::factory()->available()->create();

        $this->assertSame(
            [$available->id],
            Car::query()->byOperationalStatus('available')->pluck('id')->all()
        );

        $this->assertSame(
            [$unavailable->id],
            Car::query()->byOperationalStatus('unavailable')->pluck('id')->all()
        );
    }

    public function test_unavailable_reason_filter_can_target_specific_hold_reason(): void
    {
        $maintenanceCar = Car::factory()->unavailable(Car::UNAVAILABILITY_REASON_MAINTENANCE)->create();
        Car::factory()->unavailable(Car::UNAVAILABILITY_REASON_INSURANCE)->create();

        $component = app(CarList::class);
        $component->unavailabilityReasonFilter = Car::UNAVAILABILITY_REASON_MAINTENANCE;
        $component->applyFilters();

        $cars = Car::query()
            ->when($component->statusFilter, fn ($builder) => $builder->byOperationalStatus($component->statusFilter))
            ->when($component->unavailabilityReasonFilter, fn ($builder) => $builder->byUnavailabilityReason($component->unavailabilityReasonFilter))
            ->pluck('id')
            ->all();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $component->statusFilter);
        $this->assertSame([$maintenanceCar->id], $cars);
    }

    public function test_reservation_selection_scope_blocks_unavailable_and_maintenance_cars(): void
    {
        $unavailable = Car::factory()->create([
            'status' => 'available',
            'availability' => false,
        ]);

        $maintenance = Car::factory()->create([
            'status' => 'under_maintenance',
            'availability' => false,
        ]);

        $reserved = Car::factory()->create([
            'status' => 'reserved',
            'availability' => false,
        ]);

        $preReserved = Car::factory()->create([
            'status' => 'pre_reserved',
            'availability' => true,
        ]);

        $this->assertFalse($unavailable->isSelectableForReservation());
        $this->assertFalse($maintenance->isSelectableForReservation());
        $this->assertTrue($reserved->isSelectableForReservation());
        $this->assertTrue($preReserved->isSelectableForReservation());

        $this->assertSame(
            [$reserved->id, $preReserved->id],
            Car::query()->reservableForSelection()->orderBy('id')->pluck('id')->all()
        );
    }

    public function test_available_filter_with_date_range_keeps_cars_without_conflicting_bookings(): void
    {
        Car::factory()->available()->create([
            'plate_number' => 'FREE-1001',
        ]);

        $conflictingCar = Car::factory()->available()->create([
            'plate_number' => 'BUSY-1002',
        ]);

        Contract::factory()->for($conflictingCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-15 10:00:00'),
            'return_date' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $component = app(CarList::class);
        $component->statusFilter = 'available';
        $component->pickupFrom = '2026-07-13';
        $component->pickupTo = '2026-07-19';

        $query = Car::query()
            ->when($component->statusFilter, fn ($builder) => $builder->byOperationalStatus($component->statusFilter));

        $applyDateFilters = new \ReflectionMethod($component, 'applyDateFilters');
        $applyDateFilters->setAccessible(true);
        $applyDateFilters->invoke($component, $query);

        $cars = $query->orderBy('plate_number')->pluck('plate_number')->all();

        $this->assertSame(['FREE-1001'], $cars);
    }

    public function test_daily_price_filter_applies_price_range_and_normalizes_reversed_bounds(): void
    {
        Car::factory()->available()->create([
            'plate_number' => 'LOW-1001',
            'price_per_day_short' => 120,
        ]);

        Car::factory()->available()->create([
            'plate_number' => 'MID-1002',
            'price_per_day_short' => 240,
        ]);

        Car::factory()->available()->create([
            'plate_number' => 'HIGH-1003',
            'price_per_day_short' => 380,
        ]);

        $component = app(CarList::class);
        $component->dailyPriceMin = '300';
        $component->dailyPriceMax = '200';

        $query = Car::query();

        $applyDailyPriceFilters = new \ReflectionMethod($component, 'applyDailyPriceFilters');
        $applyDailyPriceFilters->setAccessible(true);
        $applyDailyPriceFilters->invoke($component, $query);

        $cars = $query->orderBy('plate_number')->pluck('plate_number')->all();

        $this->assertSame(['MID-1002'], $cars);
    }

    public function test_pickup_range_filters_pickups_not_overlapping_rentals_and_includes_both_days(): void
    {
        $expected = [];
        foreach (['2026-09-30 10:00:00', '2026-10-19 23:59:59', '2026-10-20 00:00:00', '2026-10-24 23:59:59', '2026-10-25 00:00:00'] as $index => $pickup) {
            $car = Car::factory()->create();
            Contract::factory()->for($car)->status('reserved')->create([
                'pickup_date' => $pickup, 'return_date' => '2026-10-30 12:00:00',
            ]);
            if (in_array($index, [2, 3], true)) {
                $expected[] = $car->id;
            }
        }
        Car::factory()->create(); // A vehicle without a booking must not match.

        $component = app(CarList::class);
        $component->pickupFrom = '2026-10-20';
        $component->pickupTo = '2026-10-24';
        $component->applyFilters();

        $this->assertSame($expected, $component->render()->getData()['cars']->pluck('id')->all());
    }

    public function test_pickup_date_boundaries_can_be_used_independently(): void
    {
        $ids = [];
        foreach (['2026-10-19 12:00:00', '2026-10-20 12:00:00', '2026-10-25 12:00:00'] as $pickup) {
            $car = Car::factory()->create();
            $ids[] = $car->id;
            Contract::factory()->for($car)->status('reserved')->create([
                'pickup_date' => $pickup, 'return_date' => '2026-10-30 12:00:00',
            ]);
        }

        $component = app(CarList::class);
        $component->pickupFrom = '2026-10-20';
        $this->assertSame([$ids[1], $ids[2]], $component->render()->getData()['cars']->pluck('id')->all());
        $component->pickupFrom = null;
        $component->pickupTo = '2026-10-20';
        $this->assertSame([$ids[0], $ids[1]], $component->render()->getData()['cars']->pluck('id')->all());
    }

    public function test_displayed_booking_matches_pickup_filter_even_when_car_has_an_older_active_booking(): void
    {
        $car = Car::factory()->create();
        Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => '2026-09-30 10:00:00', 'return_date' => '2026-10-21 10:00:00',
        ]);
        $matching = Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => '2026-10-22 14:30:00', 'return_date' => '2026-10-24 14:30:00',
        ]);
        $component = app(CarList::class);
        $component->pickupFrom = '2026-10-20';
        $component->pickupTo = '2026-10-24';
        $cars = $component->render()->getData()['cars'];

        $this->assertCount(1, $cars);
        $this->assertSame($matching->id, $cars->first()->currentContract->id);

        $component->clearFilters();
        $this->assertNull($component->pickupFrom);
        $this->assertNull($component->pickupTo);
        $this->assertSame('2026-09-30', $component->render()->getData()['cars']->first()->currentContract->pickup_date->toDateString());
    }

    public function test_invalid_date_filters_show_errors_without_query_exceptions(): void
    {
        Car::factory()->create();
        foreach ([['not-a-date', null], ['2026-02-30', null], ['2026-10-24', '2026-10-20']] as [$from, $to]) {
            $component = app(CarList::class);
            $component->pickupFrom = $from;
            $component->pickupTo = $to;
            $this->assertCount(0, $component->render()->getData()['cars']);
            $this->assertTrue($component->getErrorBag()->isNotEmpty());
            try {
                $component->applyFilters();
                $this->fail('Invalid date filters must fail validation.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_return_to_includes_the_entire_day_and_excludes_later_returns_and_closed_bookings(): void
    {
        $ids = [];
        foreach (['2026-10-26 12:00:00', '2026-10-27 00:00:00', '2026-10-27 23:59:59', '2026-10-28 00:00:00', '2026-10-31 12:00:00'] as $return) {
            $car = Car::factory()->create();
            $ids[] = $car->id;
            Contract::factory()->for($car)->status('reserved')->create([
                'pickup_date' => '2026-09-27 10:00:00', 'return_date' => $return,
            ]);
        }
        $closed = Car::factory()->create();
        Contract::factory()->for($closed)->status('cancelled')->create([
            'pickup_date' => '2026-09-27 10:00:00', 'return_date' => '2026-10-27 12:00:00',
        ]);
        Car::factory()->create();

        $component = app(CarList::class);
        $component->returnTo = '2026-10-27';
        $component->applyFilters();
        $cars = $component->render()->getData()['cars'];
        $this->assertSame(array_slice($ids, 0, 3), $cars->pluck('id')->all());
        $this->assertSame('2026-10-27 23:59:59', $cars->last()->currentContract->return_date->format('Y-m-d H:i:s'));
        $component->returnFrom = '2026-10-27';
        $this->assertSame([$ids[1], $ids[2]], $component->render()->getData()['cars']->pluck('id')->all());
        $component->returnTo = null;
        $this->assertSame(array_slice($ids, 1), $component->render()->getData()['cars']->pluck('id')->all());
        $component->clearFilters();
        $this->assertNull($component->returnFrom);
        $this->assertNull($component->returnTo);
    }

    public function test_pickup_and_return_bounds_match_the_same_booking_and_show_that_booking(): void
    {
        $splitMatch = Car::factory()->create();
        Contract::factory()->for($splitMatch)->status('reserved')->create([
            'pickup_date' => '2026-09-01 10:00:00', 'return_date' => '2026-10-27 10:00:00',
        ]);
        Contract::factory()->for($splitMatch)->status('reserved')->create([
            'pickup_date' => '2026-10-20 10:00:00', 'return_date' => '2026-10-30 10:00:00',
        ]);
        $matchingCar = Car::factory()->create();
        Contract::factory()->for($matchingCar)->status('reserved')->create([
            'pickup_date' => '2026-09-01 10:00:00', 'return_date' => '2026-10-19 10:00:00',
        ]);
        $matching = Contract::factory()->for($matchingCar)->status('reserved')->create([
            'pickup_date' => '2026-10-20 10:00:00', 'return_date' => '2026-10-27 10:00:00',
        ]);
        $component = app(CarList::class);
        $component->pickupFrom = '2026-10-20';
        $component->returnTo = '2026-10-27';
        $cars = $component->render()->getData()['cars'];
        $this->assertSame([$matchingCar->id], $cars->pluck('id')->all());
        $this->assertSame($matching->id, $cars->first()->currentContract->id);
    }

    public function test_invalid_return_bounds_fail_validation_and_never_produce_an_unfiltered_report(): void
    {
        Car::factory()->create();
        foreach ([['invalid', null], [null, '2026-02-30'], ['2026-10-28', '2026-10-27']] as [$from, $to]) {
            $component = app(CarList::class);
            $component->returnFrom = $from;
            $component->returnTo = $to;
            $this->assertCount(0, $component->render()->getData()['cars']);
            $this->assertTrue($component->getErrorBag()->isNotEmpty());
            try {
                $component->applyFilters();
                $this->fail('Invalid return bounds must fail validation.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_return_report_uses_the_new_planned_return_after_an_approved_extension(): void
    {
        $car = Car::factory()->available()->create();
        $contract = Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => '2026-10-01 10:00:00', 'return_date' => '2026-10-27 10:00:00',
        ]);
        $component = app(CarList::class);
        $component->returnTo = '2026-10-27';
        $this->assertSame([$car->id], $component->render()->getData()['cars']->pluck('id')->all());
        $service = app(ContractAmendmentService::class);
        $service->approve($service->requestExtension($contract, '2026-10-28 10:00:00', $contract->user_id), $contract->user_id);

        $this->assertCount(0, $component->render()->getData()['cars']);
        $component->returnTo = '2026-10-28';
        $result = $component->render()->getData()['cars']->sole();
        $this->assertSame($car->id, $result->id);
        $this->assertSame('2026-10-28', $result->currentContract->return_date->toDateString());
    }

    public function test_return_filter_is_available_in_the_form_and_can_be_loaded_from_a_report_link(): void
    {
        $this->actingAs(User::factory()->create());
        $car = Car::factory()->create();
        Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => '2026-09-27 10:00:00', 'return_date' => '2026-10-27 23:59:59',
        ]);
        Livewire::withQueryParams(['returnTo' => '2026-10-27'])->test(CarList::class)
            ->assertSee('Return From')->assertSee('Return To')
            ->assertSet('returnTo', '2026-10-27')
            ->call('applyFilters')->assertHasNoErrors()
            ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === [$car->id])
            ->call('clearFilters')->assertSet('returnFrom', null)->assertSet('returnTo', null);
    }
}
