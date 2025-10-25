<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestTarsApproval;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestTarsApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_inspection_moves_contract_to_awaiting_return(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('delivery')
            ->create(['kardo_required' => false]);

        PickupDocument::factory()->for($contract)->create([
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
        ]);

        $component = app(RentalRequestTarsApproval::class);
        $component->mount($contract->id);
        $component->completeInspection();

        $contract->refresh();
        $this->assertEquals('agreement_inspection', $contract->current_status);
        $this->assertEquals('Inspection completed and status changed to agreement_inspection.', session('success'));

        $component->moveToAwaitingReturn();

        $contract->refresh();
        $this->assertEquals('awaiting_return', $contract->current_status);
        $statuses = $contract->statuses()->latest('id')->take(2)->pluck('status');
        $this->assertTrue($statuses->contains('agreement_inspection'));
        $this->assertTrue($statuses->contains('awaiting_return'));
        $this->assertEquals('Status changed to awaiting_return.', session('success'));
    }
}
