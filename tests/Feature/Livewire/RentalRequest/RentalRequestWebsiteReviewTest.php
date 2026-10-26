<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestCancelledList;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestWebsiteReview;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentalRequestWebsiteReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_queue_shows_only_pending_website_requests_and_can_claim_one(): void
    {
        $expert = User::factory()->create();
        $car = Car::factory()->available()->create();
        $customer = Customer::factory()->create(['first_name' => 'Website', 'last_name' => 'Customer']);

        $websiteRequest = Contract::factory()
            ->for($customer)
            ->for($car)
            ->state([
                'requested_car_id' => $car->id,
                'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
                'current_status' => Contract::STATUS_REVIEW_PENDING,
                'user_id' => null,
            ])
            ->create();

        Contract::factory()
            ->for(Customer::factory())
            ->for($car)
            ->state([
                'intake_source' => Contract::INTAKE_SOURCE_PANEL,
                'current_status' => Contract::STATUS_REVIEW_PENDING,
            ])
            ->create();

        $this->actingAs($expert);
        Livewire::test(RentalRequestWebsiteReview::class)
            ->assertSee('Website Customer')
            ->assertSee('Review requests before they reserve a car')
            ->call('claim', $websiteRequest->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contracts', [
            'id' => $websiteRequest->id,
            'user_id' => $expert->id,
            'current_status' => Contract::STATUS_REVIEW_PENDING,
        ]);
    }

    public function test_expert_can_confirm_or_dismiss_cancellation_and_the_request_moves_to_cancelled_history(): void
    {
        $expert = User::factory()->create();
        $request = $this->websiteRequest();
        $this->actingAs($expert);
        $page = Livewire::test(RentalRequestWebsiteReview::class)
            ->assertSee('Cancel request')
            ->call('cancelRequest')->assertHasErrors(['cancellation'])
            ->call('prepareCancellation', $request->id)
            ->assertSet('cancellingRequestId', $request->id)
            ->assertSee('Confirm cancellation')
            ->set('cancellationReason', 'Customer changed plans')
            ->call('dismissCancellation')
            ->assertSet('cancellingRequestId', null);
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
        $page->call('prepareCancellation', $request->id)
            ->set('cancellationReason', 'Customer changed plans')
            ->call('cancelRequest')->assertHasNoErrors()
            ->assertSet('cancellingRequestId', null)
            ->assertViewHas('requests', fn ($rows) => $rows->total() === 0)
            ->assertViewHas('summary', fn ($summary) => $summary['total'] === 0)
            ->assertDispatched('refreshContracts');
        $this->assertSame('cancelled', $request->fresh()->current_status);
        $this->assertSame($expert->id, $request->fresh()->user_id);
        $this->assertStringContainsString('Customer changed plans', $request->statuses()->sole()->notes);
        Livewire::test(RentalRequestCancelledList::class)
            ->assertViewHas('contracts', fn ($rows) => $rows->pluck('id')->all() === [$request->id]);
    }

    public function test_other_experts_cannot_cancel_a_claimed_request_even_through_a_direct_action(): void
    {
        $owner = User::factory()->create();
        $request = $this->websiteRequest(['user_id' => $owner->id]);
        $this->actingAs(User::factory()->create());
        Livewire::test(RentalRequestWebsiteReview::class)
            ->assertDontSee('Cancel request')
            ->call('prepareCancellation', $request->id)
            ->assertHasErrors(['cancellation'])
            ->assertSet('cancellingRequestId', null);
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
    }

    public function test_stale_confirmation_does_not_cancel_a_request_that_has_moved_out_of_review(): void
    {
        $expert = User::factory()->create();
        $request = $this->websiteRequest(['user_id' => $expert->id]);
        $this->actingAs($expert);
        $page = Livewire::test(RentalRequestWebsiteReview::class)->call('prepareCancellation', $request->id);
        $request->changeStatus('assigned', $expert->id, 'Approved elsewhere');
        $page->call('cancelRequest')->assertHasErrors(['cancellation']);
        $this->assertSame('assigned', $request->fresh()->current_status);
        $this->assertSame(0, $request->statuses()->where('status', 'cancelled')->count());
    }

    public function test_cancellation_reason_is_validated_and_an_empty_reason_is_allowed(): void
    {
        $expert = User::factory()->create();
        $request = $this->websiteRequest();
        $this->actingAs($expert);
        $page = Livewire::test(RentalRequestWebsiteReview::class)->call('prepareCancellation', $request->id)
            ->set('cancellationReason', str_repeat('x', 501))->call('cancelRequest')
            ->assertHasErrors(['cancellationReason']);
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
        $page->set('cancellationReason', '')->call('cancelRequest')->assertHasNoErrors();
        $this->assertSame('cancelled', $request->fresh()->current_status);
    }

    public function test_driver_cannot_prepare_or_invoke_website_review_cancellation(): void
    {
        Role::findOrCreate('driver', 'web');
        $driver = User::factory()->create();
        $driver->assignRole('driver');
        $request = $this->websiteRequest();
        $this->actingAs($driver);
        Livewire::test(RentalRequestWebsiteReview::class)->call('prepareCancellation', $request->id)->assertForbidden();
        Livewire::test(RentalRequestWebsiteReview::class)->call('cancelRequest')->assertForbidden();
        $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
    }

    private function websiteRequest(array $attributes = []): Contract
    {
        return Contract::factory()->for(Car::factory()->available())->create([
            'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
            'current_status' => Contract::STATUS_REVIEW_PENDING,
            'user_id' => null,
            'pickup_date' => now()->addDays(2),
            'return_date' => now()->addDays(5),
            ...$attributes,
        ]);
    }
}
