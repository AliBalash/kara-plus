<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
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
            ->create();

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
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

        $activeContract = Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->create();

        $completingContract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('delivery')
            ->create();

        $completingContract->changeStatus('complete', $user->id);

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
        $this->assertEquals('reserved', $activeContract->fresh()->current_status);
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
}
