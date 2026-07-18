<?php

namespace Tests\Feature\Livewire\Car;

use App\Livewire\Pages\Panel\Expert\Car\CarUnavailableDesk;
use App\Models\Car;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarUnavailableDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-18 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_need_action_queue_shows_paginated_cars_and_filters_by_upcoming_booking(): void
    {
        $withUpcoming = Car::factory()->create([
            'plate_number' => 'NA-UPCOMING',
            'status' => Car::STATUS_UNAVAILABLE,
            'availability' => false,
            'unavailability_reason' => Car::UNAVAILABILITY_REASON_NEED_ACTION,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
        ]);

        Contract::factory()->for($withUpcoming)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-10 10:00:00'),
            'return_date' => Carbon::parse('2026-07-17 10:00:00'),
        ]);

        Contract::factory()->for($withUpcoming)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-25 10:00:00'),
            'return_date' => Carbon::parse('2026-07-28 10:00:00'),
        ]);

        $withoutUpcoming = Car::factory()->create([
            'plate_number' => 'NA-NEXTLESS',
            'status' => Car::STATUS_UNAVAILABLE,
            'availability' => false,
            'unavailability_reason' => Car::UNAVAILABILITY_REASON_NEED_ACTION,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
        ]);

        Contract::factory()->for($withoutUpcoming)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2026-07-11 10:00:00'),
            'return_date' => Carbon::parse('2026-07-16 10:00:00'),
        ]);

        $component = app(CarUnavailableDesk::class);
        $component->mount();

        $queryMethod = new \ReflectionMethod($component, 'needActionCarsQuery');
        $queryMethod->setAccessible(true);

        $allCars = $queryMethod->invoke($component, 'NA-')
            ->pluck('plate_number')
            ->all();

        $this->assertSame(['NA-NEXTLESS', 'NA-UPCOMING'], $allCars);

        $component->needActionFutureFilter = 'with_upcoming';

        $withUpcomingCars = $queryMethod->invoke($component, 'NA-')
            ->pluck('plate_number')
            ->all();

        $this->assertSame(['NA-UPCOMING'], $withUpcomingCars);
    }
}
