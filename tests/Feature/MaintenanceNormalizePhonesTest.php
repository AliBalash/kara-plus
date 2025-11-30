<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceNormalizePhonesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_customer_phones_through_route(): void
    {
        $user = User::factory()->create();

        $customerWithIranianNumber = Customer::factory()->create([
            'phone' => '09123020296',
            'messenger_phone' => '0501234567',
        ]);

        $customerWithUaeNumber = Customer::factory()->create([
            'phone' => '+98‪9124822681',
            'messenger_phone' => '971501972285',
        ]);

        $response = $this->actingAs($user)->post(route('maintenance.normalize-phones'));

        $response->assertOk()->assertJson([
            'normalized' => 2,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customerWithIranianNumber->id,
            'phone' => '+989123020296',
            'messenger_phone' => '+971501234567',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customerWithUaeNumber->id,
            'phone' => '+989124822681',
            'messenger_phone' => '+971501972285',
        ]);
    }
}
