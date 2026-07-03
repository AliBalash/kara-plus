<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarList;
use App\Models\Car;
use App\Models\CarOption;
use App\Models\Contract;
use Carbon\Carbon;
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

    public function test_reservation_selection_scope_blocks_unavailable_and_maintenance_cars(): void
    {
        $unavailable = Car::factory()->create([
            'status' => 'available',
            'availability' => false,
        ]);

        $maintenance = Car::factory()->create([
            'status' => 'under_maintenance',
            'availability' => false,
        ]);

        $reserved = Car::factory()->create([
            'status' => 'reserved',
            'availability' => false,
        ]);

        $preReserved = Car::factory()->create([
            'status' => 'pre_reserved',
            'availability' => true,
        ]);

        $this->assertFalse($unavailable->isSelectableForReservation());
        $this->assertFalse($maintenance->isSelectableForReservation());
        $this->assertTrue($reserved->isSelectableForReservation());
        $this->assertTrue($preReserved->isSelectableForReservation());

        $this->assertSame(
            [$reserved->id, $preReserved->id],
            Car::query()->reservableForSelection()->orderBy('id')->pluck('id')->all()
        );
    }

    public function test_available_filter_with_date_range_keeps_cars_without_conflicting_bookings(): void
    {
        Car::factory()->available()->create([
            'plate_number' => 'FREE-1001',
        ]);

        $conflictingCar = Car::factory()->available()->create([
            'plate_number' => 'BUSY-1002',
        ]);

        Contract::factory()->for($conflictingCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-15 10:00:00'),
            'return_date' => Carbon::parse('2026-07-18 10:00:00'),
        ]);

        $component = app(CarList::class);
        $component->statusFilter = 'available';
        $component->pickupFrom = '2026-07-13';
        $component->pickupTo = '2026-07-19';

        $query = Car::query()
            ->when($component->statusFilter, fn ($builder) => $builder->byOperationalStatus($component->statusFilter));

        $applyDateFilters = new \ReflectionMethod($component, 'applyDateFilters');
        $applyDateFilters->setAccessible(true);
        $applyDateFilters->invoke($component, $query);

        $cars = $query->orderBy('plate_number')->pluck('plate_number')->all();

        $this->assertSame(['FREE-1001'], $cars);
    }
}
