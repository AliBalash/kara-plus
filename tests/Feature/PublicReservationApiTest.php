<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\CarOption;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\LocationCost;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_returns_public_reservation_metadata(): void
    {
        Agent::query()->firstOrCreate([
            'name' => 'Website',
        ], [
            'is_active' => true,
        ]);

        LocationCost::query()->create([
            'location' => 'Dubai Marina',
            'under_3_fee' => 20,
            'over_3_fee' => 10,
            'is_active' => true,
        ]);

        LocationCost::query()->create([
            'location' => 'Inactive Location',
            'under_3_fee' => 30,
            'over_3_fee' => 15,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/public/reservations/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.default_agent_id', 1)
            ->assertJsonFragment([
                'id' => 'child_seat',
                'label_fa' => 'صندلی کودک',
            ]);

        $this->assertContains('Dubai Marina', $response->json('data.location_options', []));
    }

    public function test_cars_endpoint_marks_conflicts_as_unavailable(): void
    {
        $carModel = CarModel::factory()->create([
            'brand' => 'Kia',
            'model' => 'Pegas',
        ]);

        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'status' => 'available',
            'availability' => true,
        ]);

        CarOption::query()->create([
            'car_id' => $car->id,
            'option_key' => 'gear',
            'option_value' => 'automatic',
        ]);

        $customer = Customer::factory()->create();

        Contract::factory()->create([
            'customer_id' => $customer->id,
            'car_id' => $car->id,
            'pickup_date' => '2030-05-10 10:00:00',
            'return_date' => '2030-05-12 10:00:00',
            'current_status' => 'reserved',
        ]);

        $response = $this->getJson('/api/public/reservations/cars?pickup_date=2030-05-11%2010:00:00&return_date=2030-05-13%2010:00:00');

        $response->assertOk();

        $cars = collect($response->json('data'));
        $payload = $cars->firstWhere('id', $car->id);

        $this->assertNotNull($payload);
        $this->assertFalse($payload['is_available_for_selection']);
        $this->assertSame('reserved', $payload['conflicts'][0]['status'] ?? null);
        $this->assertSame('automatic', $payload['options']['gear'] ?? null);
    }

    public function test_quote_endpoint_returns_validation_error_for_conflicting_reservation(): void
    {
        LocationCost::query()->create([
            'location' => 'Dubai Marina',
            'under_3_fee' => 10,
            'over_3_fee' => 5,
            'is_active' => true,
        ]);

        $carModel = CarModel::factory()->create();
        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'status' => 'available',
            'availability' => true,
        ]);

        $customer = Customer::factory()->create();

        Contract::factory()->create([
            'customer_id' => $customer->id,
            'car_id' => $car->id,
            'pickup_date' => '2030-05-10 10:00:00',
            'return_date' => '2030-05-12 10:00:00',
            'current_status' => 'reserved',
        ]);

        $response = $this->postJson('/api/public/reservations/quote', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'Dubai Marina',
            'return_location' => 'Dubai Marina',
            'pickup_date' => '2030-05-11 10:00:00',
            'return_date' => '2030-05-13 10:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['selected_car_id']);
    }

    public function test_quote_endpoint_calculates_expected_totals(): void
    {
        LocationCost::query()->create([
            'location' => 'Dubai Marina',
            'under_3_fee' => 10,
            'over_3_fee' => 5,
            'is_active' => true,
        ]);

        LocationCost::query()->create([
            'location' => 'Downtown Dubai',
            'under_3_fee' => 15,
            'over_3_fee' => 7,
            'is_active' => true,
        ]);

        $carModel = CarModel::factory()->create();
        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'price_per_day_short' => 200,
            'price_per_day_mid' => 180,
            'price_per_day_long' => 160,
            'ldw_price_short' => 25,
            'ldw_price_mid' => 22,
            'ldw_price_long' => 20,
            'status' => 'available',
            'availability' => true,
        ]);

        $response = $this->postJson('/api/public/reservations/quote', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'Dubai Marina',
            'return_location' => 'Downtown Dubai',
            'pickup_date' => '2030-05-10 10:00:00',
            'return_date' => '2030-05-12 10:00:00',
            'selected_services' => ['child_seat'],
            'service_quantities' => [
                'child_seat' => 2,
            ],
            'selected_insurance' => 'ldw_insurance',
            'driver_hours' => 5,
            'driving_license_option' => 'one_year',
            'apply_discount' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.rental_days', 2)
            ->assertJsonPath('data.base_price', 400)
            ->assertJsonPath('data.services_total', 80)
            ->assertJsonPath('data.insurance_total', 50)
            ->assertJsonPath('data.driver_cost', 250)
            ->assertJsonPath('data.driving_license_cost', 32)
            ->assertJsonPath('data.transfer_costs.total', 25)
            ->assertJsonPath('data.tax_amount', 41.85)
            ->assertJsonPath('data.final_total', 878.85);
    }

    public function test_submit_endpoint_creates_contract_and_charges(): void
    {
        $dispatcher = Customer::getEventDispatcher();
        Customer::unsetEventDispatcher();

        try {
            Agent::query()->firstOrCreate([
                'name' => 'Website',
            ], [
                'is_active' => true,
            ]);

            LocationCost::query()->create([
                'location' => 'Dubai Marina',
                'under_3_fee' => 10,
                'over_3_fee' => 5,
                'is_active' => true,
            ]);

            $carModel = CarModel::factory()->create();
            $car = Car::factory()->create([
                'car_model_id' => $carModel->id,
                'price_per_day_short' => 200,
                'status' => 'available',
                'availability' => true,
            ]);

            $response = $this->postJson('/api/public/reservations/submit', [
                'selected_car_id' => $car->id,
                'pickup_location' => 'Dubai Marina',
                'return_location' => 'Dubai Marina',
                'pickup_date' => '2030-05-10 10:00:00',
                'return_date' => '2030-05-12 10:00:00',
                'selected_services' => ['additional_driver'],
                'service_quantities' => [
                    'child_seat' => 0,
                ],
                'selected_insurance' => '',
                'driver_hours' => 0,
                'apply_discount' => false,
                'first_name' => 'Sara',
                'last_name' => 'Nazari',
                'email' => 'sara@example.com',
                'phone' => '+971500000001',
                'messenger_phone' => '+971500000002',
                'national_code' => 'NC1234',
                'nationality' => 'IR',
                'notes' => 'Website reservation',
                'kardo_required' => true,
                'payment_on_delivery' => true,
                'submitted_by_name' => 'Website',
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.status', 'pending');

            $this->assertDatabaseHas('customers', [
                'phone' => '+971500000001',
                'messenger_phone' => '+971500000002',
            ]);

            $contractId = (int) $response->json('data.contract_id');
            $contract = Contract::query()->find($contractId);

            $this->assertNotNull($contract);
            $this->assertSame($car->id, $contract->car_id);
            $this->assertDatabaseHas('contract_statuses', [
                'contract_id' => $contract->id,
                'status' => 'pending',
            ]);

            $titles = ContractCharges::query()
                ->where('contract_id', $contract->id)
                ->pluck('title')
                ->all();

            $this->assertContains('base_rental', $titles);
            $this->assertContains('additional_driver', $titles);
        } finally {
            Customer::setEventDispatcher($dispatcher);
        }
    }
}
