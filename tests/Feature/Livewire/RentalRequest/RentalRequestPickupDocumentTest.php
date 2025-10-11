<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPickupDocument;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalRequestPickupDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
    }

    public function test_change_status_to_delivery_updates_contract(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('reserved')
            ->create();

        PickupDocument::factory()->for($contract)->create();

        $component = app(RentalRequestPickupDocument::class);
        $component->mount($contract->id);
        $component->changeStatusToDelivery($contract->id);

        $contract->refresh();
        $this->assertEquals('delivery', $contract->current_status);
        $this->assertEquals('delivery', $contract->statuses()->latest('id')->first()->status);
        $this->assertEquals('Status changed to Delivery successfully.', session('message'));
    }
}
