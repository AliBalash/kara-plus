<?php

namespace Tests\Feature\Livewire\Components;

use App\Livewire\Components\Panel\Header;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_vehicle_search_renders_request_cards_with_contract_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'BMW',
            'model' => 'X3',
        ]);

        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'plate_number' => 'DXB-90812',
            'ownership_type' => 'company',
            'status' => 'available',
            'availability' => true,
        ]);

        $customer = Customer::factory()->create([
            'first_name' => 'Mina',
            'last_name' => 'Rahimi',
        ]);

        $contract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->status('reserved')
            ->create([
                'pickup_date' => now()->addDay(),
                'return_date' => now()->addDays(3),
            ]);

        $component = app(Header::class);
        $component->query = '90812';
        $component->updatedQuery();

        $this->assertCount(1, $component->cars);
        $this->assertTrue($component->cars->first()->is($car));
        $this->assertTrue($component->cars->first()->contracts->contains($contract));
    }

    public function test_quick_vehicle_search_uses_operational_status_label(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        Car::factory()->create([
            'car_model_id' => $carModel->id,
            'plate_number' => 'SYNC-51004',
            'ownership_type' => 'company',
            'status' => 'available',
            'availability' => false,
        ]);

        $component = app(Header::class);
        $component->query = '51004';
        $component->updatedQuery();

        $this->assertCount(1, $component->cars);
        $this->assertSame('Unavailable', $component->cars->first()->operationalStatusLabel());
    }

    public function test_quick_vehicle_search_only_returns_our_fleet_cars(): void
    {
        $this->actingAs(User::factory()->create());

        $companyCar = Car::factory()->for(CarModel::factory()->state([
            'brand' => 'Mercedes-Benz',
            'model' => 'C-Class',
        ]))->create([
            'plate_number' => 'BENZ-OUR-1',
            'ownership_type' => 'company',
        ]);

        $partnerCar = Car::factory()->for(CarModel::factory()->state([
            'brand' => 'Mercedes-Benz',
            'model' => 'C-Class',
        ]))->create([
            'plate_number' => 'BENZ-PARTNER-1',
            'ownership_type' => 'golden_key',
        ]);

        $component = app(Header::class);
        $component->query = 'benz';
        $component->updatedQuery();

        $this->assertCount(1, $component->cars);
        $this->assertTrue($component->cars->first()->is($companyCar));
        $this->assertFalse($component->cars->contains($partnerCar));
    }
}
