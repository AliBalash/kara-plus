<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CreateCarForm;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CreateCarFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_creates_car_and_options(): void
    {
        Storage::fake('car_pics');

        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Tesla',
            'model' => 'Model S',
        ]);

        $component = Mockery::mock(CreateCarForm::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount();

        $component->is_featured = true;
        $component->newImage = null;
        $component->car_options = [
            'gear' => 'automatic',
            'seats' => 4,
            'doors' => 4,
            'luggage' => 3,
            'min_days' => 2,
            'fuel_type' => 'petrol',
            'unlimited_km' => true,
            'base_insurance' => true,
        ];

        $validatedData = [
            'selectedBrand' => $carModel->brand,
            'selectedModelId' => $carModel->id,
            'plate_number' => 'AB-1234',
            'status' => 'available',
            'availability' => true,
            'mileage' => 1200,
            'price_per_day_short' => 300.75,
            'price_per_day_mid' => 250.5,
            'price_per_day_long' => 200.25,
            'ldw_price_short' => 20.5,
            'ldw_price_mid' => 18.25,
            'ldw_price_long' => 15.75,
            'scdw_price_short' => 25.5,
            'scdw_price_mid' => 22.25,
            'scdw_price_long' => 20.75,
            'service_due_date' => now()->addMonth()->toDateString(),
            'damage_report' => 'Minor scratch on rear bumper',
            'manufacturing_year' => now()->year,
            'color' => 'Red',
            'chassis_number' => 'CHASSIS1234567890',
            'gps' => true,
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'passing_date' => now()->subMonths(6)->toDateString(),
            'passing_valid_for_days' => 180,
            'registration_valid_for_days' => 200,
            'notes' => 'Fleet unit',
            'passing_status' => 'done',
            'registration_status' => 'done',
            'car_options' => $component->car_options,
        ];

        $component->shouldReceive('validate')->once()->andReturn($validatedData);

        $component->submit();

        $car = Car::where('plate_number', 'AB-1234')->first();
        $this->assertNotNull($car, 'Car record should exist after submission');
        $this->assertEquals($carModel->id, $car->car_model_id);
        $this->assertEquals('Red', $car->color);
        $this->assertEquals(true, $car->gps);

        $this->assertEquals(8, $car->options()->count());
        $this->assertTrue($carModel->fresh()->is_featured);
        $this->assertEquals('Car added successfully!', session('message'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
