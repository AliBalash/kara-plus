<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestWebsiteReview;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        Livewire::actingAs($expert)
            ->test(RentalRequestWebsiteReview::class)
            ->assertSee('Website Customer')
            ->assertSee('Review requests before they reserve a car')
            ->call('claim', $websiteRequest->id)
            ->assertSee('Assigned to you');

        $this->assertDatabaseHas('contracts', [
            'id' => $websiteRequest->id,
            'user_id' => $expert->id,
            'current_status' => Contract::STATUS_REVIEW_PENDING,
        ]);
    }
}
