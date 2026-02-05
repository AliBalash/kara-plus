<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\EditCarForm;
use App\Models\Car;
use App\Models\CarOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EditCarFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_updates_car_and_related_options(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'price_per_day_short' => 320.4,
            'price_per_day_mid' => 280.3,
            'price_per_day_long' => 240.2,
            'ldw_price_short' => 25.1,
            'ldw_price_mid' => 20.2,
            'ldw_price_long' => 18.3,
            'scdw_price_short' => 30.4,
            'scdw_price_mid' => 26.5,
            'scdw_price_long' => 24.6,
            'color' => 'Silver',
            'notes' => 'Initial note',
            'passing_status' => 'done',
            'registration_status' => 'done',
        ]);

        $car->carModel->update(['is_featured' => false]);

        CarOption::create([
            'car_id' => $car->id,
            'option_key' => 'gear',
            'option_value' => 'automatic',
        ]);

        $component = Mockery::mock(EditCarForm::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount($car->id);

        $component->is_featured = true;

        $validated = [
            'plate_number' => $car->plate_number,
            'status' => 'available',
            'availability' => true,
            'mileage' => 1500,
            'price_per_day_short' => 320.4,
            'price_per_day_mid' => 280.3,
            'price_per_day_long' => 240.2,
            'ldw_price_short' => 25.1,
            'ldw_price_mid' => 20.2,
            'ldw_price_long' => 18.3,
            'scdw_price_short' => 30.4,
            'scdw_price_mid' => 26.5,
            'scdw_price_long' => 24.6,
            'service_due_date' => $car->service_due_date,
            'damage_report' => 'Updated note',
            'manufacturing_year' => $car->manufacturing_year,
            'color' => 'Blue',
            'chassis_number' => $car->chassis_number,
            'gps' => $car->gps,
            'ownership_type' => $car->ownershipType(),
            'issue_date' => $car->issue_date,
            'expiry_date' => $car->expiry_date,
            'passing_date' => $car->passing_date,
            'passing_valid_for_days' => $car->passing_valid_for_days,
            'registration_valid_for_days' => $car->registration_valid_for_days,
            'notes' => 'Updated note',
            'passing_status' => $car->passing_status,
            'registration_status' => $car->registration_status,
            'car_options' => [
                'gear' => 'manual',
                'seats' => 2,
                'doors' => 2,
                'luggage' => 1,
                'min_days' => 3,
                'fuel_type' => 'diesel',
                'unlimited_km' => false,
                'base_insurance' => true,
            ],
        ];

        $component->shouldReceive('validate')->once()->andReturn($validated);

        $component->submit();

        $car->refresh();
        $this->assertEquals('Blue', $car->color);
        $this->assertEquals('Updated note', $car->notes);
        $this->assertEquals(1500, $car->mileage);
        $this->assertTrue($car->carModel->fresh()->is_featured);

        $options = $car->options()->pluck('option_value', 'option_key');
        $this->assertEquals('manual', $options['gear']);
        $this->assertEquals('2', $options['seats']);
        $this->assertEquals('0', $options['unlimited_km']);
        $this->assertEquals('1', $options['base_insurance']);
        $this->assertEquals('Car updated successfully!', session('message'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
