<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Services\ContractCommercialCorrectionService;
use App\Services\ContractPaymentRangeRentalService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContractPaymentRangeRentalServiceTest extends TestCase
{
    public function test_contract_2261_clock_correction_belongs_to_original_rental_not_extension(): void
    {
        $contract = $this->correctedContract('2026-10-08 17:55', '2026-10-08 16:10');
        $service = app(ContractPaymentRangeRentalService::class);

        $this->assertSame(910.14, $service->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-10-08')));
        $this->assertSame(1365.21, $service->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
        $this->assertPartitionReconciles($contract);
    }

    public function test_date_and_amount_corrections_reflow_with_original_rental_and_leave_extensions_unchanged(): void
    {
        foreach ([
            ['2026-10-09 16:10', '2026-10-08 16:10'], // Shorter rental.
            ['2026-10-07 16:10', '2026-10-08 16:10'], // Longer rental.
            ['2026-10-08 16:10', '2026-10-08 16:10'], // Rate/services only.
        ] as [$oldReturn, $newReturn]) {
            $contract = $this->correctedContract($oldReturn, $newReturn);
            // A later pickup/rate correction must also reflow older corrections.
            $contract->pickup_date = '2026-09-20 16:10';
            $correction = new ContractAmendment([
                'type' => 'adjustment', 'status' => 'approved',
                'pricing_policy' => ContractCommercialCorrectionService::SCOPE_OPERATIONAL_LOCATION,
                'old_return_at' => $contract->return_date, 'new_return_at' => $contract->return_date,
            ]);
            $correction->id = 3;
            $contract->amendments->push($correction);
            $contract->charges->push(new ContractCharges(['amendment_id' => 3, 'amount' => 105]));
            $contract->total_price = 2380.35;

            $service = app(ContractPaymentRangeRentalService::class);
            $this->assertSame(1015.14, $service->amount($contract, Carbon::parse('2026-09-20'), Carbon::parse('2026-10-08')));
            $this->assertSame(1365.21, $service->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
            $this->assertSame(0.0, $service->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-09-20')));
            $this->assertPartitionReconciles($contract);
        }
    }

    public function test_legacy_charge_policy_identifies_corrections_without_amendment_policy(): void
    {
        $contract = $this->correctedContract('2026-10-08 17:55', '2026-10-08 16:10');
        $contract->amendments->first()->pricing_policy = null;
        $service = app(ContractPaymentRangeRentalService::class);

        $this->assertSame(910.14, $service->amount($contract, Carbon::parse('2026-09-18'), Carbon::parse('2026-10-08')));
        $this->assertSame(1365.21, $service->amount($contract, Carbon::parse('2026-10-08'), Carbon::parse('2026-11-07')));
    }

    private function correctedContract(string $oldReturn, string $newReturn): Contract
    {
        $contract = new Contract(['pickup_date' => '2026-09-18 16:10', 'return_date' => '2026-11-07 16:10', 'total_price' => 2275.35]);
        $correction = new ContractAmendment([
            'type' => 'adjustment', 'status' => 'approved',
            'pricing_policy' => ContractCommercialCorrectionService::SCOPE_AUTHORIZED,
            'old_return_at' => $oldReturn, 'new_return_at' => $newReturn,
        ]);
        $correction->id = 1;
        $extension = new ContractAmendment([
            'type' => 'extension', 'status' => 'approved',
            'extension_start_at' => $newReturn, 'extension_end_at' => '2026-11-07 16:10',
        ]);
        $extension->id = 2;
        $contract->setRelation('amendments', collect([$correction, $extension]));
        $contract->setRelation('charges', collect([
            new ContractCharges(['amount' => 910.14]),
            new ContractCharges(['amount' => 45.51]),
            new ContractCharges(['amendment_id' => 1, 'amount' => -43.34, 'metadata' => ['policy' => ContractCommercialCorrectionService::SCOPE_AUTHORIZED, 'category' => 'base_rental']]),
            new ContractCharges(['amendment_id' => 1, 'amount' => -2.17, 'metadata' => ['policy' => ContractCommercialCorrectionService::SCOPE_AUTHORIZED, 'category' => 'vat']]),
            new ContractCharges(['amendment_id' => 2, 'amount' => 1300.20, 'tax_amount' => 65.01]),
            new ContractCharges(['amendment_id' => 2, 'amount' => 65.01]),
        ]));

        return $contract;
    }

    private function assertPartitionReconciles(Contract $contract): void
    {
        $service = app(ContractPaymentRangeRentalService::class);
        $sum = 0.0;
        for ($date = $contract->pickup_date->copy()->startOfDay(); $date->lt($contract->return_date->copy()->startOfDay()); $date->addDay()) {
            $sum += $service->amount($contract, $date, $date->copy()->addDay());
        }
        $this->assertEqualsWithDelta((float) $contract->total_price, $sum, 0.00001);
    }

    public function test_original_rental_and_extension_tax_are_assigned_once_to_their_ranges(): void
    {
        $contract = new Contract(['pickup_date' => '2026-09-14 11:30', 'return_date' => '2026-11-06 11:30', 'total_price' => 2411.87]);
        $extension = new ContractAmendment([
            'type' => 'extension', 'status' => 'approved',
            'extension_start_at' => '2026-10-07 11:30', 'extension_end_at' => '2026-11-06 11:30',
        ]);
        $extension->id = 1;
        $contract->setRelation('amendments', collect([$extension]));
        $contract->setRelation('charges', collect([
            new ContractCharges(['amount' => 996.82]),
            new ContractCharges(['amount' => 49.84]),
            new ContractCharges(['amendment_id' => 1, 'amount' => 1300.20, 'tax_amount' => 65.01]),
            new ContractCharges(['amendment_id' => 1, 'amount' => 65.01]),
        ]));
        $service = app(ContractPaymentRangeRentalService::class);
        $this->assertSame(1046.66, $service->amount($contract, Carbon::parse('2026-09-14'), Carbon::parse('2026-10-07')));
        $this->assertSame(1365.21, $service->amount($contract, Carbon::parse('2026-10-07'), Carbon::parse('2026-11-06')));
    }

    public function test_legacy_proration_conserves_cents_and_does_not_allocate_outside_contract_dates(): void
    {
        $contract = new Contract(['pickup_date' => '2026-01-01', 'return_date' => '2026-01-04', 'total_price' => 100]);
        $contract->setRelation('amendments', collect());
        $contract->setRelation('charges', collect());
        $service = app(ContractPaymentRangeRentalService::class);
        $amounts = [];
        foreach ([1, 2, 3] as $day) {
            $amounts[] = $service->amount($contract, Carbon::parse("2026-01-0{$day}"), Carbon::parse('2026-01-01')->addDays($day));
        }
        $this->assertSame([33.33, 33.34, 33.33], $amounts);
        $this->assertSame(100.0, array_sum($amounts));
        $this->assertSame(0.0, $service->amount($contract, Carbon::parse('2026-01-04'), Carbon::parse('2026-01-08')));
    }

    public static function legacyExtensionCorrectionPolicies(): array
    {
        return [
            'unclassified legacy adjustment' => [null],
            'legacy commercial edit undoing extension' => [ContractCommercialCorrectionService::SCOPE_AUTHORIZED],
        ];
    }

    #[DataProvider('legacyExtensionCorrectionPolicies')]
    public function test_legacy_correction_and_reversal_rows_cancel_the_replaced_extension(?string $policy): void
    {
        $contract = new Contract(['pickup_date' => '2026-08-20', 'return_date' => '2026-09-27', 'total_price' => 4095]);
        $extension = new ContractAmendment(['type' => 'extension', 'status' => 'approved', 'extension_start_at' => '2026-09-19', 'extension_end_at' => '2026-09-26']);
        $extension->id = 1;
        $correction = new ContractAmendment(['type' => 'adjustment', 'status' => 'approved', 'pricing_policy' => $policy, 'old_return_at' => '2026-09-26', 'new_return_at' => '2026-09-19']);
        $correction->id = 2;
        $replacement = new ContractAmendment(['type' => 'extension', 'status' => 'approved', 'extension_start_at' => '2026-09-26', 'extension_end_at' => '2026-09-27']);
        $replacement->id = 3;
        $contract->setRelation('amendments', collect([$extension, $correction, $replacement]));
        $contract->setRelation('charges', collect([
            new ContractCharges(['amount' => 3150]),
            new ContractCharges(['amendment_id' => 1, 'amount' => 735]),
            new ContractCharges(['amendment_id' => 2, 'amount' => -735]),
            new ContractCharges(['amendment_id' => 1, 'amount' => 735]),
            new ContractCharges(['amendment_id' => 3, 'amount' => 2415]),
            new ContractCharges(['amendment_id' => 3, 'amount' => -2415]),
            new ContractCharges(['amendment_id' => 3, 'amount' => 210]),
        ]));
        $service = app(ContractPaymentRangeRentalService::class);
        $this->assertSame(3150.0, $service->amount($contract, Carbon::parse('2026-08-20'), Carbon::parse('2026-09-19')));
        $this->assertSame(945.0, $service->amount($contract, Carbon::parse('2026-09-19'), Carbon::parse('2026-09-27')));
    }
}
