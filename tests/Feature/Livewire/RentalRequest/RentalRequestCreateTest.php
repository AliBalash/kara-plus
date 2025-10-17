<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestCreate;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\ContractStatus;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RentalRequestCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_creates_contract_and_initial_status(): void
    {
        Carbon::setTestNow('2025-01-01 09:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Audi',
            'model' => 'A4',
        ]);

        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'price_per_day_short' => 200,
            'price_per_day_mid' => 180,
            'price_per_day_long' => 150,
            'ldw_price_short' => 25,
            'ldw_price_mid' => 20,
            'ldw_price_long' => 18,
            'scdw_price_short' => 35,
            'scdw_price_mid' => 28,
            'scdw_price_long' => 25,
            'status' => 'available',
            'availability' => true,
        ]);

        $pickup = Carbon::now()->addDay();
        $return = Carbon::now()->addDays(4);

        $component = Mockery::mock(RentalRequestCreate::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount();

        $component->selectedBrand = $carModel->brand;
        $component->selectedModelId = $carModel->id;
        $component->selectedCarId = $car->id;
        $component->pickup_location = 'UAE/Dubai/Clock Tower/Main Branch';
        $component->return_location = 'UAE/Dubai/Clock Tower/Main Branch';
        $component->pickup_date = $pickup->format('Y-m-d\TH:i');
        $component->return_date = $return->format('Y-m-d\TH:i');
        $component->selected_services = ['child_seat'];
        $component->selected_insurance = 'basic_insurance';
        $component->kardo_required = true;
        $component->payment_on_delivery = true;
        $component->apply_discount = false;
        $component->driver_note = 'Collect payment from customer at pickup';
        $component->first_name = 'Sara';
        $component->last_name = 'Nazari';
        $component->email = 'sara.nazari@example.com';
        $component->phone = '+971500000001';
        $component->messenger_phone = '+971500000002';
        $component->address = 'Dubai Marina';
        $component->national_code = 'NC1234567';
        $component->passport_number = 'P1234567';
        $component->passport_expiry_date = Carbon::now()->addYear()->toDateString();
        $component->nationality = 'IR';
        $component->license_number = 'LIC-7788';

        $validated = [
            'selectedBrand' => $carModel->brand,
            'selectedModelId' => $carModel->id,
            'selectedCarId' => $car->id,
            'pickup_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'return_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'pickup_date' => $pickup->format('Y-m-d\TH:i'),
            'return_date' => $return->format('Y-m-d\TH:i'),
            'first_name' => 'Sara',
            'last_name' => 'Nazari',
            'email' => 'sara.nazari@example.com',
            'phone' => '+971500000001',
            'messenger_phone' => '+971500000002',
            'address' => 'Dubai Marina',
            'national_code' => 'NC1234567',
            'passport_number' => 'P1234567',
            'passport_expiry_date' => Carbon::now()->addYear()->toDateString(),
            'nationality' => 'IR',
            'license_number' => 'LIC-7788',
            'selected_insurance' => 'basic_insurance',
            'kardo_required' => true,
            'payment_on_delivery' => true,
            'apply_discount' => false,
            'custom_daily_rate' => null,
            'selected_services' => ['child_seat'],
            'driver_note' => 'Collect payment from customer at pickup',
        ];

        $component->shouldReceive('validate')->once()->andReturn($validated);

        $component->submit();

        $customer = Customer::where('phone', '+971500000001')->first();
        $this->assertNotNull($customer);

        $contract = Contract::where('customer_id', $customer->id)->first();
        $this->assertNotNull($contract);

        $this->assertEquals('pending', $contract->current_status);
        $this->assertEquals($car->id, $contract->car_id);
        $this->assertEquals('Collect payment from customer at pickup', $contract->meta['driver_note'] ?? null);

        $status = ContractStatus::where('contract_id', $contract->id)->latest('id')->first();
        $this->assertNotNull($status);
        $this->assertEquals('pending', $status->status);

        $charges = ContractCharges::where('contract_id', $contract->id)->get();
        $this->assertTrue($charges->pluck('title')->contains('base_rental'));
        $this->assertGreaterThanOrEqual(1, $charges->count());

        $expectedDays = $pickup->diffInDays($return, false);
        $expectedSubtotal = ($expectedDays * 200) + ($expectedDays * 20);
        $expectedTax = round($expectedSubtotal * 0.05);
        $this->assertEquals($expectedSubtotal + $expectedTax, (int) $contract->total_price);

        $this->assertEquals('Contract created successfully!', session('success'));
    }

    public function test_change_status_to_reserve_updates_contract_and_logs_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->status('pending')->create([
            'user_id' => $user->id,
        ]);

        $component = app(RentalRequestCreate::class);
        $component->mount();
        $component->changeStatusToReserve($contract->id);

        $contract->refresh();
        $this->assertEquals('reserved', $contract->current_status);
        $this->assertEquals('reserved', $contract->statuses()->latest('id')->first()->status);
        $this->assertEquals('Contract status changed to Reserved successfully.', session('success'));
    }

    public function test_assign_to_me_sets_user_and_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create(['user_id' => null]);

        $component = app(RentalRequestCreate::class);
        $component->mount();
        $component->assignToMe($contract->id);

        $contract->refresh();
        $this->assertEquals($user->id, $contract->user_id);
        $this->assertEquals('assigned', $contract->current_status);
        $this->assertEquals('Contract assigned to you successfully.', session('success'));
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
