<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractAmendment;
use App\Models\ContractCharges;
use App\Services\ContractPaymentRangeRentalService;
use Carbon\Carbon;
use Tests\TestCase;

class ContractPaymentRangeRentalServiceTest extends TestCase
{
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

    public function test_legacy_correction_and_reversal_rows_cancel_the_replaced_extension(): void
    {
        $contract = new Contract(['pickup_date' => '2026-08-20', 'return_date' => '2026-09-27', 'total_price' => 4095]);
        $extension = new ContractAmendment(['type' => 'extension', 'status' => 'approved', 'extension_start_at' => '2026-09-19', 'extension_end_at' => '2026-09-26']);
        $extension->id = 1;
        $correction = new ContractAmendment(['type' => 'adjustment', 'status' => 'approved', 'old_return_at' => '2026-09-26', 'new_return_at' => '2026-09-19']);
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
