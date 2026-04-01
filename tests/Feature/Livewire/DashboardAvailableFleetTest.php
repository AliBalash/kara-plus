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

    public function test_available_fleet_defaults_to_our_returned_and_available_cars_sorted_by_latest_return(): void
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

        Car::factory()->available()->create([
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

        $this->assertSame([$ourLatest->id, $ourOlder->id], $cars->pluck('id')->all());
        $this->assertSame(2, $component->getAvailableCarsTotalProperty());
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

        ContractStatus::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'returned',
            'created_at' => $returnedMoment,
            'updated_at' => $returnedMoment,
        ]);

        return $car;
    }
}
