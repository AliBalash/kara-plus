<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Image;
use App\Models\LocationCost;
use App\Models\VehicleCatalogItem;
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

    public function test_quote_endpoint_calculates_totals_and_reports_conflicts_without_blocking_a_request(): void
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
            ->assertOk()
            ->assertJsonPath('data.availability.has_conflict', true);
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
            ->assertJsonPath('data.status', 'review_pending')
            ->assertJsonPath('data.requires_review', true)
            ->assertJsonPath('data.quote.rental_days', 2);

        $this->assertEquals(210.0, (float) $response->json('data.quote.final_total'));

        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseHas('contracts', [
            'car_id' => $car->id,
            'requested_car_id' => $car->id,
            'intake_source' => 'website',
            'current_status' => 'review_pending',
        ]);
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

    public function test_store_accepts_a_conflicting_vehicle_as_a_review_request_and_does_not_duplicate_retries(): void
    {
        $car = $this->seedCarWithKnownPricing();
        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Main',
            'under_3_fee' => 0,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        Contract::factory()->create([
            'car_id' => $car->id,
            'pickup_date' => '2026-04-10 10:00:00',
            'return_date' => '2026-04-14 10:00:00',
            'current_status' => 'assigned',
        ]);

        $payload = [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Main',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-11 10:00:00',
            'return_date' => '2026-04-12 10:00:00',
            'first_name' => 'Conflict',
            'last_name' => 'Request',
            'email' => 'conflict-request@example.com',
            'phone' => '+971501234571',
            'messenger_phone' => '+971501234572',
            'nationality' => 'Iranian',
            'public_request_uuid' => 'b9a1a7de-1a3f-4300-9a06-2b7a65f91501',
        ];

        $first = $this->postJson('http://localhost/api/public/reservations/submit', $payload);
        $first
            ->assertCreated()
            ->assertJsonPath('data.status', Contract::STATUS_REVIEW_PENDING)
            ->assertJsonPath('data.requires_review', true);

        $second = $this->postJson('http://localhost/api/public/reservations/submit', $payload);
        $second
            ->assertCreated()
            ->assertJsonPath('data.duplicate_submission', true)
            ->assertJsonPath('data.contract_id', $first->json('data.contract_id'));

        $this->assertDatabaseCount('contracts', 2);
        $this->assertDatabaseHas('contracts', [
            'public_request_uuid' => $payload['public_request_uuid'],
            'current_status' => Contract::STATUS_REVIEW_PENDING,
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
                'nationality',
            ]);
    }

    public function test_store_endpoint_allows_empty_national_code(): void
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
            'email' => 'ali-without-national-code@example.com',
            'phone' => '+971501234569',
            'messenger_phone' => '+971501234570',
            'national_code' => '',
            'nationality' => 'Iranian',
            'kardo_required' => true,
            'payment_on_delivery' => true,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('customers', [
            'email' => 'ali-without-national-code@example.com',
            'national_code' => null,
        ]);
    }

    public function test_store_endpoint_does_not_reuse_customer_by_phone_or_national_code_without_matching_email(): void
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

        $existingCustomer = Customer::factory()->create([
            'first_name' => 'Existing',
            'last_name' => 'Customer',
            'email' => 'existing@example.com',
            'phone' => '+971501111111',
            'messenger_phone' => '+971501111112',
            'national_code' => 'NC-EXISTING',
        ]);

        $response = $this->postJson('http://localhost/api/public/reservations/submit', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Main',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-10 10:00:00',
            'return_date' => '2026-04-12 10:00:00',
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'new@example.com',
            'phone' => '+971501111111',
            'messenger_phone' => '+971501999999',
            'national_code' => 'NC-EXISTING',
            'nationality' => 'Iranian',
            'kardo_required' => true,
            'payment_on_delivery' => true,
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseHas('customers', [
            'id' => $existingCustomer->id,
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
            'phone' => '+971501111111',
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'new@example.com',
            'first_name' => 'New',
            'phone' => '+971501111111',
            'national_code' => 'NC-EXISTING',
        ]);
    }

    public function test_store_endpoint_rejects_duplicate_email(): void
    {
        $car = $this->seedCarWithKnownPricing();

        LocationCost::query()->create([
            'location' => 'UAE/Dubai/Main',
            'under_3_fee' => 0,
            'over_3_fee' => 0,
            'is_active' => true,
        ]);

        Customer::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('http://localhost/api/public/reservations/submit', [
            'selected_car_id' => $car->id,
            'pickup_location' => 'UAE/Dubai/Main',
            'return_location' => 'UAE/Dubai/Main',
            'pickup_date' => '2026-04-10 10:00:00',
            'return_date' => '2026-04-12 10:00:00',
            'first_name' => 'Ali',
            'last_name' => 'Rezai',
            'email' => 'existing@example.com',
            'phone' => '+971501234567',
            'messenger_phone' => '+971501234568',
            'nationality' => 'Iranian',
            'kardo_required' => true,
            'payment_on_delivery' => true,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cars_endpoint_returns_encoded_image_urls_and_safe_fallback_for_missing_files(): void
    {
        $realImageModel = CarModel::factory()->create([
            'brand' => 'ImageBrand',
            'model' => 'ImageModel One',
        ]);
        $missingImageModel = CarModel::factory()->create([
            'brand' => 'ImageBrand',
            'model' => 'ImageModel Two',
        ]);
        $similarImageModel = CarModel::factory()->create([
            'brand' => 'ImageBrand',
            'model' => 'ImageModel Three',
        ]);

        $carWithRealImage = Car::factory()->available()->create([
            'car_model_id' => $realImageModel->id,
        ]);

        $carWithMissingImage = Car::factory()->available()->create([
            'car_model_id' => $missingImageModel->id,
        ]);
        $carWithSimilarImage = Car::factory()->available()->create([
            'car_model_id' => $similarImageModel->id,
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

        $response = $this->getJson('http://localhost/api/public/reservations/cars');

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

    public function test_catalog_selection_resolves_a_vehicle_code_to_matching_fleet_cars(): void
    {
        $picanto = CarModel::factory()->create([
            'brand' => 'KIA',
            'model' => 'PICANTO',
        ]);
        $otherModel = CarModel::factory()->create([
            'brand' => 'KIA',
            'model' => 'RIO',
        ]);

        $matchingCar = Car::factory()->available()->create([
            'car_model_id' => $picanto->id,
            'manufacturing_year' => 2022,
        ]);
        Car::factory()->available()->create([
            'car_model_id' => $picanto->id,
            'manufacturing_year' => 2023,
        ]);
        Car::factory()->available()->create([
            'car_model_id' => $otherModel->id,
            'manufacturing_year' => 2022,
        ]);

        $response = $this->getJson('http://localhost/api/public/reservations/catalog-selection?vehicle_code=KIA-PIC-22&pickup_date=2026-04-10%2010:00:00&return_date=2026-04-12%2010:00:00');

        $response
            ->assertOk()
            ->assertJsonPath('data.catalog_item.code', 'KIA-PIC-22')
            ->assertJsonPath('data.catalog_item.manufacturing_year', 2022)
            ->assertJsonCount(1, 'data.cars')
            ->assertJsonPath('data.cars.0.id', $matchingCar->id);

        $this->getJson('http://localhost/api/public/reservations/catalog-selection?vehicle_code=UNKNOWN-CAR')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_code']);
    }

    public function test_reservation_cards_group_model_years_by_default_and_separate_enabled_model_years(): void
    {
        $model = CarModel::factory()->create([
            'brand' => 'Test Brand',
            'model' => 'Test Model',
        ]);
        $olderCar = Car::factory()->available()->create([
            'car_model_id' => $model->id,
            'manufacturing_year' => 2023,
            'price_per_day_short' => 100,
        ]);
        $newerCar = Car::factory()->available()->create([
            'car_model_id' => $model->id,
            'manufacturing_year' => 2025,
            'price_per_day_short' => 120,
        ]);

        $grouped = $this->getJson("http://localhost/api/public/reservations/cars?model_id={$model->id}");

        $grouped
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reservation_display.is_year_variant', false)
            ->assertJsonPath('data.0.reservation_display.year_from', 2023)
            ->assertJsonPath('data.0.reservation_display.year_to', 2025)
            ->assertJsonPath('data.0.reservation_display.candidate_count', 2)
            ->assertJsonPath('data.0.id', $newerCar->id);

        $model->update(['show_year_variants_in_reservation' => true]);

        $separated = $this->getJson("http://localhost/api/public/reservations/cars?model_id={$model->id}");
        $cards = collect($separated->json('data'));

        $separated->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame($newerCar->id, $cards->firstWhere('reservation_display.year', 2025)['id']);
        $this->assertSame($olderCar->id, $cards->firstWhere('reservation_display.year', 2023)['id']);
    }

    public function test_catalog_selection_falls_back_to_matching_model_when_requested_year_is_not_in_fleet(): void
    {
        $model = CarModel::factory()->create([
            'brand' => 'Fallback Brand',
            'model' => 'Fallback Model',
        ]);
        $fallbackCar = Car::factory()->available()->create([
            'car_model_id' => $model->id,
            'manufacturing_year' => 2024,
        ]);
        VehicleCatalogItem::query()->create([
            'code' => 'FALLBACK-22',
            'website_slug' => 'fallback-model-2022',
            'display_name' => 'Fallback Brand Fallback Model',
            'brand' => 'Fallback Brand',
            'model' => 'Fallback Model',
            'match_brand' => 'Fallback Brand',
            'match_model' => 'Fallback Model',
            'manufacturing_year' => 2022,
            'is_active' => true,
        ]);

        $response = $this->getJson('http://localhost/api/public/reservations/catalog-selection?vehicle_code=FALLBACK-22');

        $response
            ->assertOk()
            ->assertJsonPath('data.selection_mode', 'model_fallback')
            ->assertJsonPath('data.cars.0.id', $fallbackCar->id);
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
