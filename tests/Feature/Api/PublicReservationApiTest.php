<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Image;
use App\Models\LocationCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicReservationApiTest extends TestCase
{
    private string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-01 09:00:00');

        $this->sqlitePath = database_path('testing-public-reservation.sqlite');
        if (file_exists($this->sqlitePath)) {
            unlink($this->sqlitePath);
        }

        touch($this->sqlitePath);

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $this->sqlitePath);
        config()->set('app.url', 'http://localhost');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Artisan::call('migrate:fresh', ['--force' => true]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        if (isset($this->sqlitePath) && file_exists($this->sqlitePath)) {
            unlink($this->sqlitePath);
        }

        parent::tearDown();
    }

    public function test_bootstrap_endpoint_returns_core_data(): void
    {
        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Main',
            'under_3_fee' => 10,
            'over_3_fee' => 5,
            'is_active' => true,
        ]);

        Agent::query()->firstOrCreate([
            'name' => 'Website',
        ], [
            'is_active' => true,
        ]);

        $response = $this->getJson('http://localhost/api/public/reservations/bootstrap');

        $response->assertOk();

        $payload = $response->json('data');
        $this->assertSame('AED', $payload['currency']);
        $this->assertEquals(0.05, (float) $payload['tax_rate']);
        $this->assertArrayHasKey('UAE/Dubai/Main', $payload['location_costs']);
        $this->assertEquals(10.0, (float) $payload['location_costs']['UAE/Dubai/Main']['under_3']);
        $this->assertIsInt($payload['default_agent_id']);
    }

    public function test_quote_endpoint_calculates_totals_and_detects_conflicts(): void
    {
        $car = $this->seedCarWithKnownPricing();

        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Pickup',
            'under_3_fee' => 30,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Return',
            'under_3_fee' => 20,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://localhost/api/public/reservations/quote', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Pickup',
            'return_location' => 'UAE/Dubai/Return',
            'pickup_date' => '2026-04-10 10:00:00',
            'return_date' => '2026-04-12 10:00:00',
            'selected_services' => ['additional_driver'],
            'service_quantities' => ['child_seat' => 2],
            'selected_insurance' => 'ldw_insurance',
            'driver_hours' => 10,
            'driving_license_option' => 'one_year',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.rental_days', 2)
            ->assertJsonPath('data.availability.has_conflict', false);

        $quoteData = $response->json('data');
        $this->assertEquals(200.0, (float) $quoteData['base_price']);
        $this->assertEquals(50.0, (float) $quoteData['transfer_costs']['total']);
        $this->assertEquals(100.0, (float) $quoteData['services_total']);
        $this->assertEquals(20.0, (float) $quoteData['insurance_total']);
        $this->assertEquals(330.0, (float) $quoteData['driver_cost']);
        $this->assertEquals(32.0, (float) $quoteData['driving_license_cost']);
        $this->assertEquals(732.0, (float) $quoteData['subtotal']);
        $this->assertEquals(36.6, (float) $quoteData['tax_amount']);
        $this->assertEquals(768.6, (float) $quoteData['final_total']);

        $existingCustomer = Customer::factory()->create();
        Contract::factory()->create([
            'customer_id' => $existingCustomer->id,
            'car_id' => $car->id,
            'pickup_date' => Carbon::parse('2026-04-11 08:00:00'),
            'return_date' => Carbon::parse('2026-04-13 08:00:00'),
            'current_status' => 'pending',
        ]);

        $conflictResponse = $this->postJson('http://localhost/api/public/reservations/quote', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Pickup',
            'return_location' => 'UAE/Dubai/Return',
            'pickup_date' => '2026-04-12 00:00:00',
            'return_date' => '2026-04-14 00:00:00',
        ]);

        $conflictResponse
            ->assertStatus(422)
            ->assertJsonValidationErrors(['selected_car_id']);
    }

    public function test_store_endpoint_creates_contract_and_charges(): void
    {
        $car = $this->seedCarWithKnownPricing();

        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Main',
            'under_3_fee' => 0,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        Agent::query()->firstOrCreate([
            'name' => 'Website',
        ], [
            'is_active' => true,
        ]);

        $response = $this->postJson('http://localhost/api/public/reservations/submit', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Main',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-10 10:00:00',
            'return_date' => '2026-04-12 10:00:00',
            'first_name' => 'Ali',
            'last_name' => 'Rezai',
            'email' => 'ali@example.com',
            'phone' => '+971501234567',
            'messenger_phone' => '+971501234568',
            'national_code' => '1234567890',
            'nationality' => 'Iranian',
            'kardo_required' => true,
            'payment_on_delivery' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.quote.rental_days', 2);

        $this->assertEquals(210.0, (float) $response->json('data.quote.final_total'));

        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseCount('contract_charges', 2);
        $this->assertDatabaseHas('contract_charges', [
            'title' => 'base_rental',
            'amount' => 200.00,
        ]);
        $this->assertDatabaseHas('contract_charges', [
            'title' => 'tax',
            'amount' => 10.00,
        ]);
    }

    public function test_quote_and_store_endpoints_validate_payload_fields(): void
    {
        $car = $this->seedCarWithKnownPricing();

        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Main',
            'under_3_fee' => 0,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        $invalidQuote = $this->postJson('http://localhost/api/public/reservations/quote', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'INVALID/LOCATION',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-15 10:00:00',
            'return_date' => '2026-04-14 10:00:00',
            'selected_insurance' => 'invalid_insurance',
            'selected_services' => ['invalid_service'],
            'service_quantities' => ['invalid_service' => 1],
        ]);

        $invalidQuote
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pickup_location',
                'return_date',
                'selected_insurance',
                'selected_services.0',
                'service_quantities.invalid_service',
            ]);

        $invalidStore = $this->postJson('http://localhost/api/public/reservations', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'INVALID/LOCATION',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-15 10:00:00',
            'return_date' => '2026-04-16 10:00:00',
            'first_name' => 'Ali',
            'last_name' => 'Rezai',
            'phone' => '09120000000',
            'messenger_phone' => '09120000001',
            'national_code' => '',
            'nationality' => '',
        ]);

        $invalidStore
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pickup_location',
                'phone',
                'messenger_phone',
                'national_code',
                'nationality',
            ]);
    }

    public function test_cars_endpoint_returns_encoded_image_urls_and_safe_fallback_for_missing_files(): void
    {
        $model = CarModel::factory()->create([
            'brand' => 'ImageBrand',
            'model' => 'ImageModel',
        ]);

        $carWithRealImage = Car::factory()->available()->create([
            'car_model_id' => $model->id,
        ]);

        $carWithMissingImage = Car::factory()->available()->create([
            'car_model_id' => $model->id,
        ]);
        $carWithSimilarImage = Car::factory()->available()->create([
            'car_model_id' => $model->id,
        ]);

        $relativeDir = 'assets/car-pics';
        $fileNameWithSpace = 'qa image test.webp';
        $filePath = public_path($relativeDir . '/' . $fileNameWithSpace);
        $similarStoredFile = 'qa-fuzzy-image-2026.webp';
        $similarStoredFilePath = public_path($relativeDir . '/' . $similarStoredFile);

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, 'test-image-content');
        file_put_contents($similarStoredFilePath, 'fuzzy-image-content');

        Image::query()->create([
            'file_path' => $relativeDir,
            'file_name' => $fileNameWithSpace,
            'imageable_id' => $carWithRealImage->id,
            'imageable_type' => Car::class,
        ]);

        Image::query()->create([
            'file_path' => $relativeDir,
            'file_name' => 'definitely-missing-file.webp',
            'imageable_id' => $carWithMissingImage->id,
            'imageable_type' => Car::class,
        ]);
        Image::query()->create([
            'file_path' => $relativeDir,
            'file_name' => 'qa-fuzzy-image.png',
            'imageable_id' => $carWithSimilarImage->id,
            'imageable_type' => Car::class,
        ]);

        $response = $this->getJson("http://localhost/api/public/reservations/cars?model_id={$model->id}");

        $response->assertOk();

        $cars = collect($response->json('data'));
        $realImageCar = $cars->firstWhere('id', $carWithRealImage->id);
        $missingImageCar = $cars->firstWhere('id', $carWithMissingImage->id);
        $similarImageCar = $cars->firstWhere('id', $carWithSimilarImage->id);

        $this->assertNotNull($realImageCar);
        $this->assertNotNull($missingImageCar);
        $this->assertNotNull($similarImageCar);
        $this->assertStringContainsString('qa%20image%20test.webp', $realImageCar['primary_image_url']);
        $this->assertStringContainsString('car%20test.webp', $missingImageCar['primary_image_url']);
        $this->assertStringContainsString('qa-fuzzy-image-2026.webp', $similarImageCar['primary_image_url']);

        if (is_file($filePath)) {
            unlink($filePath);
        }
        if (is_file($similarStoredFilePath)) {
            unlink($similarStoredFilePath);
        }
    }

    private function seedCarWithKnownPricing(): Car
    {
        $model = CarModel::factory()->create([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        return Car::factory()->available()->create([
            'car_model_id' => $model->id,
            'price_per_day_short' => 100,
            'price_per_day_mid' => 80,
            'price_per_day_long' => 60,
            'ldw_price_short' => 10,
            'ldw_price_mid' => 8,
            'ldw_price_long' => 6,
            'scdw_price_short' => 20,
            'scdw_price_mid' => 16,
            'scdw_price_long' => 12,
            'status' => 'available',
            'availability' => true,
        ]);
    }
}
