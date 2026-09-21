<?php

namespace Tests\Feature\Contracts;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractAmendmentService;
use App\Services\ContractCommercialCorrectionService;
use App\Services\RentalPricingService;
use App\Services\VehicleAvailabilityService;
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

    public function test_overdue_contract_can_be_extended_from_need_action_and_restores_active_vehicle_state(): void
    {
        [$contract, $actor, $originalCharge] = $this->operationalContract();
        $car = $contract->car->fresh();

        // The helper's planned return is in the past, so the automatic status
        // synchronizer has correctly put this still-open rental in Need Action.
        $this->assertSame(Car::STATUS_UNAVAILABLE, $car->status);
        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $car->unavailability_reason);

        Payment::factory()->for($contract)->for($contract->customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 1000,
            'amount_in_aed' => 1000,
            'approval_status' => 'approved',
        ]);

        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, Carbon::now()->addDay(), $actor->id);
        $approved = $service->approve($amendment, $actor->id);

        $contract->refresh();
        $car->refresh();

        $this->assertTrue($approved->isApproved());
        $this->assertTrue($contract->return_date->isFuture());
        $this->assertSame(Car::STATUS_RESERVED, $car->status);
        $this->assertFalse($car->availability);
        $this->assertNull($car->unavailability_reason);
        $this->assertSame(Car::MANUAL_STATUS_AVAILABLE, $car->resolvedManualStatus());
        $this->assertSame(1, Payment::count());
        $this->assertSame($originalCharge->id, $contract->charges()->where('source_type', 'original')->sole()->id);
        $this->assertEqualsWithDelta(1000 + (float) $approved->total_amount, (float) $contract->total_price, 0.01);
        $this->assertEqualsWithDelta((float) $approved->total_amount, $contract->calculateRemainingBalance(), 0.01);
    }

    public function test_need_action_from_another_overdue_contract_remains_a_vehicle_block(): void
    {
        $car = Car::factory()->available()->create();
        $now = Carbon::now();

        Contract::factory()->for($car)->for(Customer::factory()->state(['gender' => 'male']))->state([
            'current_status' => 'awaiting_return',
            'pickup_date' => $now->copy()->subHours(3),
            'return_date' => $now->copy()->subHour(),
        ])->create();

        $car->refresh();

        $this->assertSame(Car::UNAVAILABILITY_REASON_NEED_ACTION, $car->unavailability_reason);
        $conflicts = app(VehicleAvailabilityService::class)->conflicts(
            $car,
            $now->copy()->subMinutes(30),
            $now->copy()->addDay(),
            999999,
        );

        $this->assertContains('vehicle_status', array_column($conflicts, 'type'));
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

    public function test_extension_uses_insurance_and_addon_tariffs_saved_on_the_contract(): void
    {
        [$contract] = $this->operationalContract([
            'ldw_price_short' => 99,
            'ldw_price_mid' => 88,
            'ldw_price_long' => 77,
        ], [
            'selected_insurance' => 'ldw_insurance',
            'selected_services' => ['child_seat'],
            'service_quantities' => ['child_seat' => 2],
            'pricing_tariffs' => [
                'source' => 'contract_creation',
                'tax_rate' => 0.05,
                'base_days' => 9,
                'insurance' => ['ldw_insurance' => 7],
                'services' => ['child_seat' => ['unit_rate' => 3, 'per_day' => true]],
            ],
        ], false);
        foreach ([
            ['title' => 'base_rental', 'type' => 'base', 'amount' => 1000],
            ['title' => 'ldw_insurance', 'type' => 'insurance', 'amount' => 63],
            ['title' => 'child_seat', 'type' => 'addon', 'amount' => 54],
        ] as $charge) {
            ContractCharges::query()->create([
                'contract_id' => $contract->id,
                'source_type' => 'original',
                ...$charge,
            ]);
        }
        $contract->update(['current_status' => 'awaiting_return']);

        $quote = app(RentalPricingService::class)->quoteExtension(
            $contract->fresh('car'),
            $contract->return_date->copy()->addDays(2)
        );
        $items = collect($quote['items'])->keyBy('code');

        $this->assertSame(RentalPricingService::RATE_SOURCE_CONTRACT, $quote['rate_source']);
        $this->assertEqualsWithDelta(460, (float) $items['extension_rental']['amount'], 0.01);
        $this->assertEqualsWithDelta(14, (float) $items['ldw_insurance']['amount'], 0.01);
        $this->assertEqualsWithDelta(12, (float) $items['child_seat']['amount'], 0.01);
        $this->assertEqualsWithDelta(24.30, (float) $quote['tax'], 0.01);
        $this->assertEqualsWithDelta(510.30, (float) $quote['total'], 0.01);
    }

    public function test_all_billing_policies_and_the_28_day_tariff_boundary_are_explicit(): void
    {
        [$contract] = $this->operationalContract([
            'price_per_day_short' => 240,
            'price_per_day_mid' => 180,
            'price_per_day_long' => 120,
        ]);
        $pricing = app(RentalPricingService::class);

        $daily = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(90), RentalPricingService::POLICY_DAILY_CEILING, RentalPricingService::RATE_SOURCE_CURRENT);
        $hourly = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(90), RentalPricingService::POLICY_HOURLY, RentalPricingService::RATE_SOURCE_CURRENT);
        $prorated = $pricing->quoteExtension($contract, $contract->return_date->copy()->addHours(12), RentalPricingService::POLICY_PRORATED_DAILY, RentalPricingService::RATE_SOURCE_CURRENT);
        $grace = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(60), RentalPricingService::POLICY_GRACE_THEN_DAILY, RentalPricingService::RATE_SOURCE_CURRENT);
        $graceExceeded = $pricing->quoteExtension($contract, $contract->return_date->copy()->addMinutes(61), RentalPricingService::POLICY_GRACE_THEN_DAILY, RentalPricingService::RATE_SOURCE_CURRENT);
        $long = $pricing->quoteExtension($contract, $contract->return_date->copy()->addDays(28), RentalPricingService::POLICY_DAILY_CEILING, RentalPricingService::RATE_SOURCE_CURRENT);

        $this->assertSame(1.0, $daily['billable_days']);
        $this->assertSame(2.0, $hourly['billable_days']);
        $this->assertSame(0.5, $prorated['billable_days']);
        $this->assertSame(0.0, $grace['billable_days']);
        $this->assertSame(1.0, $graceExceeded['billable_days']);
        $this->assertSame(120.0, (float) $long['items'][0]['unit_price']);
        $this->assertSame(RentalPricingService::POLICY_PRORATED_DAILY, $prorated['snapshot']['policy']);
    }

    public function test_contract_rate_is_default_and_rate_comparison_is_explicit(): void
    {
        [$contract] = $this->operationalContract([
            'price_per_day_short' => 100,
            'price_per_day_mid' => 95,
            'price_per_day_long' => 67,
        ]);
        $contract->applyApprovedCommercialCorrection(['used_daily_rate' => 120]);
        $pricing = app(RentalPricingService::class);

        $contractQuote = $pricing->quoteExtension($contract, $contract->return_date->copy()->addDays(2));
        $currentQuote = $pricing->quoteExtension(
            $contract,
            $contract->return_date->copy()->addDays(2),
            RentalPricingService::POLICY_DAILY_CEILING,
            RentalPricingService::RATE_SOURCE_CURRENT
        );

        $this->assertSame(RentalPricingService::RATE_SOURCE_CONTRACT, $contractQuote['rate_source']);
        $this->assertSame(120.0, $contractQuote['effective_daily_rate']);
        $this->assertSame(100.0, $contractQuote['current_daily_rate']);
        $this->assertTrue($contractQuote['rate_changed']);
        $this->assertSame(252.0, $contractQuote['total']);
        $this->assertSame(RentalPricingService::RATE_SOURCE_CURRENT, $currentQuote['rate_source']);
        $this->assertSame(210.0, $currentQuote['total']);
    }

    public function test_current_total_duration_tariff_uses_resulting_daily_weekly_or_monthly_tier(): void
    {
        [$contract] = $this->operationalContract([
            'price_per_day_short' => 240,
            'price_per_day_mid' => 180,
            'price_per_day_long' => 120,
        ]);
        $pricing = app(RentalPricingService::class);

        $extensionLength = $pricing->quoteExtension(
            $contract,
            $contract->return_date->copy()->addDays(2),
            RentalPricingService::POLICY_DAILY_CEILING,
            RentalPricingService::RATE_SOURCE_CURRENT,
        );
        $resultingTotal = $pricing->quoteExtension(
            $contract,
            $contract->return_date->copy()->addDays(2),
            RentalPricingService::POLICY_DAILY_CEILING,
            RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION,
        );

        $this->assertSame(240.0, $extensionLength['effective_daily_rate']);
        $this->assertSame('daily_1_to_6', $extensionLength['rate_tier']);
        $this->assertSame(180.0, $resultingTotal['effective_daily_rate']);
        $this->assertSame('weekly_7_to_27', $resultingTotal['rate_tier']);
        $this->assertSame(11, $resultingTotal['resulting_rental_days']);
        $this->assertSame(RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION, $resultingTotal['snapshot']['rate_source']);
    }

    public function test_pending_extension_can_be_requoted_and_updated_without_changing_contract(): void
    {
        [$contract, $actor] = $this->operationalContract([
            'price_per_day_short' => 240,
            'price_per_day_mid' => 180,
        ]);
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDays(2), $actor->id);

        $updated = $service->updatePendingExtension(
            $amendment,
            $contract->return_date->copy()->addDays(3),
            $actor->id,
            RentalPricingService::POLICY_DAILY_CEILING,
            RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION,
            'Customer requested one more day',
            'Reviewed with customer',
        );

        $this->assertSame('pending_approval', $updated->status);
        $this->assertSame('2026-09-13 10:00:00', $updated->new_return_at->format('Y-m-d H:i:s'));
        $this->assertSame(RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION, $updated->pricing_snapshot['rate_source']);
        $this->assertEqualsWithDelta(567.0, (float) $updated->total_amount, 0.01);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
        $this->assertSame(0, $updated->charges()->count());
    }

    public function test_unapproved_extension_delete_is_soft_and_has_no_financial_effect(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id);

        $service->deleteExtension($amendment, $actor->id, 'Duplicate request');

        $this->assertSame(0, $contract->amendments()->count());
        $this->assertSame(1, ContractAmendment::withTrashed()->whereKey($amendment->id)->count());
        $this->assertNotNull(ContractAmendment::withTrashed()->findOrFail($amendment->id)->deleted_at);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
    }

    public function test_latest_approved_extension_can_be_voided_with_balancing_reversal(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $approved = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDays(2), $actor->id), $actor->id);

        $service->deleteExtension($approved, $actor->id, 'Entered by mistake');

        $this->assertSame('voided', $approved->fresh()->status);
        $this->assertSame('2026-09-10 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(1000.0, (float) $contract->fresh()->total_price, 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $contract->charges()->sum('amount'), 0.01);
        $this->assertSame(-483.0, (float) $contract->amendments()->where('pricing_policy', 'extension_void_reversal')->sole()->total_amount);
    }

    public function test_latest_approved_extension_can_be_revised_without_rewriting_old_ledger_rows(): void
    {
        [$contract, $actor] = $this->operationalContract([
            'price_per_day_short' => 240,
            'price_per_day_mid' => 180,
        ]);
        $service = app(ContractAmendmentService::class);
        $approved = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDays(2), $actor->id), $actor->id);
        $originalChargeIds = $approved->charges()->pluck('id')->all();

        $replacement = $service->reviseApprovedExtension(
            $approved,
            $approved->old_return_at->copy()->addDays(3),
            $actor->id,
            RentalPricingService::POLICY_DAILY_CEILING,
            RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION,
            'Corrected return date',
        );

        $this->assertSame('superseded', $approved->fresh()->status);
        $this->assertTrue($replacement->isApproved());
        $this->assertSame($approved->id, $replacement->pricing_snapshot['replaces_amendment_id']);
        $this->assertSame('2026-09-13 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(1567.0, (float) $contract->fresh()->total_price, 0.01);
        $this->assertEqualsWithDelta(1567.0, (float) $contract->charges()->sum('amount'), 0.01);
        $this->assertEqualsCanonicalizing($originalChargeIds, ContractCharges::whereKey($originalChargeIds)->pluck('id')->all());
        $this->assertSame([1, 2, 3], $contract->amendments()->pluck('sequence_no')->all());
    }

    public function test_older_approved_extension_cannot_be_revised_before_dependent_latest_extension(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $first = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id), $actor->id);
        $contract->refresh();
        $second = $service->approve($service->requestExtension($contract, $contract->return_date->copy()->addDay(), $actor->id), $actor->id);

        try {
            $service->reviseApprovedExtension(
                $first,
                $first->old_return_at->copy()->addHours(12),
                $actor->id,
                RentalPricingService::POLICY_DAILY_CEILING,
                RentalPricingService::RATE_SOURCE_CONTRACT,
            );
            $this->fail('An older dependent extension must not be revised first.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amendment', $exception->errors());
        }

        $this->assertTrue($first->fresh()->isApproved());
        $this->assertTrue($second->fresh()->isApproved());
        $this->assertSame('2026-09-12 10:00:00', $contract->fresh()->return_date->format('Y-m-d H:i:s'));
    }

    public function test_approval_rejects_an_unseen_current_tariff_change(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $service = app(ContractAmendmentService::class);
        $amendment = $service->requestExtension(
            $contract,
            $contract->return_date->copy()->addDay(),
            $actor->id,
            pricingPolicy: RentalPricingService::POLICY_DAILY_CEILING,
            rateSource: RentalPricingService::RATE_SOURCE_CURRENT
        );
        $contract->car->update(['price_per_day_short' => 300]);

        try {
            $service->approve($amendment, $actor->id);
            $this->fail('Approval must not silently replace the rate reviewed at request time.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pricing', $exception->errors());
        }

        $this->assertSame('pending_approval', $amendment->fresh()->status);
        $this->assertSame(1000.0, (float) $contract->fresh()->total_price);
        $this->assertSame(0, $contract->charges()->where('source_type', 'amendment')->count());
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

    public function test_commercial_correction_accepts_any_signed_in_panel_user_and_audits_the_actor(): void
    {
        [$contract, $actor] = $this->operationalContract();

        $amendment = app(ContractCommercialCorrectionService::class)->apply(
            $contract,
            $actor->id,
            ['total_price' => 1100],
            ['base_rental' => 1000, 'vat' => 0],
            ['base_rental' => 1100, 'vat' => 0],
        );

        $this->assertTrue($amendment->isApproved());
        $this->assertSame($actor->id, $amendment->approved_by);
        $this->assertSame(1, $contract->amendments()->count());
        $this->assertSame(1100.0, (float) $contract->fresh()->total_price);
        $this->assertSame(1100.0, (float) $contract->charges()->sum('amount'));
    }

    public function test_commercial_correction_cannot_replace_the_return_date_of_an_approved_extension(): void
    {
        [$contract, $actor] = $this->operationalContract();
        $extension = app(ContractAmendmentService::class)->requestExtension(
            $contract,
            $contract->return_date->copy()->addDays(3),
            $actor->id,
        );
        app(ContractAmendmentService::class)->approve($extension, $actor->id);
        $contract->refresh();

        try {
            app(ContractCommercialCorrectionService::class)->apply(
                $contract,
                $actor->id,
                ['return_date' => $contract->original_return_date],
                ['base_rental' => (float) $contract->total_price, 'vat' => 0],
                ['base_rental' => (float) $contract->total_price, 'vat' => 0],
            );
            $this->fail('A commercial correction must not undo an approved extension.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'No correction was applied. Approved extension #1 sets the current planned return to 13 Sep 2026, 10:00. A commercial correction cannot shorten, remove, or replace that extension. Use Extend Contract to revise it.',
                $exception->errors()['return_date'][0],
            );
        }

        $this->assertTrue($contract->fresh()->return_date->equalTo($extension->new_return_at));
        $this->assertSame(1, $contract->amendments()->count());
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
            'used_daily_rate' => 230,
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
