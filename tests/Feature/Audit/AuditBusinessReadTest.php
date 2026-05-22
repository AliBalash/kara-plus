<?php

namespace Tests\Feature\Audit;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestDetail;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBusinessReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_business_read_route_is_logged(): void
    {
        $user = User::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for(Car::factory())
            ->create();

        $this->actingAs($user);
        app(RentalRequestDetail::class)->mount($contract->id);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'business_read',
        ]);
    }
}
