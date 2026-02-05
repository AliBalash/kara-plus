<?php

namespace Tests\Feature\Livewire\RentalRequest;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestEdit;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RentalRequestEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_updates_contract_customer_and_charges(): void
    {
        Carbon::setTestNow('2025-03-01 08:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'BMW',
            'model' => 'X5',
        ]);

        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'price_per_day_short' => 350.9,
            'price_per_day_mid' => 320.8,
            'price_per_day_long' => 300.7,
            'ldw_price_short' => 40.6,
            'ldw_price_mid' => 35.5,
            'ldw_price_long' => 30.4,
            'scdw_price_short' => 55.3,
            'scdw_price_mid' => 50.2,
            'scdw_price_long' => 45.1,
        ]);

        $customer = Customer::factory()->create([
            'email' => 'original@example.com',
            'phone' => '+971500000010',
            'messenger_phone' => '+971500000011',
            'nationality' => 'IR',
        ]);

        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for($car)
            ->status('pending')
            ->create([
                'pickup_location' => 'UAE/Dubai/Clock Tower/Main Branch',
                'return_location' => 'UAE/Dubai/Clock Tower/Main Branch',
                'pickup_date' => '2025-03-05 10:00:00',
                'return_date' => '2025-03-08 10:00:00',
                'notes' => 'Original notes',
                'total_price' => 1000.55,
                'kardo_required' => true,
                'payment_on_delivery' => true,
                'meta' => ['driver_note' => 'Initial driver note'],
            ]);

        $contract->charges()->create([
            'title' => 'child_seat',
            'amount' => 60,
            'type' => 'addon',
        ]);

        $newPickup = Carbon::now()->addDays(10);
        $newReturn = (clone $newPickup)->addDays(5);

        $component = Mockery::mock(RentalRequestEdit::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount($contract->id);

        $component->selected_services = ['additional_driver', 'child_seat'];
        $component->selected_insurance = 'ldw_insurance';
        $component->apply_discount = true;
        $component->custom_daily_rate = 300.65;
        $component->kardo_required = false;
        $component->pickup_location = 'UAE/Dubai/JBR';
        $component->return_location = 'UAE/Dubai/JBR';
        $component->pickup_date = $newPickup->format('Y-m-d\TH:i');
        $component->return_date = $newReturn->format('Y-m-d\TH:i');
        $component->notes = 'Updated notes';
        $component->first_name = 'Ali';
        $component->last_name = 'Karimi';
        $component->email = 'ali.karimi@example.com';
        $component->phone = '+971500000099';
        $component->messenger_phone = '+971500000098';
        $component->address = 'Updated Address';
        $component->national_code = $customer->national_code;
        $component->passport_number = $customer->passport_number;
        $component->passport_expiry_date = $customer->passport_expiry_date;
        $component->nationality = 'IR';
        $component->license_number = $customer->license_number;
        $component->driver_note = 'Collect balance in cash at pickup';

        $validated = [
            'plate_number' => $contract->car->plate_number,
            'status' => $contract->car->status,
            'availability' => $contract->car->availability,
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
            'service_due_date' => $contract->car->service_due_date,
            'damage_report' => 'Updated notes',
            'manufacturing_year' => $contract->car->manufacturing_year,
            'color' => 'Blue',
            'chassis_number' => $contract->car->chassis_number,
            'gps' => $contract->car->gps,
            'issue_date' => $contract->car->issue_date,
            'expiry_date' => $contract->car->expiry_date,
            'passing_date' => $contract->car->passing_date,
            'passing_valid_for_days' => $contract->car->passing_valid_for_days,
            'registration_valid_for_days' => $contract->car->registration_valid_for_days,
            'notes' => 'Updated notes',
            'passing_status' => $contract->car->passing_status,
            'registration_status' => $contract->car->registration_status,
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

        $contract->refresh();
        $customer->refresh();

        $this->assertEquals('Ali', $customer->first_name);
        $this->assertEquals('+971500000099', $customer->phone);
        $this->assertEquals('UAE/Dubai/JBR', $contract->pickup_location);
        $this->assertEquals('Updated notes', $contract->notes);
        $this->assertFalse((bool) $contract->kardo_required);
        $this->assertEquals('Collect balance in cash at pickup', $contract->meta['driver_note'] ?? null);

        $charges = $contract->charges()->pluck('amount', 'title');
        $this->assertArrayHasKey('base_rental', $charges->toArray());
        $chargesArray = $charges->toArray();
        $this->assertArrayHasKey('additional_driver', $chargesArray);
        $this->assertArrayHasKey('child_seat', $chargesArray);
        $this->assertArrayHasKey('tax', $charges->toArray());

        $expectedDays = $newPickup->diffInDays($newReturn, false);
        $transferFees = 50 * 2; // pickup and return location fees for JBR
        $expectedSubtotal = ($expectedDays * 300.65) + ($expectedDays * 20) + 20 + ($expectedDays * 40.6) + $transferFees;
        $expectedSubtotal = round($expectedSubtotal, 2);
        $expectedTax = round($expectedSubtotal * 0.05, 2);
        $this->assertEqualsWithDelta($expectedDays * 20, (float) ($chargesArray['child_seat'] ?? 0), 0.01);
        $this->assertEqualsWithDelta($expectedSubtotal + $expectedTax, (float) $contract->total_price, 0.01);
        $this->assertEquals('Contract Updated successfully!', session('info'));
    }

    public function test_edit_component_keeps_stored_daily_rate_when_car_price_changes(): void
    {
        Carbon::setTestNow('2025-04-01 08:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Audi',
            'model' => 'Q5',
        ]);

        $car = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'price_per_day_short' => 150,
            'price_per_day_mid' => 140,
            'price_per_day_long' => 130,
        ]);

        $customer = Customer::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for($customer)
            ->for($car)
            ->status('pending')
            ->create([
                'pickup_date' => now()->addDay(),
                'return_date' => now()->addDays(4),
                'used_daily_rate' => 210.25,
                'custom_daily_rate_enabled' => false,
                'discount_note' => null,
            ]);

        $car->update([
            'price_per_day_short' => 999,
            'price_per_day_mid' => 999,
            'price_per_day_long' => 999,
        ]);

        $component = Mockery::mock(RentalRequestEdit::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount($contract->id);

        $this->assertFalse($component->apply_discount);
        $this->assertEqualsWithDelta(210.25, $component->dailyRate, 0.01);
        $this->assertEqualsWithDelta(210.25, (float) $component->custom_daily_rate, 0.01);
    }

    public function test_change_status_to_reserve_requires_same_user_and_updates_car(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create(['status' => 'available']);
        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('assigned')
            ->create([
                'pickup_date' => now()->subDay(),
                'return_date' => now()->addDay(),
            ]);

        $component = app(RentalRequestEdit::class);
        $component->mount($contract->id);
        $component->changeStatusToReserve($contract->id);

        $contract->refresh();
        $this->assertEquals('reserved', $contract->current_status);
        $this->assertEquals('reserved', $contract->statuses()->latest('id')->first()->status);
        $this->assertEquals('reserved', $contract->car->fresh()->status);
        $this->assertEquals('Status changed to Reserved successfully.', session('success'));
    }

    public function test_assign_to_me_marks_contract_assigned(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()
            ->for(Customer::factory())
            ->for(Car::factory())
            ->create(['user_id' => null]);

        $component = app(RentalRequestEdit::class);
        $component->mount($contract->id);
        $component->assignToMe($contract->id);

        $contract->refresh();
        $this->assertEquals($user->id, $contract->user_id);
        $this->assertEquals('assigned', $contract->current_status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
