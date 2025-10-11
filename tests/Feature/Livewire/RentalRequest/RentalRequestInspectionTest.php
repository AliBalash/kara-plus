<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestInspection;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestInspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_inspection_updates_status_sequence(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('delivery')
            ->create(['kardo_required' => true]);

        $pickupDocument = PickupDocument::factory()->for($contract)->create([
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'kardo_contract' => 'PickupDocument/kardo_contract_sample.jpg',
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
            'kardo_approved_at' => now(),
            'kardo_approved_by' => $user->id,
        ]);

        $component = app(RentalRequestInspection::class);
        $component->mount($contract->id);
        $component->completeInspection();

        $contract->refresh();
        $this->assertEquals('awaiting_return', $contract->current_status);
        $statuses = $contract->statuses()->latest('id')->take(2)->pluck('status');
        $this->assertTrue($statuses->contains('agreement_inspection'));
        $this->assertTrue($statuses->contains('awaiting_return'));
        $this->assertEquals('Inspection completed and status changed to awaiting_return.', session('success'));
    }
}
