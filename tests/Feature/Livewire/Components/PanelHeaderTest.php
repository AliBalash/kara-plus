<?php

namespace Tests\Feature\Livewire\Components;

use App\Livewire\Components\Panel\Header;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            'status' => 'available',
            'availability' => false,
        ]);

        Livewire::test(Header::class)
            ->set('query', '51004')
            ->assertSee('Unavailable')
            ->assertDontSee('Upcoming booking');
    }
}
