<?php

namespace Tests\Feature\Reports;

use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\Reports\OperationsReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OperationsReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OperationsReportService::class);
    }

    public function test_customer_requests_report_filters_by_customer_and_builds_financial_summary(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Sara',
            'last_name' => 'Smith',
            'gender' => 'female',
        ]);
        $car = Car::factory()->create(['plate_number' => 'SMT-1001']);
        $expert = User::factory()->create(['first_name' => 'Nina', 'last_name' => 'Stone']);

        $matchingContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->for($expert)
            ->create([
                'created_at' => Carbon::parse('2025-02-10 09:00:00'),
                'pickup_date' => Carbon::parse('2025-02-15 10:00:00'),
                'return_date' => Carbon::parse('2025-02-18 10:00:00'),
                'current_status' => 'payment',
                'total_price' => 1000,
                'kardo_required' => true,
                'payment_on_delivery' => false,
                'submitted_by_name' => 'Website',
            ]);

        ContractCharges::factory()->for($matchingContract)->create([
            'title' => 'LDW Insurance',
            'type' => 'insurance',
            'amount' => 120,
            'description' => '3 day(s)',
        ]);

        Payment::factory()->for($matchingContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 600,
            'amount_in_aed' => 600,
            'payment_date' => '2025-02-15',
        ]);
        Payment::factory()->for($matchingContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'security_deposit',
            'currency' => 'AED',
            'amount' => 300,
            'amount_in_aed' => 300,
            'payment_date' => '2025-02-15',
        ]);
        Payment::factory()->for($matchingContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'fine',
            'currency' => 'AED',
            'amount' => 50,
            'amount_in_aed' => 50,
            'payment_date' => '2025-02-18',
        ]);
        Payment::factory()->for($matchingContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'discount',
            'currency' => 'AED',
            'amount' => 10,
            'amount_in_aed' => 10,
            'payment_date' => '2025-02-15',
        ]);
        Payment::factory()->for($matchingContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'payment_back',
            'currency' => 'AED',
            'amount' => 20,
            'amount_in_aed' => 20,
            'payment_date' => '2025-02-19',
        ]);

        Contract::factory()->create([
            'created_at' => Carbon::parse('2025-01-01 09:00:00'),
            'pickup_date' => Carbon::parse('2025-01-05 10:00:00'),
            'return_date' => Carbon::parse('2025-01-07 10:00:00'),
            'current_status' => 'complete',
        ]);

        $report = $this->service->customerRequests([
            'search' => 'Sara',
            'date_field' => 'created_at',
            'date_from' => '2025-02-01',
            'date_to' => '2025-02-28',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(1, $report['summary']['matching_contracts']);
        $this->assertSame(1000.0, $report['summary']['gross_contract_value']);
        $this->assertSame(930.0, $report['summary']['recorded_payments']);
        $this->assertSame(160.0, $report['summary']['outstanding_balance']);
        $this->assertSame(3.0, $report['summary']['average_rental_days']);
        $this->assertSame('LDW Insurance', $report['rows'][0]['selected_insurance']);
        $this->assertSame(160.0, $report['rows'][0]['remaining_balance']);
    }

    public function test_customer_balance_report_groups_contracts_and_marks_overdue_accounts(): void
    {
        Carbon::setTestNow('2025-04-15 12:00:00');

        $customer = Customer::factory()->create([
            'first_name' => 'Ali',
            'last_name' => 'Karimi',
            'gender' => 'male',
        ]);
        $car = Car::factory()->create();

        $overdueContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->create([
                'pickup_date' => Carbon::parse('2025-04-01 10:00:00'),
                'return_date' => Carbon::parse('2025-04-03 10:00:00'),
                'current_status' => 'payment',
                'total_price' => 1000,
            ]);
        Payment::factory()->for($overdueContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 400,
            'amount_in_aed' => 400,
            'payment_date' => '2025-04-02',
        ]);

        $settledContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->create([
                'pickup_date' => Carbon::parse('2025-04-05 10:00:00'),
                'return_date' => Carbon::parse('2025-04-07 10:00:00'),
                'current_status' => 'complete',
                'total_price' => 300,
            ]);
        Payment::factory()->for($settledContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 300,
            'amount_in_aed' => 300,
            'payment_date' => '2025-04-05',
        ]);

        $report = $this->service->customerBalances([
            'search' => 'Ali',
            'date_field' => 'pickup_date',
            'date_from' => '2025-04-01',
            'date_to' => '2025-04-30',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame('overdue', $report['rows'][0]['status']);
        $this->assertSame(600.0, $report['rows'][0]['outstanding_balance']);
        $this->assertSame(600.0, $report['summary']['total_outstanding']);
        $this->assertSame(1, $report['summary']['overdue_customers']);
        $this->assertStringContainsString((string) $overdueContract->id, $report['rows'][0]['open_contract_ids']);

        Carbon::setTestNow();
    }

    public function test_customer_balance_report_ignores_cancelled_contracts(): void
    {
        Carbon::setTestNow('2025-04-15 12:00:00');

        $customer = Customer::factory()->create([
            'first_name' => 'Mina',
            'last_name' => 'Cancelled',
        ]);
        $car = Car::factory()->create();

        $activeContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->status('payment')
            ->create([
                'pickup_date' => Carbon::parse('2025-04-01 10:00:00'),
                'return_date' => Carbon::parse('2025-04-03 10:00:00'),
                'total_price' => 1000,
            ]);

        Payment::factory()->for($activeContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 400,
            'amount_in_aed' => 400,
            'payment_date' => '2025-04-02',
        ]);

        $cancelledContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->status('cancelled')
            ->create([
                'pickup_date' => Carbon::parse('2025-04-05 10:00:00'),
                'return_date' => Carbon::parse('2025-04-07 10:00:00'),
                'total_price' => 7000,
            ]);

        $report = $this->service->customerBalances([
            'search' => 'Mina',
            'date_field' => 'pickup_date',
            'date_from' => '2025-04-01',
            'date_to' => '2025-04-30',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(600.0, $report['rows'][0]['outstanding_balance']);
        $this->assertSame(1000.0, $report['rows'][0]['gross_contract_value']);
        $this->assertSame((string) $activeContract->id, $report['rows'][0]['open_contract_ids']);
        $this->assertStringNotContainsString((string) $cancelledContract->id, $report['rows'][0]['open_contract_ids']);
        $this->assertSame(600.0, $report['summary']['total_outstanding']);

        Carbon::setTestNow();
    }

    public function test_fleet_performance_report_calculates_utilization_and_revenue_in_window(): void
    {
        $car = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
            'plate_number' => 'UTIL-9001',
        ]);

        $firstContract = Contract::factory()->for($car)->create([
            'pickup_date' => Carbon::parse('2025-03-01 10:00:00'),
            'return_date' => Carbon::parse('2025-03-04 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 900,
        ]);
        $secondContract = Contract::factory()->for($car)->create([
            'pickup_date' => Carbon::parse('2025-03-06 10:00:00'),
            'return_date' => Carbon::parse('2025-03-08 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 600,
        ]);

        Payment::factory()->for($firstContract)->for($car)->paid()->create([
            'customer_id' => $firstContract->customer_id,
            'payment_type' => 'rental_fee',
            'currency' => 'AED',
            'amount' => 900,
            'amount_in_aed' => 900,
            'payment_date' => '2025-03-01',
        ]);

        $otherFleetCar = Car::factory()->create([
            'ownership_type' => 'liverpool',
            'is_company_car' => false,
        ]);

        Contract::factory()->for($otherFleetCar)->create([
            'pickup_date' => Carbon::parse('2025-03-02 10:00:00'),
            'return_date' => Carbon::parse('2025-03-05 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 400,
        ]);

        $report = $this->service->fleetPerformance([
            'date_from' => '2025-03-01',
            'date_to' => '2025-03-10',
            'ownership' => 'company',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(1500.0, $report['rows'][0]['revenue']);
        $this->assertSame(5.0, $report['rows'][0]['booked_days']);
        $this->assertSame(50.0, $report['rows'][0]['utilization_pct']);
        $this->assertSame(1500.0, $report['summary']['revenue']);
    }

    public function test_payment_collections_report_filters_unpaid_records_and_summarizes_amounts(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Mina',
            'last_name' => 'Jones',
            'gender' => 'female',
        ]);
        $car = Car::factory()->create();
        $contract = Contract::factory()->for($customer)->for($car)->create();

        Payment::factory()->for($contract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'approval_status' => 'approved',
            'currency' => 'AED',
            'amount' => 700,
            'amount_in_aed' => 700,
            'payment_date' => '2025-05-01',
        ]);
        Payment::factory()->for($contract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'payment_back',
            'approval_status' => 'approved',
            'currency' => 'AED',
            'amount' => 50,
            'amount_in_aed' => 50,
            'payment_date' => '2025-05-02',
        ]);
        Payment::factory()->for($contract)->for($customer)->for($car)->unpaid()->create([
            'payment_type' => 'security_deposit',
            'approval_status' => 'pending',
            'currency' => 'AED',
            'amount' => 300,
            'amount_in_aed' => 300,
            'payment_date' => '2025-05-03',
        ]);

        $report = $this->service->paymentCollections([
            'search' => 'Mina',
            'date_field' => 'payment_date',
            'date_from' => '2025-05-01',
            'date_to' => '2025-05-31',
            'payment_state' => 'unpaid',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame('Security Deposit', $report['rows'][0]['payment_type_label']);
        $this->assertSame(300.0, $report['summary']['unpaid_amount']);
        $this->assertSame(300.0, $report['summary']['net_recorded_payments']);
    }
}
