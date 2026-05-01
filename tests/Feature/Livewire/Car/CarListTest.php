<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarList;
use App\Models\Car;
use App\Models\CarOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('car_pics');
    }

    public function test_deletecar_removes_image_record_options_and_file(): void
    {
        $car = Car::factory()->create();

        CarOption::create([
            'car_id' => $car->id,
            'option_key' => 'gear',
            'option_value' => 'automatic',
        ]);

        $image = $car->image()->create([
            'file_path' => 'car-pics/',
            'file_name' => 'car-list-delete.webp',
        ]);

        Storage::disk('car_pics')->put('car-list-delete.webp', 'car-image');

        app(CarList::class)->deletecar($car->id);

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('car_options', ['car_id' => $car->id]);
        Storage::disk('car_pics')->assertMissing('car-list-delete.webp');
    }

    public function test_available_status_with_false_availability_is_shown_as_unavailable(): void
    {
        Car::factory()->create([
            'plate_number' => '51004-V',
            'status' => 'available',
            'availability' => false,
        ]);

        $car = Car::where('plate_number', '51004-V')->firstOrFail();

        $this->assertSame('unavailable', $car->operationalStatus());
        $this->assertSame('Unavailable', $car->operationalStatusLabel());
    }

    public function test_status_filter_uses_operational_availability_not_only_status_column(): void
    {
        $unavailable = Car::factory()->create([
            'status' => 'available',
            'availability' => false,
        ]);

        $available = Car::factory()->available()->create();

        $this->assertSame(
            [$available->id],
            Car::query()->byOperationalStatus('available')->pluck('id')->all()
        );

        $this->assertSame(
            [$unavailable->id],
            Car::query()->byOperationalStatus('unavailable')->pluck('id')->all()
        );
    }
}
