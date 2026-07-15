<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\CarUnavailabilityPeriod;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractCarAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_active_contract_marks_car_unavailable(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state([
                'pickup_date' => Carbon::now()->subDay(),
                'return_date' => Carbon::now()->addDay(),
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_awaiting_return_contract_keeps_car_reserved(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(2),
                'return_date' => null,
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_upcoming_contract_marks_car_pre_reserved(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state(function () {
                $pickup = Carbon::now()->addDays(5);

                return [
                    'pickup_date' => $pickup,
                    'return_date' => $pickup->copy()->addDays(3),
                ];
            })
            ->create();

        $car->refresh();

        $this->assertEquals('pre_reserved', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_completing_contract_releases_car_when_no_other_active_contract_exists(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('delivery')
            ->create();

        $contract->changeStatus('complete', $user->id);

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_car_remains_reserved_if_other_active_contract_exists(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();
        $now = Carbon::now();

        $activeContract = Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->state([
                'pickup_date' => $now->copy()->subDay(),
                'return_date' => $now->copy()->addDay(),
            ])
            ->create();

        $completingContract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('delivery')
            ->state([
                'pickup_date' => $now->copy()->subDays(2),
                'return_date' => $now->copy()->addDays(2),
            ])
            ->create();

        $completingContract->changeStatus('complete', $user->id);

        $car->refresh();

        $this->assertEquals('reserved', $car->status);
        $this->assertFalse($car->availability);
        $this->assertEquals('reserved', $activeContract->fresh()->current_status);
    }

    public function test_payment_status_releases_car(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);
        $user = User::factory()->create();

        $contract = Contract::factory()
            ->for($user)
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(3),
                'return_date' => null,
            ])
            ->create();

        $contract->changeStatus('payment', $user->id);

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_does_not_override_sold_car_status(): void
    {
        $car = Car::factory()->sold()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state([
                'pickup_date' => Carbon::now()->subDay(),
                'return_date' => Carbon::now()->addDay(),
            ])
            ->create();

        $car->refresh();

        $this->assertEquals('sold', $car->status);
        $this->assertFalse($car->availability);
    }

    public function test_deleting_contract_releases_car_when_no_active_contract_remains(): void
    {
        $car = Car::factory()->create(['status' => 'reserved', 'availability' => false]);

        $contract = Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('reserved')
            ->create();

        $contract->delete();

        $car->refresh();

        $this->assertEquals('available', $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_operational_state_restores_ready_car_availability_when_no_reservation_exists(): void
    {
        $car = Car::factory()->create([
            'status' => Car::STATUS_AVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
            'availability' => false,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_operational_state_keeps_manual_unavailable_reason_without_reservation(): void
    {
        $car = Car::factory()->create([
            'status' => Car::STATUS_UNAVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_UNAVAILABLE,
            'manual_unavailability_reason' => Car::UNAVAILABILITY_REASON_MAINTENANCE,
            'availability' => true,
            'unavailability_reason' => null,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertFalse($car->availability);
        $this->assertSame(Car::UNAVAILABILITY_REASON_MAINTENANCE, $car->unavailability_reason);
    }

    public function test_sync_operational_state_releases_stale_pre_reserved_status_without_upcoming_reservation(): void
    {
        $car = Car::factory()->create([
            'status' => Car::STATUS_PRE_RESERVED,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
            'availability' => true,
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue($car->availability);
    }

    public function test_sync_operational_state_marks_overdue_contracts_as_need_action(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(3),
                'return_date' => Carbon::now()->subHour(),
            ])
            ->create();

        $car->refresh();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertFalse($car->availability);
        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $car->unavailability_reason);
    }

    public function test_need_action_can_also_expose_upcoming_booking_note(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('awaiting_return')
            ->state([
                'pickup_date' => Carbon::now()->subDays(3),
                'return_date' => Carbon::now()->subHour(),
            ])
            ->create();

        Contract::factory()
            ->for(User::factory())
            ->for(Customer::factory())
            ->for($car)
            ->status('pending')
            ->state([
                'pickup_date' => Carbon::now()->addDays(5),
                'return_date' => Carbon::now()->addDays(8),
            ])
            ->create();

        $car->refresh();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $car->unavailability_reason);
        $this->assertSame('Upcoming booking also exists.', $car->operationalStatusContextNote());
    }

    public function test_need_action_can_also_expose_active_hold_note(): void
    {
        $car = Car::factory()->available()->create();

        Contract::factory()
            ->for($car)
            ->status('awaiting_return')
            ->create([
                'pickup_date' => Carbon::now()->subDays(10),
                'return_date' => Carbon::now()->subDay(),
            ]);

        CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_MAINTENANCE,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDay()->toDateString(),
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $car->unavailability_reason);
        $this->assertStringContainsString('Active hold also exists: Maintenance', $car->operationalStatusContextNote());
    }

    public function test_active_scheduled_unavailability_marks_car_unavailable(): void
    {
        $car = Car::factory()->available()->create();

        CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'note' => 'Oil and filter service',
            'start_date' => Carbon::today()->subDay()->toDateString(),
            'end_date' => Carbon::today()->addDay()->toDateString(),
        ]);

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertFalse($car->availability);
        $this->assertSame(Car::UNAVAILABILITY_REASON_SERVICE_OIL, $car->unavailability_reason);
        $this->assertSame(
            Carbon::today()->subDay()->format('Y-m-d') . ' → ' . Carbon::today()->addDay()->format('Y-m-d'),
            $car->activeScheduledUnavailabilityWindowLabel()
        );
    }

    public function test_cancelled_scheduled_unavailability_does_not_block_car(): void
    {
        $car = Car::factory()->available()->create();

        $period = CarUnavailabilityPeriod::query()->create([
            'car_id' => $car->id,
            'reason' => Car::UNAVAILABILITY_REASON_SERVICE_OIL,
            'note' => 'Oil and filter service',
            'start_date' => Carbon::today()->subDay()->toDateString(),
            'end_date' => Carbon::today()->addDay()->toDateString(),
        ]);
        $period->cancel(null, 'Released early');

        $car->syncOperationalState();
        $car->refresh();

        $this->assertSame(Car::STATUS_AVAILABLE, $car->status);
        $this->assertTrue((bool) $car->availability);
        $this->assertNull($car->unavailability_reason);
        $this->assertNull($car->activeScheduledUnavailabilityPeriod());
        $this->assertSame('cancelled', $period->fresh()->state());
    }
}
