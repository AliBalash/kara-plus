<?php

namespace Tests\Unit\Livewire;

use App\Livewire\Concerns\HandlesContractCancellation;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class HandlesContractCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    protected function makeComponent(): object
    {
        return new class
        {
            use HandlesContractCancellation;

            public bool $afterHookCalled = false;
            public bool $resetCalled = false;
            public array $events = [];

            public function afterContractCancelled(): void
            {
                $this->afterHookCalled = true;
            }

            public function resetPage(): void
            {
                $this->resetCalled = true;
            }

            public function dispatch($event, ...$payload): void
            {
                $this->events[] = [$event, $payload];
            }
        };
    }

    protected function createContract(string $status = 'reserved', ?Car $car = null): Contract
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $car ??= Car::factory()->create(['status' => $status === 'cancelled' ? 'available' : 'reserved']);

        return Contract::factory()
            ->for($user)
            ->for($customer)
            ->for($car)
            ->status($status)
            ->create();
    }

    public function test_cancel_contract_updates_status_and_releases_car_when_no_active_contract_remains(): void
    {
        $user = User::factory()->create();
        $component = $this->makeComponent();
        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory()->create(['status' => 'reserved']))
            ->status('reserved')
            ->create();

        $this->actingAs($user);

        $component->cancelContract($contract->id);

        $contract->refresh();

        $this->assertEquals('cancelled', $contract->current_status);
        $this->assertEquals('available', $contract->car->fresh()->status);
        $this->assertTrue($component->afterHookCalled);
        $this->assertTrue($component->resetCalled);
        $this->assertContains('refreshContracts', array_column($component->events, 0));
        $this->assertEquals('Contract cancelled successfully.', session()->get('success'));
        $this->assertEquals('cancelled', $contract->statuses()->latest('id')->first()->status);
    }

    public function test_cancel_contract_keeps_car_reserved_when_other_active_contract_exists(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create(['status' => 'reserved']);
        $targetContract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('assigned')
            ->create();

        $component = $this->makeComponent();
        $this->actingAs($user);

        $component->cancelContract($targetContract->id);

        $this->assertEquals('cancelled', $targetContract->refresh()->current_status);
        $this->assertEquals('reserved', $car->fresh()->status);
    }

    public function test_cancel_contract_exits_early_when_contract_already_cancelled(): void
    {
        $user = User::factory()->create();
        $component = $this->makeComponent();
        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory()->create(['status' => 'available']))
            ->status('cancelled')
            ->create();

        $this->actingAs($user);

        $component->cancelContract($contract->id);

        $this->assertEquals('Contract is already cancelled.', session()->get('info'));
        $this->assertEquals(0, $contract->statuses()->count());
    }
}

