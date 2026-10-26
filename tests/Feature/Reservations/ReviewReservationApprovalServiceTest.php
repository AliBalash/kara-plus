<?php

namespace Tests\Feature\Reservations;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\User;
use App\Services\Reservations\ReviewReservationApprovalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReviewReservationApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_pending_request_does_not_reserve_a_car_until_an_expert_approves_it(): void
    {
        Carbon::setTestNow('2026-08-23 09:00:00');

        try {
            $car = Car::factory()->available()->create();
            $expert = User::factory()->create();
            $pickup = now()->addDays(4)->startOfHour();
            $request = $this->reviewRequest($car, $pickup, $pickup->copy()->addDays(3));

            $car->syncOperationalState();
            $this->assertSame(Car::STATUS_AVAILABLE, $car->fresh()->status);

            $approved = app(ReviewReservationApprovalService::class)->approve($request->id, $expert->id);

            $this->assertSame('assigned', $approved->current_status);
            $this->assertSame($expert->id, $approved->user_id);
            $this->assertDatabaseHas('contract_statuses', [
                'contract_id' => $request->id,
                'status' => 'assigned',
                'user_id' => $expert->id,
            ]);

            $car->refresh();
            $this->assertSame(Car::STATUS_PRE_RESERVED, $car->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_approval_keeps_review_request_open_when_another_reservation_overlaps(): void
    {
        Carbon::setTestNow('2026-08-23 09:00:00');

        try {
            $car = Car::factory()->available()->create();
            $expert = User::factory()->create();
            $pickup = now()->addDays(4)->startOfHour();

            Contract::factory()
                ->for(Customer::factory())
                ->for($car)
                ->state([
                    'current_status' => 'assigned',
                    'pickup_date' => $pickup,
                    'return_date' => $pickup->copy()->addDays(3),
                ])
                ->create();

            $request = $this->reviewRequest($car, $pickup->copy()->addDay(), $pickup->copy()->addDays(4));

            try {
                app(ReviewReservationApprovalService::class)->approve($request->id, $expert->id);
                $this->fail('Approval must reject an overlapping real reservation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('selectedCarId', $exception->errors());
            }

            $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
            $this->assertNull($request->fresh()->user_id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expert_can_claim_a_review_without_reserving_the_car_and_another_expert_cannot_take_it(): void
    {
        Carbon::setTestNow('2026-08-23 09:00:00');

        try {
            $car = Car::factory()->available()->create();
            $firstExpert = User::factory()->create();
            $otherExpert = User::factory()->create();
            $pickup = now()->addDays(4)->startOfHour();
            $request = $this->reviewRequest($car, $pickup, $pickup->copy()->addDays(3));
            $service = app(ReviewReservationApprovalService::class);

            $claimed = $service->claim($request->id, $firstExpert->id);

            $this->assertSame(Contract::STATUS_REVIEW_PENDING, $claimed->current_status);
            $this->assertSame($firstExpert->id, $claimed->user_id);
            $car->syncOperationalState();
            $this->assertSame(Car::STATUS_AVAILABLE, $car->fresh()->status);

            $this->expectException(ValidationException::class);
            $service->claim($request->id, $otherExpert->id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_diagnostics_explain_a_real_reservation_conflict_without_changing_the_request(): void
    {
        Carbon::setTestNow('2026-08-23 09:00:00');

        try {
            $car = Car::factory()->available()->create();
            $pickup = now()->addDays(4)->startOfHour();
            Contract::factory()
                ->for(Customer::factory())
                ->for($car)
                ->state([
                    'current_status' => 'reserved',
                    'pickup_date' => $pickup,
                    'return_date' => $pickup->copy()->addDays(3),
                ])
                ->create();
            $request = $this->reviewRequest($car, $pickup->copy()->addDay(), $pickup->copy()->addDays(4));

            $diagnostics = app(ReviewReservationApprovalService::class)->diagnostics($request->load('car.carModel'));

            $this->assertFalse($diagnostics['ready']);
            $this->assertSame('reservation_conflict', $diagnostics['issues'][0]['code']);
            $this->assertSame(Contract::STATUS_REVIEW_PENDING, $request->fresh()->current_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_cancelling_a_website_review_preserves_quotes_charges_and_customer_with_one_status_history_entry(): void
    {
        $car = Car::factory()->available()->create();
        $expert = User::factory()->create();
        $request = $this->reviewRequest($car, now()->addDays(2), now()->addDays(5));
        $charge = ContractCharges::factory()->for($request)->create(['amount' => $request->total_price, 'source_type' => 'original']);
        $before = $request->only(['car_id', 'requested_car_id', 'customer_id', 'pickup_date', 'return_date', 'total_price', 'meta']);
        $chargeBefore = $charge->fresh()->getAttributes();
        $service = app(ReviewReservationApprovalService::class);

        $cancelled = $service->cancel($request->id, $expert->id, 'Customer did not proceed');
        $service->cancel($request->id, $expert->id, 'Repeated click');

        $this->assertSame('cancelled', $cancelled->current_status);
        $this->assertSame($expert->id, $cancelled->user_id);
        $this->assertEquals($before, $cancelled->only(array_keys($before)));
        $this->assertSame($chargeBefore, $charge->fresh()->getAttributes());
        $this->assertDatabaseHas('customers', ['id' => $request->customer_id]);
        $this->assertSame(1, $request->statuses()->where('status', 'cancelled')->count());
        $history = $request->statuses()->sole();
        $this->assertSame($expert->id, $history->user_id);
        $this->assertStringContainsString('Customer did not proceed', $history->notes);
        $this->assertSame(Car::STATUS_AVAILABLE, $car->fresh()->status);
        $this->assertTrue($car->fresh()->availability);
    }

    public function test_cancelling_a_review_does_not_release_a_vehicle_reserved_by_another_contract(): void
    {
        $car = Car::factory()->available()->create();
        $booking = Contract::factory()->for($car)->status('reserved')->create([
            'pickup_date' => now()->subDay(), 'return_date' => now()->addDays(3),
        ]);
        $request = $this->reviewRequest($car, now()->addDay(), now()->addDays(4));
        $expert = User::factory()->create();
        app(ReviewReservationApprovalService::class)->cancel($request->id, $expert->id);

        $this->assertSame('reserved', $booking->fresh()->current_status);
        $this->assertSame(Car::STATUS_RESERVED, $car->fresh()->status);
        $this->assertSame('cancelled', $request->fresh()->current_status);
    }

    public function test_review_cancellation_rejects_another_experts_request_and_requests_already_approved_or_from_panel(): void
    {
        $expert = User::factory()->create();
        $otherExpert = User::factory()->create();
        foreach ([
            ['intake_source' => Contract::INTAKE_SOURCE_WEBSITE, 'current_status' => Contract::STATUS_REVIEW_PENDING, 'user_id' => $otherExpert->id],
            ['intake_source' => Contract::INTAKE_SOURCE_WEBSITE, 'current_status' => 'assigned', 'user_id' => $expert->id],
            ['intake_source' => Contract::INTAKE_SOURCE_PANEL, 'current_status' => Contract::STATUS_REVIEW_PENDING, 'user_id' => $expert->id],
        ] as $attributes) {
            $request = Contract::factory()->create($attributes);
            try {
                app(ReviewReservationApprovalService::class)->cancel($request->id, $expert->id);
                $this->fail('Only an unassigned or own pending website request may be cancelled.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('contract', $exception->errors());
            }
            $this->assertSame($attributes['current_status'], $request->fresh()->current_status);
            $this->assertSame(0, $request->statuses()->count());
        }
    }

    private function reviewRequest(Car $car, Carbon $pickup, Carbon $return): Contract
    {
        return Contract::factory()
            ->for(Customer::factory())
            ->for($car)
            ->state([
                'requested_car_id' => $car->id,
                'intake_source' => Contract::INTAKE_SOURCE_WEBSITE,
                'user_id' => null,
                'current_status' => Contract::STATUS_REVIEW_PENDING,
                'pickup_date' => $pickup,
                'return_date' => $return,
            ])
            ->create();
    }
}
