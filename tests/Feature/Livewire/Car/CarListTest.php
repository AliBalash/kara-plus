<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarList;
use App\Models\Car;
use App\Models\CarOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

        Livewire::test(CarList::class)
            ->call('deletecar', $car->id);

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('car_options', ['car_id' => $car->id]);
        Storage::disk('car_pics')->assertMissing('car-list-delete.webp');
    }
}
