<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestKardoApproval;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalRequestKardoApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('myimage');
    }

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

        PickupDocument::factory()->for($contract)->create([
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'kardo_contract' => 'PickupDocument/kardo_contract_sample.jpg',
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
            'kardo_approved_at' => now(),
            'kardo_approved_by' => $user->id,
        ]);

        $component = app(RentalRequestKardoApproval::class);
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

    public function test_kardo_page_resolves_uploaded_document_from_stored_record_path(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('delivery')
            ->create(['kardo_required' => true]);

        $storedPath = 'PickupDocument/kardo-contract/' . $contract->id . '/kardo-contract-example.jpg';
        Storage::disk('myimage')->put($storedPath, 'kardo-file');

        PickupDocument::factory()->for($contract)->create([
            'kardo_contract' => $storedPath,
            'tars_approved_at' => now(),
            'tars_approved_by' => $user->id,
        ]);

        $component = app(RentalRequestKardoApproval::class);
        $component->mount($contract->id);

        $this->assertSame(Storage::disk('myimage')->url($storedPath), $component->existingFiles['kardoContract']);
    }
}
