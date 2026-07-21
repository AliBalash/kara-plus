<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\EditCarForm;
use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use App\Models\CarOption;
use App\Models\CarStatusPeriod;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class EditCarFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_updates_car_and_related_options(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'price_per_day_short' => 320.4,
            'price_per_day_mid' => 280.3,
            'price_per_day_long' => 240.2,
            'ldw_price_short' => 25.1,
            'ldw_price_mid' => 20.2,
            'ldw_price_long' => 18.3,
            'scdw_price_short' => 30.4,
            'scdw_price_mid' => 26.5,
            'scdw_price_long' => 24.6,
            'color' => 'Silver',
            'notes' => 'Initial note',
            'passing_status' => 'done',
            'registration_status' => 'done',
        ]);

        $car->carModel->update(['is_featured' => false]);

        CarOption::create([
            'car_id' => $car->id,
            'option_key' => 'gear',
            'option_value' => 'automatic',
        ]);

        $component = app(EditCarForm::class);
        $component->mount($car->id);

        $component->is_featured = true;

        $validated = [
            'plate_number' => $car->plate_number,
            'status' => 'available',
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
            'service_due_date' => $car->service_due_date,
            'damage_report' => 'Updated note',
            'manufacturing_year' => $car->manufacturing_year,
            'color' => 'Blue',
            'chassis_number' => $car->chassis_number,
            'gps' => $car->gps,
            'ownership_type' => 'safe_drive',
            'issue_date' => $car->issue_date,
            'expiry_date' => $car->expiry_date,
            'passing_date' => $car->passing_date,
            'passing_valid_for_days' => $car->passing_valid_for_days,
            'registration_valid_for_days' => $car->registration_valid_for_days,
            'notes' => 'Updated note',
            'passing_status' => $car->passing_status,
            'registration_status' => $car->registration_status,
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

        foreach ($validated as $field => $value) {
            $component->{$field} = $value;
        }

        $component->submit();

        $car->refresh();
        $this->assertEquals('Blue', $car->color);
        $this->assertEquals('Updated note', $car->notes);
        $this->assertEquals(1500, $car->mileage);
        $this->assertEquals('safe_drive', $car->ownership_type);
        $this->assertFalse($car->is_company_car);
        $this->assertTrue($car->carModel->fresh()->is_featured);

        $options = $car->options()->pluck('option_value', 'option_key');
        $this->assertEquals('manual', $options['gear']);
        $this->assertEquals('2', $options['seats']);
        $this->assertEquals('0', $options['unlimited_km']);
        $this->assertEquals('1', $options['base_insurance']);
        $this->assertEquals('Car updated successfully!', session('message'));
    }

    public function test_cannot_mark_car_as_sold_while_it_has_reserving_contracts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'status' => 'available',
            'availability' => true,
        ]);

        Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->create();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->status = 'sold';
        $component->availability = false;

        try {
            $component->submit();
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->validator->errors()->toArray());
        }

        $this->assertNotEquals('sold', $car->fresh()->status);
    }

    public function test_can_mark_car_as_sold_when_no_reserving_contract_exists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'status' => 'available',
            'availability' => true,
        ]);

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->status = 'sold';
        $component->availability = false;
        $component->submit();

        $car->refresh();
        $this->assertEquals('sold', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_edit_form_shows_legacy_manual_unavailable_as_unavailable_control_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'status' => Car::STATUS_UNAVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_UNAVAILABLE,
            'manual_unavailability_reason' => Car::UNAVAILABILITY_REASON_MAINTENANCE,
            'availability' => false,
            'unavailability_reason' => Car::UNAVAILABILITY_REASON_MAINTENANCE,
        ]);

        $component = app(EditCarForm::class);
        $component->mount($car->id);

        $this->assertSame(Car::MANUAL_STATUS_UNAVAILABLE, $component->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_MAINTENANCE, $component->hold_reason);
        $this->assertNull($component->hold_start_date);
        $this->assertNull($component->hold_end_date);
    }

    public function test_edit_form_shows_final_status_and_hides_manual_availability_controls(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'status' => Car::STATUS_AVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
            'availability' => false,
        ]);

        $component = app(EditCarForm::class);
        $component->mount($car->id);

        $this->assertSame('Available', $component->effectiveStatusLabel);
        $this->assertTrue($component->availability);
    }

    public function test_edit_form_no_longer_creates_unavailable_window_records(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->hold_reason = Car::UNAVAILABILITY_REASON_ACCIDENT;
        $component->hold_note = 'Workshop inspection';

        $component->saveUnavailableWindow();

        $this->assertArrayHasKey('status', $component->getErrorBag()->toArray());
        $this->assertSame(0, CarUnavailabilityPeriod::query()->where('car_id', $car->id)->count());

        $car->refresh();
        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue((bool) $car->availability);
        $this->assertNull($car->unavailability_reason);
    }

    public function test_submit_with_unavailable_status_creates_open_status_period(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->status = Car::MANUAL_STATUS_UNAVAILABLE;
        $component->hold_reason = Car::UNAVAILABILITY_REASON_REGISTRATION;
        $component->hold_note = 'Registration renewal';

        $component->submit();

        $this->assertDatabaseMissing('car_unavailability_periods', [
            'car_id' => $car->id,
        ]);

        $car->refresh();
        $this->assertSame(Car::MANUAL_STATUS_UNAVAILABLE, $car->manual_status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_REGISTRATION, $car->manual_unavailability_reason);
        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_REGISTRATION, $car->unavailability_reason);
        $this->assertSame(Car::MANUAL_STATUS_UNAVAILABLE, $component->status);

        $this->assertDatabaseHas('car_status_periods', [
            'car_id' => $car->id,
            'status' => Car::STATUS_UNAVAILABLE,
            'reason' => Car::UNAVAILABILITY_REASON_REGISTRATION,
            'source' => CarStatusPeriod::SOURCE_MANUAL,
            'note' => 'Registration renewal',
            'ended_at' => null,
        ]);
    }

    public function test_edit_form_accepts_change_plate_unavailable_reason(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->status = Car::MANUAL_STATUS_UNAVAILABLE;
        $component->hold_reason = Car::UNAVAILABILITY_REASON_CHANGE_PLATE;
        $component->hold_note = 'Plate change in progress';

        $component->submit();

        $this->assertDatabaseHas('car_status_periods', [
            'car_id' => $car->id,
            'status' => Car::STATUS_UNAVAILABLE,
            'reason' => Car::UNAVAILABILITY_REASON_CHANGE_PLATE,
            'source' => CarStatusPeriod::SOURCE_MANUAL,
        ]);
        $this->assertSame(Car::UNAVAILABILITY_REASON_CHANGE_PLATE, $car->fresh()->unavailability_reason);
    }

    public function test_submit_with_available_status_does_not_edit_legacy_unavailable_window(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();
        $period = CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_ACCIDENT,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $car->syncOperationalState();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $this->assertSame(Car::MANUAL_STATUS_AVAILABLE, $component->status);

        $component->status = Car::MANUAL_STATUS_AVAILABLE;
        $component->submit();

        $this->assertDatabaseHas('car_unavailability_periods', ['id' => $period->id]);
        $this->assertNull($period->fresh()->cancelled_at);
        $car->refresh();
        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue((bool) $car->availability);
        $this->assertSame(Car::MANUAL_STATUS_AVAILABLE, $component->status);
    }

    public function test_edit_form_keeps_legacy_manual_unavailable_without_default_end_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->create([
            'status' => Car::STATUS_UNAVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_UNAVAILABLE,
            'manual_unavailability_reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'availability' => false,
            'unavailability_reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'notes' => 'Legacy oil service',
        ]);

        $component = app(EditCarForm::class);
        $component->mount($car->id);

        $this->assertNull($component->unavailability_period_id);
        $this->assertSame(Car::UNAVAILABILITY_REASON_SERVICE_OIL, $component->hold_reason);
        $this->assertNull($component->hold_start_date);
        $this->assertNull($component->hold_end_date);

        $component->submit();

        $car->refresh();
        $this->assertSame(Car::MANUAL_STATUS_UNAVAILABLE, $car->manual_status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_SERVICE_OIL, $car->manual_unavailability_reason);
        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_SERVICE_OIL, $car->unavailability_reason);
    }

    public function test_confirming_available_resolves_expired_window_and_releases_car(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();
        $period = CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'start_date' => Carbon::today()->subDays(2)->toDateString(),
            'end_date' => Carbon::today()->subDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $car->syncOperationalState();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $this->assertSame(Car::STATUS_AVAILABLE, $car->fresh()->status);
        $this->assertNull($car->fresh()->unavailability_reason);

        $component->status = Car::MANUAL_STATUS_AVAILABLE;
        $component->submit();

        $period->refresh();
        $car->refresh();
        $this->assertNotNull($period->resolved_at);
        $this->assertSame($user->id, $period->resolved_by);
        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue((bool) $car->availability);
        $this->assertNull($car->unavailability_reason);
    }

    public function test_manual_unavailable_status_resolves_older_expired_legacy_window(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $car = Car::factory()->available()->create();
        $expiredPeriod = CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'start_date' => Carbon::today()->subDays(2)->toDateString(),
            'end_date' => Carbon::today()->subDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $car->syncOperationalState();

        $component = app(EditCarForm::class);
        $component->mount($car->id);
        $component->status = Car::MANUAL_STATUS_UNAVAILABLE;
        $component->hold_reason = Car::UNAVAILABILITY_REASON_CHANGE_PLATE;
        $component->hold_note = 'Further inspection required';
        $component->submit();

        $this->assertNotNull($expiredPeriod->fresh()->resolved_at);
        $car->refresh();
        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_CHANGE_PLATE, $car->unavailability_reason);
    }
}
