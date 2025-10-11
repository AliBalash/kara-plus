<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestReturnDocument;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalRequestReturnDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('myimage');
    }

    public function test_change_status_to_payment_transitions_through_returned(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('awaiting_return')
            ->create();

        $component = app(RentalRequestReturnDocument::class);
        $component->mount($contract->id);
        $component->changeStatusToPayment($contract->id);

        $contract->refresh();
        $this->assertEquals('payment', $contract->current_status);

        $statuses = $contract->statuses()->latest('id')->take(2)->pluck('status');
        $this->assertTrue($statuses->contains('returned'));
        $this->assertTrue($statuses->contains('payment'));
        $this->assertEquals('Status changed to Returned then Payment successfully.', session('message'));
    }

    public function test_change_status_to_payment_is_noop_when_already_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->status('payment')
            ->create();

        $component = app(RentalRequestReturnDocument::class);
        $component->mount($contract->id);
        $component->changeStatusToPayment($contract->id);

        $this->assertEquals('Contract is already in payment status.', session('message'));
        $this->assertEquals(0, $contract->statuses()->count());
    }
}
