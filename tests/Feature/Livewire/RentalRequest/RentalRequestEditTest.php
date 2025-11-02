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
            'price_per_day_short' => 350,
            'price_per_day_mid' => 320,
            'price_per_day_long' => 300,
            'ldw_price_short' => 40,
            'ldw_price_mid' => 35,
            'ldw_price_long' => 30,
            'scdw_price_short' => 55,
            'scdw_price_mid' => 50,
            'scdw_price_long' => 45,
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
                'total_price' => 1000,
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
        $component->custom_daily_rate = 300;
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
            'price_per_day_short' => 320,
            'price_per_day_mid' => 280,
            'price_per_day_long' => 240,
            'ldw_price_short' => 25,
            'ldw_price_mid' => 20,
            'ldw_price_long' => 18,
            'scdw_price_short' => 30,
            'scdw_price_mid' => 26,
            'scdw_price_long' => 24,
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
        $expectedSubtotal = ($expectedDays * 300) + ($expectedDays * 20) + 20 + ($expectedDays * 40) + $transferFees;
        $expectedTax = round($expectedSubtotal * 0.05);
        $this->assertEquals($expectedDays * 20, (float) ($chargesArray['child_seat'] ?? 0));
        $this->assertEquals($expectedSubtotal + $expectedTax, (float) $contract->total_price);
        $this->assertEquals('Contract Updated successfully!', session('info'));
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
            ->create();

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
