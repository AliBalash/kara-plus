<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestInspectionList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestInspectionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_move_contract_to_awaiting_return_when_kardo_not_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('delivery')
            ->create([
                'kardo_required' => false,
            ]);

        PickupDocument::factory()->for($contract)->create([
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
        ]);

        $component = app(RentalRequestInspectionList::class);
        $component->moveToAwaitingReturn($contract->id);

        $contract->refresh();

        $this->assertEquals('awaiting_return', $contract->current_status);
        $statuses = $contract->statuses()->latest('id')->take(2)->pluck('status');
        $this->assertTrue($statuses->contains('agreement_inspection'));
        $this->assertTrue($statuses->contains('awaiting_return'));
        $this->assertEquals('Contract moved to awaiting return.', session('success'));
    }

    public function test_cannot_move_contract_without_required_kardo_approval(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('delivery')
            ->create([
                'kardo_required' => true,
            ]);

        PickupDocument::factory()->for($contract)->create([
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'kardo_contract' => 'PickupDocument/kardo_contract_sample.jpg',
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
            'kardo_approved_at' => null,
            'kardo_approved_by' => null,
        ]);

        $component = app(RentalRequestInspectionList::class);
        $component->moveToAwaitingReturn($contract->id);

        $contract->refresh();

        $this->assertEquals('delivery', $contract->current_status);
        $this->assertEquals('Please approve KARDO first.', session('error'));
    }
}

