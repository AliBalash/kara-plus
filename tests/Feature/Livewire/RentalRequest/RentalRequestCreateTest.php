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
use Illuminate\Validation\ValidationException;
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
            'price_per_day_short' => 200.45,
            'price_per_day_mid' => 180.35,
            'price_per_day_long' => 150.25,
            'ldw_price_short' => 25.15,
            'ldw_price_mid' => 20.05,
            'ldw_price_long' => 18.95,
            'scdw_price_short' => 35.55,
            'scdw_price_mid' => 28.45,
            'scdw_price_long' => 25.35,
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
        $expectedSubtotal = $expectedDays * 200.45;
        $expectedSubtotal = round($expectedSubtotal, 2);
        $expectedTax = round($expectedSubtotal * 0.05, 2);
        $this->assertEqualsWithDelta($expectedSubtotal + $expectedTax, (float) $contract->total_price, 0.01);

        $this->assertEquals('Contract created successfully!', session('success'));
    }

    public function test_phone_lookup_suggests_existing_customer_and_requires_explicit_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create([
            'first_name' => 'Niloofar',
            'last_name' => 'Rahmani',
            'email' => 'niloofar@example.com',
            'phone' => '+971500000123',
            'messenger_phone' => '+971500000124',
            'address' => 'Dubai Hills',
            'birth_date' => '1995-06-12',
            'national_code' => '1234567890',
            'passport_number' => 'P998877',
            'passport_expiry_date' => '2028-05-10',
            'nationality' => 'IR',
            'license_number' => 'LIC-8899',
            'gender' => 'female',
        ]);

        Contract::factory()->for($customer)->status('pending')->create();

        $component = app(RentalRequestCreate::class);
        $component->mount();

        $component->phone = '+971500000';
        $component->updated('phone');

        $suggestedCustomerIds = collect($component->customerPhoneSuggestions)->pluck('id');

        $this->assertTrue($suggestedCustomerIds->contains($customer->id));
        $this->assertNull($component->selectedExistingCustomerId);

        $component->phone = '+971500000123';
        $component->updated('phone');

        $this->assertNull($component->selectedExistingCustomerId);
        $this->assertSame('+971500000123', $component->phone);
        $this->assertTrue(collect($component->customerPhoneSuggestions)->pluck('id')->contains($customer->id));

        $component->selectExistingCustomer($customer->id);

        $this->assertSame($customer->id, $component->selectedExistingCustomerId);
        $this->assertSame('Niloofar', $component->first_name);
        $this->assertSame('Rahmani', $component->last_name);
        $this->assertSame('+971500000123', $component->phone);
        $this->assertSame('+971500000124', $component->messenger_phone);
        $this->assertSame('Dubai Hills', $component->address);
        $this->assertSame('1995-06-12', $component->birth_date);
        $this->assertSame('1234567890', $component->national_code);
        $this->assertSame('P998877', $component->passport_number);
        $this->assertSame('2028-05-10', $component->passport_expiry_date);
        $this->assertSame('IR', $component->nationality);
        $this->assertSame('LIC-8899', $component->license_number);
        $this->assertSame([], $component->customerPhoneSuggestions);
    }

    public function test_submit_reuses_existing_customer_when_phone_matches_saved_profile(): void
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
            'price_per_day_short' => 200.45,
            'price_per_day_mid' => 180.35,
            'price_per_day_long' => 150.25,
            'ldw_price_short' => 25.15,
            'ldw_price_mid' => 20.05,
            'ldw_price_long' => 18.95,
            'scdw_price_short' => 35.55,
            'scdw_price_mid' => 28.45,
            'scdw_price_long' => 25.35,
            'status' => 'available',
            'availability' => true,
        ]);

        $customer = Customer::factory()->create([
            'first_name' => 'Sara',
            'last_name' => 'Nazari',
            'email' => 'sara.nazari@example.com',
            'phone' => '+971500000001',
            'messenger_phone' => '+971500000002',
            'address' => 'Old Address',
            'national_code' => 'NC1234567',
            'passport_number' => 'P1234567',
            'passport_expiry_date' => Carbon::now()->addYear()->toDateString(),
            'nationality' => 'IR',
            'license_number' => 'LIC-7788',
            'gender' => 'female',
        ]);

        $pickup = Carbon::now()->addDay();
        $return = Carbon::now()->addDays(4);

        $component = Mockery::mock(RentalRequestCreate::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();
        $component->mount();

        $component->selectExistingCustomer($customer->id);
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

        $this->assertEquals(1, Customer::where('email', 'sara.nazari@example.com')->count());

        $customer->refresh();
        $this->assertEquals('Dubai Marina', $customer->address);

        $contract = Contract::where('customer_id', $customer->id)->first();
        $this->assertNotNull($contract);
        $this->assertEquals('pending', $contract->current_status);
        $this->assertEquals('Contract created successfully!', session('success'));
    }

    public function test_submit_requires_loading_existing_customer_when_phone_matches_saved_profile(): void
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
            'status' => 'available',
            'availability' => true,
        ]);

        Customer::factory()->create([
            'first_name' => 'Saved',
            'last_name' => 'Customer',
            'email' => 'saved@example.com',
            'phone' => '+971500000001',
            'messenger_phone' => '+971500000002',
            'nationality' => 'IR',
        ]);

        $pickup = Carbon::now()->addDay();
        $return = Carbon::now()->addDays(2);

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
        $component->first_name = 'New';
        $component->last_name = 'Customer';
        $component->email = 'new@example.com';
        $component->phone = '+971500000001';
        $component->messenger_phone = '+971500000009';
        $component->nationality = 'IR';

        $component->shouldReceive('validate')->once()->andReturn([
            'selectedBrand' => $carModel->brand,
            'selectedModelId' => $carModel->id,
            'selectedCarId' => $car->id,
            'pickup_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'return_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'pickup_date' => $pickup->format('Y-m-d\TH:i'),
            'return_date' => $return->format('Y-m-d\TH:i'),
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'new@example.com',
            'phone' => '+971500000001',
            'messenger_phone' => '+971500000009',
            'address' => null,
            'national_code' => null,
            'passport_number' => null,
            'passport_expiry_date' => null,
            'nationality' => 'IR',
            'license_number' => null,
            'selected_insurance' => 'basic_insurance',
            'kardo_required' => true,
            'payment_on_delivery' => true,
            'apply_discount' => false,
            'custom_daily_rate' => null,
            'selected_services' => [],
            'driver_note' => null,
        ]);

        try {
            $component->submit();
            $this->fail('Expected phone-based customer replacement validation to be triggered.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This phone number already belongs to an existing customer. Please load that customer before saving the contract.',
                $exception->errors()['phone'][0] ?? null
            );
        }

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_submit_does_not_reuse_customer_when_only_national_code_matches(): void
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
            'price_per_day_short' => 200.45,
            'price_per_day_mid' => 180.35,
            'price_per_day_long' => 150.25,
            'ldw_price_short' => 25.15,
            'ldw_price_mid' => 20.05,
            'ldw_price_long' => 18.95,
            'scdw_price_short' => 35.55,
            'scdw_price_mid' => 28.45,
            'scdw_price_long' => 25.35,
            'status' => 'available',
            'availability' => true,
        ]);

        $existingCustomer = Customer::factory()->create([
            'first_name' => 'Existing',
            'last_name' => 'Customer',
            'email' => 'existing@example.com',
            'phone' => '+971500000001',
            'messenger_phone' => '+971500000002',
            'national_code' => 'NC1234567',
            'nationality' => 'IR',
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
        $component->first_name = 'New';
        $component->last_name = 'Customer';
        $component->email = 'new@example.com';
        $component->phone = '+971500000010';
        $component->messenger_phone = '+971500000011';
        $component->address = 'Dubai Marina';
        $component->national_code = 'NC1234567';
        $component->passport_expiry_date = Carbon::now()->addYear()->toDateString();
        $component->nationality = 'IR';

        $validated = [
            'selectedBrand' => $carModel->brand,
            'selectedModelId' => $carModel->id,
            'selectedCarId' => $car->id,
            'pickup_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'return_location' => 'UAE/Dubai/Clock Tower/Main Branch',
            'pickup_date' => $pickup->format('Y-m-d\TH:i'),
            'return_date' => $return->format('Y-m-d\TH:i'),
            'first_name' => 'New',
            'last_name' => 'Customer',
            'email' => 'new@example.com',
            'phone' => '+971500000010',
            'messenger_phone' => '+971500000011',
            'address' => 'Dubai Marina',
            'national_code' => 'NC1234567',
            'passport_number' => null,
            'passport_expiry_date' => Carbon::now()->addYear()->toDateString(),
            'nationality' => 'IR',
            'license_number' => null,
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

        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseHas('customers', [
            'id' => $existingCustomer->id,
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'new@example.com',
            'first_name' => 'New',
            'national_code' => 'NC1234567',
        ]);
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

    public function test_load_cars_excludes_sold_cars_from_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carModel = CarModel::factory()->create([
            'brand' => 'Kia',
            'model' => 'Sportage',
        ]);

        $availableCar = Car::factory()->create([
            'car_model_id' => $carModel->id,
            'status' => 'available',
            'availability' => true,
        ]);

        $soldCar = Car::factory()->sold()->create([
            'car_model_id' => $carModel->id,
        ]);

        $component = app(RentalRequestCreate::class);
        $component->mount();
        $component->selectedModelId = $carModel->id;
        $component->updatedSelectedModelId();

        $carIds = collect($component->carsForModel)->pluck('id')->all();

        $this->assertContains($availableCar->id, $carIds);
        $this->assertNotContains($soldCar->id, $carIds);
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
