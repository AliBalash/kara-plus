<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Panel\Expert\Dashboard;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAvailableFleetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-31 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_available_fleet_defaults_to_our_available_cars_sorted_by_latest_return_with_never_returned_cars_last(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ourLatest = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:30:00',
            carOverrides: [
                'plate_number' => 'OUR-9001',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'available',
                'availability' => true,
            ]
        );

        $ourOlder = $this->createReturnedCar(
            returnedAt: '2026-03-30 18:00:00',
            carOverrides: [
                'plate_number' => 'OUR-9002',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'available',
                'availability' => true,
            ]
        );

        $this->createReturnedCar(
            returnedAt: '2026-03-31 07:00:00',
            carOverrides: [
                'plate_number' => 'PTN-1001',
                'ownership_type' => 'liverpool',
                'is_company_car' => false,
                'status' => 'available',
                'availability' => true,
            ]
        );

        $ourNoReturn = Car::factory()->available()->create([
            'plate_number' => 'OUR-NORET',
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'available',
            'availability' => true,
        ]);

        $this->createReturnedCar(
            returnedAt: '2026-03-31 06:30:00',
            carOverrides: [
                'plate_number' => 'OUR-OFF',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'under_maintenance',
                'availability' => true,
            ]
        );

        $component = app(Dashboard::class);
        $component->mount();

        $cars = $component->getAvailableCarsProperty();

        $this->assertSame([$ourLatest->id, $ourOlder->id, $ourNoReturn->id], $cars->pluck('id')->all());
        $this->assertSame(3, $component->getAvailableCarsTotalProperty());
    }

    public function test_switching_scope_to_all_includes_other_fleets_and_supports_oldest_return_sort(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ourLatest = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:30:00',
            carOverrides: [
                'plate_number' => 'OUR-9101',
                'ownership_type' => 'company',
                'is_company_car' => true,
            ]
        );

        $partner = $this->createReturnedCar(
            returnedAt: '2026-03-30 22:00:00',
            carOverrides: [
                'plate_number' => 'PTN-1101',
                'ownership_type' => 'golden_key',
                'is_company_car' => false,
            ]
        );

        $ourOldest = $this->createReturnedCar(
            returnedAt: '2026-03-30 10:00:00',
            carOverrides: [
                'plate_number' => 'OUR-9102',
                'ownership_type' => 'company',
                'is_company_car' => true,
            ]
        );

        $component = app(Dashboard::class);
        $component->mount();
        $component->availableFleetScope = 'all';
        $component->availableSort = 'returned_oldest';

        $cars = $component->getAvailableCarsProperty();

        $this->assertSame([$ourOldest->id, $partner->id, $ourLatest->id], $cars->pluck('id')->all());
    }

    public function test_pre_reserved_cars_are_included_only_when_readiness_filter_allows_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $availableCar = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:45:00',
            carOverrides: [
                'plate_number' => 'OUR-READY',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'available',
            ]
        );

        $preReservedCar = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:15:00',
            carOverrides: [
                'plate_number' => 'OUR-PRE',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'available',
            ]
        );

        Contract::factory()->create([
            'car_id' => $preReservedCar->id,
            'current_status' => 'reserved',
            'pickup_date' => now()->addDay(),
            'return_date' => now()->addDays(3),
        ]);

        $component = app(Dashboard::class);
        $component->mount();

        $defaultCars = $component->getAvailableCarsProperty();
        $this->assertSame([$availableCar->id], $defaultCars->pluck('id')->all());

        $component->availableReadiness = 'available_pre_reserved';
        $carsWithPreReserved = $component->getAvailableCarsProperty();

        $this->assertSame([$availableCar->id, $preReservedCar->id], $carsWithPreReserved->pluck('id')->all());
    }

    public function test_fleet_status_summary_stays_locked_to_our_fleet_when_inventory_scope_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ourReturned = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:45:00',
            carOverrides: [
                'plate_number' => 'OUR-9201',
                'ownership_type' => 'company',
                'is_company_car' => true,
                'status' => 'available',
                'availability' => true,
            ]
        );

        $ourNeverReturned = Car::factory()->available()->create([
            'plate_number' => 'OUR-9202',
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'available',
            'availability' => true,
        ]);

        $partnerReturned = $this->createReturnedCar(
            returnedAt: '2026-03-31 08:15:00',
            carOverrides: [
                'plate_number' => 'PTN-9201',
                'ownership_type' => 'golden_key',
                'is_company_car' => false,
                'status' => 'available',
                'availability' => true,
            ]
        );

        $ourBooked = Car::factory()->create([
            'plate_number' => 'OUR-BOOK',
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'reserved',
            'availability' => false,
        ]);

        $partnerBooked = Car::factory()->create([
            'plate_number' => 'PTN-BOOK',
            'ownership_type' => 'liverpool',
            'is_company_car' => false,
            'status' => 'reserved',
            'availability' => false,
        ]);

        Contract::factory()->create([
            'car_id' => $ourBooked->id,
            'current_status' => 'assigned',
            'pickup_date' => now()->addHours(4),
            'return_date' => now()->addDays(2),
        ]);

        Contract::factory()->create([
            'car_id' => $partnerBooked->id,
            'current_status' => 'assigned',
            'pickup_date' => now()->addHours(6),
            'return_date' => now()->addDays(3),
        ]);

        $component = app(Dashboard::class);
        $component->mount();

        $ourFleetSummary = [
            'total' => 3,
            'available' => 2,
            'booked' => 1,
            'unavailable' => 0,
            'availability_rate' => 67,
            'active_reservations' => 1,
            'upcoming_pickups' => 1,
        ];

        $this->assertSame($ourFleetSummary, $component->fleetStatusSummary);

        $this->assertSame([$ourReturned->id, $ourNeverReturned->id], $component->getAvailableCarsProperty()->pluck('id')->all());
        $this->assertSame($component->fleetStatusSummary['available'], $component->getAvailableCarsTotalProperty());

        $component->availableFleetScope = 'all';
        $component->render();

        $this->assertSame($ourFleetSummary, $component->fleetStatusSummary);

        $this->assertSame(
            [$ourReturned->id, $partnerReturned->id, $ourNeverReturned->id],
            $component->getAvailableCarsProperty()->pluck('id')->all()
        );
        $this->assertSame(3, $component->getAvailableCarsTotalProperty());
        $this->assertNotSame($component->fleetStatusSummary['available'], $component->getAvailableCarsTotalProperty());
    }

    public function test_dashboard_builds_fleet_status_summary_with_reservations_and_availability_breakdown(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'available',
            'availability' => true,
        ]);

        Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'available',
            'availability' => false,
        ]);

        Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'under_maintenance',
            'availability' => false,
        ]);

        $bookedNow = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'reserved',
            'availability' => false,
        ]);

        $bookedUpcoming = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'pre_reserved',
            'availability' => true,
        ]);

        Contract::factory()->create([
            'car_id' => $bookedNow->id,
            'current_status' => 'reserved',
            'pickup_date' => now()->subHours(3),
            'return_date' => now()->addDay(),
        ]);

        Contract::factory()->create([
            'car_id' => $bookedUpcoming->id,
            'current_status' => 'assigned',
            'pickup_date' => now()->addHours(8),
            'return_date' => now()->addDays(2),
        ]);

        Contract::factory()->create([
            'car_id' => $bookedUpcoming->id,
            'current_status' => 'pending',
            'pickup_date' => now()->addDays(2),
            'return_date' => now()->addDays(4),
        ]);

        $component = app(Dashboard::class);
        $component->mount();

        $this->assertSame([
            'total' => 5,
            'available' => 1,
            'booked' => 2,
            'unavailable' => 2,
            'availability_rate' => 20,
            'active_reservations' => 2,
            'upcoming_pickups' => 1,
        ], $component->fleetStatusSummary);
    }

    public function test_dashboard_treats_pre_reserved_with_false_availability_as_unavailable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'status' => 'pre_reserved',
            'availability' => false,
        ]);

        $component = app(Dashboard::class);
        $component->mount();

        $this->assertSame([
            'total' => 1,
            'available' => 0,
            'booked' => 0,
            'unavailable' => 1,
            'availability_rate' => 0,
            'active_reservations' => 0,
            'upcoming_pickups' => 0,
        ], $component->fleetStatusSummary);
    }

    private function createReturnedCar(string $returnedAt, array $carOverrides = []): Car
    {
        $car = Car::factory()->create(array_merge([
            'status' => 'available',
            'availability' => true,
            'ownership_type' => 'company',
            'is_company_car' => true,
        ], $carOverrides));

        $returnedMoment = Carbon::parse($returnedAt);

        $contract = Contract::factory()->create([
            'car_id' => $car->id,
            'current_status' => 'complete',
            'pickup_date' => $returnedMoment->copy()->subDays(3),
            'return_date' => $returnedMoment->copy()->subHours(2),
        ]);

        $status = ContractStatus::query()->create([
            'contract_id' => $contract->id,
            'status' => 'returned',
        ]);

        $status->timestamps = false;
        $status->forceFill([
            'created_at' => $returnedMoment,
            'updated_at' => $returnedMoment,
        ])->save();

        return $car;
    }
}
