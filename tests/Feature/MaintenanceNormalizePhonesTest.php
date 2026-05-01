<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Http\Controllers\MaintenanceController;
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

        $this->actingAs($user);

        $response = app(MaintenanceController::class)->normalizeCustomerPhones();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $response->getData(true)['normalized']);

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
