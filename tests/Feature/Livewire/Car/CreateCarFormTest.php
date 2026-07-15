<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CreateCarForm;
use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use App\Models\CarModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        $component = app(CreateCarForm::class);
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
            'ownership_type' => 'safe_drive',
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

        foreach ($validatedData as $field => $value) {
            $component->{$field} = $value;
        }

        $component->submit();

        $car = Car::where('plate_number', 'AB-1234')->first();
        $this->assertNotNull($car, 'Car record should exist after submission');
        $this->assertEquals($carModel->id, $car->car_model_id);
        $this->assertEquals('Red', $car->color);
        $this->assertEquals(true, $car->gps);
        $this->assertEquals('safe_drive', $car->ownership_type);
        $this->assertFalse($car->is_company_car);

        $this->assertEquals(8, $car->options()->count());
        $this->assertTrue($carModel->fresh()->is_featured);
        $this->assertEquals('Car added successfully!', session('message'));
    }

    public function test_create_form_uses_base_status_preview(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Tesla',
            'model' => 'Model 3',
        ]);

        $component = app(CreateCarForm::class);
        $component->mount();
        $component->updatedSelectedBrand($carModel->brand);
        $component->selectedModelId = (string) $carModel->id;
        $component->status = Car::MANUAL_STATUS_SOLD;
        $component->updatedStatus(Car::MANUAL_STATUS_SOLD);

        $this->assertSame('Sold', $component->effectiveStatusLabel);
    }

    public function test_submit_can_create_unavailable_car_with_history_window(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Nissan',
            'model' => 'Sunny',
        ]);

        $component = app(CreateCarForm::class);
        $component->mount();
        $component->selectedBrand = $carModel->brand;
        $component->selectedModelId = (string) $carModel->id;
        $component->plate_number = 'UN-1234';
        $component->status = Car::MANUAL_STATUS_UNAVAILABLE;
        $component->hold_reason = Car::UNAVAILABILITY_REASON_REGISTRATION;
        $component->hold_start_date = Carbon::today()->toDateString();
        $component->hold_end_date = Carbon::today()->addDays(2)->toDateString();
        $component->hold_note = 'New car registration hold';
        $component->mileage = 10;
        $component->manufacturing_year = now()->year;
        $component->color = 'White';
        $component->chassis_number = 'CREATEUNAVAILABLE123';

        $component->submit();

        $car = Car::query()->where('plate_number', 'UN-1234')->first();
        $this->assertNotNull($car);
        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertFalse((bool) $car->availability);
        $this->assertSame(Car::UNAVAILABILITY_REASON_REGISTRATION, $car->unavailability_reason);

        $this->assertDatabaseHas('car_unavailability_periods', [
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_REGISTRATION,
            'note' => 'New car registration hold',
        ]);
        $this->assertSame(1, CarUnavailabilityPeriod::query()->where('car_id', $car->id)->count());
    }
}
