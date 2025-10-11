<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPaymentList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RentalRequestPaymentListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    protected function makePaymentContract(string $firstName, string $lastName): Contract
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
        $car = Car::factory()->create();

        return Contract::factory()
            ->for($user)
            ->for($customer)
            ->for($car)
            ->status('payment')
            ->create();
    }

    public function test_change_status_to_complete_updates_contract_and_sets_flash_message(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('payment')
            ->create();

        $this->actingAs($user);

        $component = app(RentalRequestPaymentList::class);
        $component->mount();

        $component->changeStatusToComplete($contract->id);

        $this->assertEquals('complete', $contract->fresh()->current_status);
        $this->assertEquals('Status changed to complete successfully.', session()->get('success'));
        $this->assertCount(1, $contract->statuses()->where('status', 'complete')->get());
    }

    public function test_apply_search_filters_contracts_by_customer_name(): void
    {
        $matchingContract = $this->makePaymentContract('Sara', 'Smith');
        $nonMatchingContract = $this->makePaymentContract('Ali', 'Karimi');
        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('reserved')
            ->create();

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = app(RentalRequestPaymentList::class);
        $component->mount();
        $component->searchInput = 'Smith';
        $component->applySearch();

        $component->render();

        $contracts = $component->paymentContracts;
        $this->assertCount(1, $contracts);
        $this->assertTrue($contracts->first()->is($matchingContract));
        $this->assertFalse($contracts->contains($nonMatchingContract));
    }
}
