<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestListTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_to_me_updates_contract_owner_and_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for(Customer::factory())
            ->for(Car::factory())
            ->create(['user_id' => null, 'current_status' => 'pending']);

        $component = app(RentalRequestList::class);
        $component->mount();
        $component->assignToMe($contract->id);

        $contract->refresh();
        $this->assertEquals($user->id, $contract->user_id);
        $this->assertEquals('assigned', $contract->current_status);
        $this->assertEquals('Contract assigned to you successfully.', session('success'));
    }
}
