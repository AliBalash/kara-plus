<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractCarAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_active_contract_marks_car_unavailable(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state([
                'pickup_date' => Carbon::now()->subDay(),
                'return_date' => Carbon::now()->addDay(),
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_awaiting_return_contract_keeps_car_reserved(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(2),
                'return_date' => null,
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_upcoming_contract_marks_car_pre_reserved(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state(function () {
                $pickup = Carbon::now()->addDays(5);

                return [
                    'pickup_date' => $pickup,
                    'return_date' => $pickup->copy()->addDays(3),
                ];
            })
            ->create();

        $car->refresh();

        $this->assertEquals('pre_reserved', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_completing_contract_releases_car_when_no_other_active_contract_exists(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('delivery')
            ->create();

        $contract->changeStatus('complete', $user->id);

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_car_remains_reserved_if_other_active_contract_exists(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();
        $now = Carbon::now();

        $activeContract = Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->state([
                'pickup_date' => $now->copy()->subDay(),
                'return_date' => $now->copy()->addDay(),
            ])
            ->create();

        $completingContract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('delivery')
            ->state([
                'pickup_date' => $now->copy()->subDays(2),
                'return_date' => $now->copy()->addDays(2),
            ])
            ->create();

        $completingContract->changeStatus('complete', $user->id);

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
        $this->assertEquals('reserved', $activeContract->fresh()->current_status);
    }

    public function test_payment_status_releases_car(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(3),
                'return_date' => null,
            ])
            ->create();

        $contract->changeStatus('payment', $user->id);

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_does_not_override_sold_car_status(): void
    {
        $car = Car::factory()->sold()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state([
                'pickup_date' => Carbon::now()->subDay(),
                'return_date' => Carbon::now()->addDay(),
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('sold', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_deleting_contract_releases_car_when_no_active_contract_remains(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);

        $contract = Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->create();

        $contract->delete();

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_operational_state_restores_ready_car_availability_when_no_reservation_exists(): void
    {
        $car = Car::factory()->create([
            'status' => 'available',
            'availability' => false,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_operational_state_disables_under_maintenance_car_even_without_reservation(): void
    {
        $car = Car::factory()->create([
            'status' => 'under_maintenance',
            'availability' => true,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame('under_maintenance', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_sync_operational_state_releases_stale_pre_reserved_status_without_upcoming_reservation(): void
    {
        $car = Car::factory()->create([
            'status' => 'pre_reserved',
            'availability' => true,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame('available', $car->status);
        $this->assertTrue($car->availability);
    }
}
