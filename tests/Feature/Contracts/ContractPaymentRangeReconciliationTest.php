<?php

namespace Tests\Feature\Contracts;

use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestEdit;
use App\Livewire\Pages\Panel\Expert\RentalRequest\RentalRequestPayment;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\ContractAmendmentService;
use App\Services\ContractCommercialCorrectionService;
use App\Services\ContractPaymentPeriodService;
use App\Services\ContractPaymentRangeRentalService;
use App\Services\RentalPricingService;
use App\Support\RentalDuration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractPaymentRangeReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_correction_then_extension_agree_in_payment_ranges_edit_summary_and_contract_balance(): void
    {
        [$contract] = $this->correctedAndExtendedContract();
        foreach ([['2026-09-18', 910.14], ['2026-10-08', 1365.21]] as [$date, $amount]) {
            Payment::factory()->for($contract)->for($contract->customer)->for($contract->car)->create([
                'payment_type' => 'rental_fee', 'currency' => 'AED',
                'amount' => $amount, 'amount_in_aed' => $amount, 'payment_date' => $date,
                'is_paid' => true, 'approval_status' => 'approved',
            ]);
        }
        $beforeCharges = $contract->charges()->get()->toArray();
        $beforePayments = $contract->payments()->get()->toArray();
        $page = app(RentalRequestPayment::class);
        $page->mount($contract->id, $contract->customer_id);
        $periods = collect($page->paymentPeriods);

        $this->assertSame([910.14, 1365.21], $periods->pluck('rental_amount')->all());
        $this->assertSame([0.0, 0.0], $periods->pluck('ledger_balance')->all());
        $this->assertSame(0.0, $page->remainingBalance);
        $this->assertSame(0.0, $page->overallLedgerBalance);
        $this->assertSame(0, $page->unassignedPayments->count());

        $edit = app(RentalRequestEdit::class);
        $edit->mount($contract->id);
        $this->assertEqualsWithDelta(866.80, $edit->base_price, 0.00001);
        $this->assertEqualsWithDelta(1300.20, $edit->extension_charges_total, 0.00001);
        $this->assertEqualsWithDelta(108.35, $edit->tax_amount, 0.00001);
        $this->assertEqualsWithDelta(2275.35, $edit->final_total, 0.00001);
        $this->assertSame($beforeCharges, $contract->charges()->get()->toArray());
        $this->assertSame($beforePayments, $contract->payments()->get()->toArray());
        $this->assertReconciles($contract);
    }

    public function test_pickup_rate_and_location_corrections_after_extension_only_change_original_rental(): void
    {
        [$contract, $actor] = $this->correctedAndExtendedContract();
        app(ContractCommercialCorrectionService::class)->apply(
            $contract, $actor->id,
            ['pickup_date' => '2026-09-19 16:10', 'used_daily_rate' => 50, 'total_price' => 2467.71],
            ['base_rental' => 866.80, 'vat' => 43.34],
            ['base_rental' => 950, 'pickup_transfer' => 100, 'vat' => 52.50, 'rental_days' => 19, 'daily_rate' => 50],
        );
        $contract->refresh();
        $service = app(ContractPaymentRangeRentalService::class);

        $this->assertSame(1102.5, $service->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-10-08')));
        $this->assertSame(1365.21, $service->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
        $this->assertSame(0.0, $service->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-09-19')));
        $this->assertReconciles($contract);
    }

    public function test_extension_revision_and_void_keep_corrected_original_rental_and_ledger_totals(): void
    {
        [$contract, $actor, $extension] = $this->correctedAndExtendedContract();
        $service = app(ContractAmendmentService::class);
        $replacement = $service->reviseApprovedExtension(
            $extension, Carbon::parse('2026-11-05 16:10'), $actor->id,
            RentalPricingService::POLICY_DAILY_CEILING, RentalPricingService::RATE_SOURCE_CONTRACT,
        );
        $contract->refresh();
        $ranges = app(ContractPaymentRangeRentalService::class);
        $this->assertSame(910.14, $ranges->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-10-08')));
        $this->assertSame((float) $replacement->total_amount, $ranges->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
        $this->assertReconciles($contract);

        $service->deleteExtension($replacement, $actor->id);
        $contract->refresh();
        $this->assertSame(910.14, $ranges->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-10-08')));
        $this->assertSame(0.0, $ranges->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
        $this->assertReconciles($contract);

    }

    private function correctedAndExtendedContract(): array
    {
        config(['ai.enabled' => false]);
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $contract = Contract::factory()->for($actor)->for(Customer::factory())->for(Car::factory()->available())->create([
            'current_status' => 'assigned', 'pickup_date' => '2026-09-18 12:00', 'return_date' => '2026-10-08 17:55',
            'total_price' => 955.65, 'used_daily_rate' => 43.34, 'custom_daily_rate_enabled' => true,
            'meta' => ['pricing_tariffs' => RentalDuration::currentPolicySnapshot()],
        ]);
        foreach ([['base_rental', 'base', 910.14], ['tax', 'tax', 45.51]] as [$title, $type, $amount]) {
            ContractCharges::factory()->for($contract)->create(['title' => $title, 'type' => $type, 'amount' => $amount, 'source_type' => 'original']);
        }
        $contract->update(['current_status' => 'awaiting_return']);
        app(ContractCommercialCorrectionService::class)->apply(
            $contract, $actor->id,
            ['pickup_date' => '2026-09-18 16:10', 'return_date' => '2026-10-08 16:10', 'total_price' => 910.14],
            ['base_rental' => 910.14, 'vat' => 45.51],
            ['base_rental' => 866.80, 'vat' => 43.34, 'rental_days' => 20, 'daily_rate' => 43.34],
        );
        $contract->refresh();
        $service = app(ContractAmendmentService::class);
        $extension = $service->approve($service->requestExtension($contract, '2026-11-07 16:10', $actor->id), $actor->id);
        foreach ([['2026-09-18', '2026-10-08'], ['2026-10-08', '2026-11-07']] as [$start, $end]) {
            app(ContractPaymentPeriodService::class)->save($contract->id, $actor->id, ['starts_on' => $start, 'ends_on' => $end, 'is_default' => false]);
        }

        return [$contract->fresh(), $actor, $extension];
    }

    private function assertReconciles(Contract $contract): void
    {
        $contract->load(['amendments', 'charges']);
        $amount = app(ContractPaymentRangeRentalService::class)->amount($contract, Carbon::parse('2026-09-01'), Carbon::parse('2026-12-01'));
        $this->assertEqualsWithDelta((float) $contract->total_price, (float) $contract->charges->sum('amount'), 0.00001);
        $this->assertSame((float) $contract->total_price, $amount);
        $sum = 0;
        for ($date = Carbon::parse('2026-09-01'); $date->lt(Carbon::parse('2026-12-01')); $date->addDay()) {
            $sum += app(ContractPaymentRangeRentalService::class)->amount($contract, $date, $date->copy()->addDay());
        }
        $this->assertEqualsWithDelta((float) $contract->total_price, $sum, 0.00001);
    }
}
