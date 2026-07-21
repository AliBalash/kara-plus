<?php

namespace Tests\Feature\Models;

use App\Models\Car;
use App\Models\CarStatusPeriod;
use App\Models\Contract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarStatusPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_and_closes_status_periods_when_status_changes(): void
    {
        $start = Carbon::parse('2026-07-21 09:00:00');
        $change = Carbon::parse('2026-07-21 11:30:00');

        $car = Car::factory()->available()->create();

        $car->syncOperationalState(
            now: $start,
            source: CarStatusPeriod::SOURCE_MANUAL,
            note: 'Initial available status.'
        );

        $car->forceFill([
            'manual_status' => Car::MANUAL_STATUS_UNAVAILABLE,
            'manual_unavailability_reason' => Car::UNAVAILABILITY_REASON_ACCIDENT,
        ])->saveQuietly();

        $car->syncOperationalState(
            now: $change,
            source: CarStatusPeriod::SOURCE_MANUAL,
            note: 'Accident reported.'
        );

        $periods = CarStatusPeriod::query()
            ->where('car_id', $car->id)
            ->orderBy('started_at')
            ->get();

        $this->assertCount(2, $periods);
        $this->assertSame(Car::STATUS_AVAILABLE, $periods[0]->status);
        $this->assertTrue($periods[0]->ended_at->equalTo($change));
        $this->assertSame(Car::STATUS_UNAVAILABLE, $periods[1]->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_ACCIDENT, $periods[1]->reason);
        $this->assertNull($periods[1]->ended_at);
    }

    public function test_need_action_status_is_recorded_as_automatic_period(): void
    {
        $now = Carbon::parse('2026-07-21 12:00:00');
        $car = Car::factory()->available()->create();

        Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-18 10:00:00'),
            'return_date' => Carbon::parse('2026-07-20 10:00:00'),
        ]);

        $car->syncOperationalState($now);

        $period = CarStatusPeriod::query()
            ->where('car_id', $car->id)
            ->open()
            ->first();

        $this->assertNotNull($period);
        $this->assertSame(Car::STATUS_UNAVAILABLE, $period->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $period->reason);
        $this->assertSame(CarStatusPeriod::SOURCE_AUTOMATIC, $period->source);
    }

    public function test_actor_name_uses_user_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Kiana',
            'last_name' => 'Kabganian',
        ]);

        $period = CarStatusPeriod::query()->create([
            'car_id' => Car::factory()->available()->create()->id,
            'status' => Car::STATUS_UNAVAILABLE,
            'availability' => false,
            'reason' => Car::UNAVAILABILITY_REASON_ACCIDENT,
            'source' => CarStatusPeriod::SOURCE_MANUAL,
            'started_at' => now(),
            'started_by' => $user->id,
        ]);

        $period->load('starter');

        $this->assertSame('Kiana Kabganian', $period->actorName());
        $this->assertSame('KK', $period->actorInitials());
    }

    public function test_confirming_existing_migration_period_can_attach_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Marziyeh',
            'last_name' => 'Kabganian',
        ]);

        $car = Car::factory()->available()->create();

        $car->syncOperationalState(
            source: CarStatusPeriod::SOURCE_MIGRATION,
            note: 'Initial backfill.'
        );

        $car->syncOperationalState(
            source: CarStatusPeriod::SOURCE_MANUAL,
            actorId: $user->id,
            note: 'Confirmed by fleet team.',
            triggerType: 'car_edit',
            triggerId: $car->id
        );

        $period = CarStatusPeriod::query()->where('car_id', $car->id)->open()->with('starter')->first();

        $this->assertSame($user->id, $period->started_by);
        $this->assertSame(CarStatusPeriod::SOURCE_MANUAL, $period->source);
        $this->assertSame('Marziyeh Kabganian', $period->actorName());
    }
}
