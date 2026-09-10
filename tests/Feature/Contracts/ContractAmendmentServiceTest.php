<?php

namespace Tests\Feature\Contracts;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractAmendmentService;
use App\Services\ContractCommercialCorrectionService;
use App\Services\RentalPricingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractAmendmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_extends_the_same_contract_and_keeps_original_charges(): void
    {
        [$contract, $actor, $original] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);

        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDays(5), $actor->id, '6e742fd6-236f-4dfd-a963-e4e4be23b7c1');
        $approved = $service->approve($amendment, $actor->id);
        $contract->refresh();

        $this->assertSame(1, Contract::count());
        $this->assertTrue($approved->isApproved());
        $this->assertSame(1, $contract->amendments()->count());
        $this->assertSame($original->id, $contract->charges()->where('source_type', 'original')->sole()->id);
        $this->assertSame(2, $contract->charges()->where('source_type', 'amendment')->count());
        $this->assertEqualsWithDelta((float) $approved->total_amount, (float) $approved->charges()->sum('amount'), 0.01);
        $this->assertSame('2026-09-10 10:00:00', $contract->original_return_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-15 10:00:00', $contract->return_date->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(2207.50, (float) $contract->total_price, 0.01);
    }

    public function test_conflicting_future_booking_rejects_approval_and_rolls_back_every_financial_change(): void
    {
        [$contract, $actor] = $this->operationalContract();
        Contract::factory()->for($contract->car)->for(Customer::factory()->state(['gender' => 'male']))->state([
            'current_status' => 'reserved',
            'pickup_date' => $contract->return_date->copy()->addDays(2),
            'return_date' => $contract->return_date->copy()->addDays(4),
        ])->create();
        $amendment = app(ContractAmendmentService::class)->requestExtension($contract, $contract->return_date->copy()->addDays(5), $actor->id);

        try {
            app(ContractAmendmentService::class)->approve($amendment, $actor->id);
            $this->fail('Expected an availability conflict.');
        } catch (ValidationException) {
        }

        $this->assertSame('pending_approval', $amendment->fresh()->status);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
        $this->assertSame(0, $contract->charges()->where('source_type', 'amendment')->count());
    }

    public function test_repeated_approval_and_idempotent_request_do_not_duplicate_charges(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $key = 'a97875bf-f1ba-4dda-bc72-7fc18e5457e8';
        $first = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id, $key);
        $second = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id, $key);
        $service->approve($first, $actor->id);
        $service->approve($first->fresh(), $actor->id);
        $afterApprovalRetry = $service->requestExtension($contract, Carbon::parse('2026-09-11 10:00:00'), $actor->id, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->id, $afterApprovalRetry->id);
        $this->assertSame(1, $contract->fresh()->amendments()->count());
        $this->assertSame(2, $contract->charges()->where('source_type', 'amendment')->count());
    }

    public function test_same_idempotency_key_cannot_be_reused_for_different_request(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $key = '54a74064-f15f-4a43-b05f-225bace9b35e';
        $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id, $key);

        $this->expectException(ValidationException::class);
        $service->requestExtension($contract, $contract->return_date->copy()->addDays(2), $actor->id, $key);
    }

    public function test_multiple_extensions_are_sequenced_and_accumulate_on_one_contract(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $first = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id);
        $service->approve($first, $actor->id);
        $contract->refresh();
        $second = $service->requestExtension($contract, $contract->return_date->copy()->addDays(2), $actor->id);
        $service->approve($second, $actor->id);

        $this->assertSame(1, Contract::count());
        $this->assertSame([1, 2], $contract->amendments()->pluck('sequence_no')->all());
        $this->assertSame('2026-09-13 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(1000 + (float) $first->fresh()->total_amount + (float) $second->fresh()->total_amount, (float) $contract->fresh()->total_price, 0.01);
    }

    public function test_extension_increases_the_existing_contract_payment_balance_only(): void
    {
        [$contract, $actor] = $this->operationalContract();
        Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 1000,
            'amount_in_aed' => 1000,
            'approval_status' => 'approved',
        ]);
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id);
        $approved = $service->approve($amendment, $actor->id);

        $this->assertSame(1, Contract::count());
        $this->assertSame(1, Payment::count());
        $this->assertEqualsWithDelta((float) $approved->total_amount, $contract->fresh()->calculateRemainingBalance(), 0.01);
    }

    public function test_insurance_addons_and_vat_are_priced_for_only_the_added_period(): void
    {
        [$contract, $actor] = $this->operationalContract([
            'ldw_price_short' => 35,
            'ldw_price_mid' => 30,
            'ldw_price_long' => 25,
        ], ['service_quantities' => ['child_seat' => 2]], false);
        ContractCharges::query()->create(['contract_id' => $contract->id, 'title' => 'base_rental', 'type' => 'base', 'amount' => 1000, 'source_type' => 'original']);
        ContractCharges::query()->create(['contract_id' => $contract->id, 'title' => 'ldw_insurance', 'type' => 'insurance', 'amount' => 315, 'source_type' => 'original']);
        ContractCharges::query()->create(['contract_id' => $contract->id, 'title' => 'child_seat', 'type' => 'addon', 'amount' => 360, 'source_type' => 'original']);
        $contract->update(['current_status' => 'awaiting_return']);

        $service = app(ContractAmendmentService::class);
        $approved = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDays(5), $actor->id), $actor->id);
        $charges = $approved->charges()->get()->keyBy('type');

        $this->assertEqualsWithDelta(1150, (float) $charges['extension_rental']->amount, 0.01);
        $this->assertEqualsWithDelta(175, (float) $charges['ldw_insurance']->amount, 0.01);
        $this->assertEqualsWithDelta(200, (float) $charges['child_seat']->amount, 0.01);
        $this->assertEqualsWithDelta(76.25, (float) $charges['tax']->amount, 0.01);
        $this->assertEqualsWithDelta(1601.25, (float) $approved->total_amount, 0.01);
        $this->assertEqualsWithDelta((float) $approved->total_amount, (float) $approved->charges()->sum('amount'), 0.01);
    }

    public function test_all_billing_policies_and_the_28_day_tariff_boundary_are_explicit(): void
    {
        [$contract] = $this->operationalContract([
            'price_per_day_short' => 240,
            'price_per_day_mid' => 180,
            'price_per_day_long' => 120,
        ]);
        $pricing = app(RentalPricingService::class);

        $daily = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(90), RentalPricingService::POLICY_DAILY_CEILING);
        $hourly = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(90), RentalPricingService::POLICY_HOURLY);
        $prorated = $pricing->quoteExtension($contract, $contract->return_date->copy()->addHours(12), RentalPricingService::POLICY_PRORATED_DAILY);
        $grace = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(120), RentalPricingService::POLICY_GRACE_THEN_DAILY);
        $long = $pricing->quoteExtension($contract, $contract->return_date->copy()->addDays(28));

        $this->assertSame(1.0, $daily['billable_days']);
        $this->assertSame(2.0, $hourly['billable_days']);
        $this->assertSame(0.5, $prorated['billable_days']);
        $this->assertSame(0.0, $grace['billable_days']);
        $this->assertSame(120.0, (float) $long['items'][0]['unit_price']);
        $this->assertSame(RentalPricingService::POLICY_PRORATED_DAILY, $prorated['snapshot']['policy']);
    }

    public function test_approval_uses_current_tariff_and_then_freezes_that_snapshot(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id);
        $contract->car->update(['price_per_day_short' => 300]);

        $approved = $service->approve($amendment, $actor->id);

        $this->assertEqualsWithDelta(315, (float) $approved->total_amount, 0.01);
        $this->assertEqualsWithDelta(300, (float) $approved->pricing_snapshot['items'][0]['unit_price'], 0.01);
    }

    public function test_rejected_extension_changes_neither_contract_nor_charges(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDays(3), $actor->id);
        $service->reject($amendment, $actor->id, 'Customer changed their mind.');

        $this->assertSame('rejected', $amendment->fresh()->status);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
        $this->assertSame(0, $contract->charges()->where('source_type', 'amendment')->count());
    }

    public function test_approved_amendment_and_operational_financial_terms_are_immutable(): void
    {
        [$contract, $actor, $original] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $approved = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id), $actor->id);

        $mutations = [
            'approved amendment' => fn () => $approved->update(['pricing_snapshot' => ['tampered' => true]]),
            'original charge update' => fn () => $original->update(['amount' => 1]),
            'original charge delete' => fn () => $original->delete(),
            'contract total' => fn () => $contract->fresh()->update(['total_price' => 1]),
            'contract return' => fn () => $contract->fresh()->update(['return_date' => Carbon::parse('2026-10-01')]),
            'original return' => fn () => $contract->fresh()->update(['original_return_date' => Carbon::parse('2026-10-01')]),
        ];
        foreach ($mutations as $name => $mutation) {
            try {
                $mutation();
                $this->fail("Operational financial history must be immutable: {$name}.");
            } catch (DomainException) {
            }
        }

        $this->assertNotSame(1.0, (float) $contract->fresh()->total_price);
        $this->assertNotSame(1.0, (float) $original->fresh()->amount);
    }

    public function test_actual_return_is_written_once_and_never_overwrites_planned_return(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $planned = $contract->return_date->copy();
        Carbon::setTestNow('2026-09-11 12:00:00');
        $contract->update(['actual_return_at' => now()]);
        Carbon::setTestNow('2026-09-12 15:00:00');
        $contract->changeStatus('complete', $actor->id);

        $fresh = $contract->fresh();
        $this->assertTrue($fresh->return_date->equalTo($planned));
        $this->assertSame('2026-09-11 12:00:00', $fresh->actual_return_at->format('Y-m-d H:i:s'));
        Carbon::setTestNow();
    }

    public function test_returned_contract_cannot_be_extended(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $contract->update(['current_status' => 'returned']);

        $this->expectException(ValidationException::class);
        app(ContractAmendmentService::class)->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id);
    }

    public function test_commercial_correction_rejects_every_user_except_the_explicitly_authorized_user(): void
    {
        [$contract, $actor] = $this->operationalContract();

        try {
            app(ContractCommercialCorrectionService::class)->apply(
                $contract,
                $actor->id,
                ['total_price' => 1100],
                ['base_rental' => 1000, 'vat' => 0],
                ['base_rental' => 1100, 'vat' => 0],
            );
            $this->fail('An unauthorized user must not be able to correct locked commercial terms.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contract', $exception->errors());
        }

        $this->assertSame(0, $contract->amendments()->count());
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
        $this->assertSame(1000.0, (float) $contract->charges()->sum('amount'));
    }

    public function test_contract_customer_and_vehicle_history_cannot_be_hard_deleted(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id), $actor->id);

        foreach ([$contract->fresh(), $contract->customer, $contract->car] as $model) {
            try {
                $model->delete();
                $this->fail('Rental business history must not be hard deleted.');
            } catch (DomainException) {
            }
        }

        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('customers', ['id' => $contract->customer_id]);
        $this->assertDatabaseHas('cars', ['id' => $contract->car_id]);
    }

    private function operationalContract(array $carAttributes = [], array $meta = [], bool $createOriginalCharge = true): array
    {
        $car = Car::factory()->available()->create([
            'price_per_day_short' => 230,
            'price_per_day_mid' => 230,
            'price_per_day_long' => 230,
            ...$carAttributes,
        ]);
        $contract = Contract::factory()->for($car)->for(Customer::factory()->state(['gender' => 'male']))->state([
            'current_status' => 'assigned',
            'pickup_date' => Carbon::parse('2026-09-01 10:00:00'),
            'return_date' => Carbon::parse('2026-09-10 10:00:00'),
            'total_price' => 1000,
            'meta' => $meta,
        ])->create();
        $original = $createOriginalCharge ? ContractCharges::factory()->for($contract)->create([
            'title' => 'base_rental',
            'type' => 'base',
            'amount' => 1000,
            'source_type' => 'original',
        ]) : null;

        if ($createOriginalCharge) {
            $contract->update(['current_status' => 'awaiting_return']);
        }

        return [$contract->fresh(), User::factory()->create(), $original];
    }
}
