<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarDetail;
use App\Models\Car;
use App\Models\CarOption;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class CarDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_detail_shows_operational_information_and_edit_and_contract_links(): void
    {
        $this->actingAs(User::factory()->create());

        $car = Car::factory()->create([
            'plate_number' => 'KARA-30',
            'mileage' => 45200,
            'price_per_day_short' => 320,
            'notes' => 'Check the rear tyre',
        ]);

        CarOption::create(['car_id' => $car->id, 'option_key' => 'gear', 'option_value' => 'automatic']);
        Insurance::create(['car_id' => $car->id, 'expiry_date' => '2027-03-01', 'status' => 'done']);
        $contract = Contract::factory()->for($car)->for(Customer::factory())->status('reserved')->create();

        Livewire::test(CarDetail::class, ['carId' => $car->id])
            ->assertSee('KARA-30')
            ->assertSee('45,200 km')
            ->assertSee('320.00 AED')
            ->assertSee('Check the rear tyre')
            ->assertSee('Automatic')
            ->assertSee('2027-03-01')
            ->assertSee('Contract #'.$contract->id)
            ->assertSee(route('car.edit', $car->id))
            ->assertSee(route('rental-requests.details', $contract->id));

        $this->get(route('car.detail', $car->id))
            ->assertOk()
            ->assertSee('KARA-30')
            ->assertSee(route('car.edit', $car->id));
    }

    public function test_missing_car_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('car.detail', 999999))
            ->assertNotFound();
    }

    public function test_detail_handles_missing_optional_vehicle_records(): void
    {
        $this->actingAs(User::factory()->create());

        $car = Car::factory()->create([
            'plate_number' => 'SPARSE-30',
            'notes' => null,
            'damage_report' => null,
            'service_due_date' => null,
        ]);

        $this->get(route('car.detail', $car->id))
            ->assertOk()
            ->assertSee('SPARSE-30')
            ->assertSee('No active or upcoming booking')
            ->assertSee('No damage report recorded.');
    }
}
