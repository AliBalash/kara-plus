<?php

namespace Tests\Feature\Reports;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\Lead;
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
                'used_daily_rate' => 250,
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
        $this->assertSame(250.0, $report['rows'][0]['rental_rate']);
        $this->assertSame(160.0, $report['rows'][0]['remaining_balance']);
        $this->assertContains('Rental Rate AED/Day', $report['export_headings']);
        $this->assertSame(250.0, $report['export_rows'][0][5]);
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

    public function test_first_time_customer_report_only_returns_customers_without_an_older_eligible_contract(): void
    {
        $car = Car::factory()->create();

        $existingCustomer = Customer::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Customer',
            'gender' => 'male',
        ]);
        Contract::factory()->for($existingCustomer)->for($car)->status('complete')->create([
            'pickup_date' => Carbon::parse('2025-06-20 10:00:00'),
            'return_date' => Carbon::parse('2025-06-23 10:00:00'),
            'created_at' => Carbon::parse('2025-06-10 09:00:00'),
            'total_price' => 700,
        ]);
        $excludedJulyContract = Contract::factory()->for($existingCustomer)->for($car)->status('complete')->create([
            'pickup_date' => Carbon::parse('2025-07-05 10:00:00'),
            'return_date' => Carbon::parse('2025-07-08 10:00:00'),
            'created_at' => Carbon::parse('2025-07-01 09:00:00'),
            'total_price' => 900,
        ]);

        $newCustomer = Customer::factory()->create([
            'first_name' => 'New',
            'last_name' => 'Customer',
            'gender' => 'female',
        ]);
        $includedJulyContract = Contract::factory()->for($newCustomer)->for($car)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-07-10 10:00:00'),
            'return_date' => Carbon::parse('2025-07-14 10:00:00'),
            'created_at' => Carbon::parse('2025-07-02 09:00:00'),
            'total_price' => 1200,
            'used_daily_rate' => 300,
        ]);

        $cancelledBeforeCustomer = Customer::factory()->create([
            'first_name' => 'Retry',
            'last_name' => 'Customer',
            'gender' => 'female',
        ]);
        Contract::factory()->for($cancelledBeforeCustomer)->for($car)->status('cancelled')->create([
            'pickup_date' => Carbon::parse('2025-06-15 10:00:00'),
            'return_date' => Carbon::parse('2025-06-16 10:00:00'),
            'created_at' => Carbon::parse('2025-06-01 09:00:00'),
            'total_price' => 500,
        ]);
        $includedAfterCancelledContract = Contract::factory()->for($cancelledBeforeCustomer)->for($car)->status('complete')->create([
            'pickup_date' => Carbon::parse('2025-07-18 10:00:00'),
            'return_date' => Carbon::parse('2025-07-20 10:00:00'),
            'created_at' => Carbon::parse('2025-07-03 09:00:00'),
            'total_price' => 800,
        ]);

        $report = $this->service->firstTimeCustomers([
            'date_field' => 'pickup_date',
            'date_from' => '2025-07-01',
            'date_to' => '2025-07-31',
        ]);

        $this->assertCount(2, $report['rows']);
        $this->assertSame(2, $report['summary']['new_customers']);
        $this->assertSame(2, $report['summary']['first_contracts']);
        $this->assertSame('Pickup Date', $report['rows'][0]['first_contract_basis']);
        $this->assertSame(
            collect([$includedAfterCancelledContract->id, $includedJulyContract->id])->sort()->values()->all(),
            collect($report['rows'])->pluck('contract_id')->sort()->values()->all()
        );
        $this->assertNotContains($excludedJulyContract->id, collect($report['rows'])->pluck('contract_id')->all());
        $this->assertContains('First Contract Date', $report['export_headings']);
    }

    public function test_lead_source_report_summarizes_channels_and_conversion_in_date_window(): void
    {
        Carbon::setTestNow('2026-07-15 10:00:00');

        $assignedUser = User::factory()->create(['first_name' => 'Leila', 'last_name' => 'Owner']);
        $createdBy = User::factory()->create(['first_name' => 'Omid', 'last_name' => 'Creator']);
        $convertedBy = User::factory()->create(['first_name' => 'Nima', 'last_name' => 'Closer']);
        $customer = Customer::factory()->create([
            'first_name' => 'Converted',
            'last_name' => 'Customer',
            'gender' => 'female',
        ]);
        $model = CarModel::factory()->create([
            'brand' => 'BMW',
            'model' => 'X5',
        ]);

        Lead::create([
            'first_name' => 'Sara',
            'last_name' => 'Ads',
            'phone' => '+971501111111',
            'email' => 'sara@example.com',
            'source' => 'google_ads',
            'discovery_source' => 'Summer campaign',
            'requested_brand' => 'BMW',
            'requested_model_id' => $model->id,
            'request_date' => '2026-07-10',
            'pickup_date' => '2026-07-20',
            'return_date' => '2026-07-25',
            'priority' => Lead::PRIORITY_HIGH,
            'status' => Lead::STATUS_FOLLOW_UP,
            'assigned_to' => $assignedUser->id,
            'created_by' => $createdBy->id,
            'next_follow_up_at' => '2026-07-14 09:00:00',
            'last_contacted_at' => '2026-07-11 12:00:00',
            'notes' => 'Needs SUV',
            'created_at' => '2026-07-10 08:30:00',
            'updated_at' => '2026-07-10 08:30:00',
        ]);

        Lead::create([
            'first_name' => 'Mina',
            'last_name' => 'Chat',
            'phone' => '+971502222222',
            'email' => 'mina@example.com',
            'source' => 'whatsapp',
            'discovery_source' => 'Referral',
            'requested_brand' => 'BMW',
            'requested_model_id' => $model->id,
            'request_date' => '2026-07-12',
            'priority' => Lead::PRIORITY_URGENT,
            'status' => Lead::STATUS_CONVERTED,
            'assigned_to' => $assignedUser->id,
            'created_by' => $createdBy->id,
            'customer_id' => $customer->id,
            'converted_by' => $convertedBy->id,
            'converted_at' => '2026-07-13 15:00:00',
            'created_at' => '2026-07-12 09:00:00',
            'updated_at' => '2026-07-13 15:00:00',
        ]);

        Lead::create([
            'first_name' => 'Outside',
            'last_name' => 'Window',
            'phone' => '+971503333333',
            'source' => 'google_ads',
            'request_date' => '2026-06-25',
            'priority' => Lead::PRIORITY_NORMAL,
            'status' => Lead::STATUS_NEW,
            'created_by' => $createdBy->id,
            'created_at' => '2026-06-25 10:00:00',
            'updated_at' => '2026-06-25 10:00:00',
        ]);

        $report = $this->service->leadSources([
            'date_field' => 'request_date',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        $this->assertCount(2, $report['rows']);
        $this->assertSame(2, $report['summary']['matching_leads']);
        $this->assertSame(1, $report['summary']['converted_leads']);
        $this->assertSame(50.0, $report['summary']['conversion_rate']);
        $this->assertSame(1, $report['summary']['due_follow_ups']);
        $this->assertSame(2, $report['summary']['unique_channels']);
        $this->assertContains('Google Ads', collect($report['rows'])->pluck('source_label')->all());
        $this->assertContains('WhatsApp', collect($report['rows'])->pluck('source_label')->all());
        $this->assertContains('Communication Channel', $report['export_headings']);
        $this->assertSame('All channels', $report['filter_summary']['Communication Channel']);
        $this->assertCount(2, $report['extra_sheets']);

        Carbon::setTestNow();
    }

    public function test_monthly_contracts_use_28_day_threshold_and_count_only_returns_ending_this_month_for_current_month_summary(): void
    {
        Carbon::setTestNow('2025-05-15 09:00:00');

        $endingThisMonthCar = Car::factory()->create();
        Contract::factory()->for($endingThisMonthCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-04-10 10:00:00'),
            'return_date' => Carbon::parse('2025-05-20 10:00:00'),
        ]);
        Contract::factory()->for($endingThisMonthCar)->status('awaiting_return')->create([
            'pickup_date' => Carbon::parse('2025-04-12 10:00:00'),
            'return_date' => Carbon::parse('2025-05-25 10:00:00'),
        ]);

        $overlapOnlyCar = Car::factory()->create();
        Contract::factory()->for($overlapOnlyCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-05-10 10:00:00'),
            'return_date' => Carbon::parse('2025-06-10 10:00:00'),
        ]);

        $thresholdCar = Car::factory()->create();
        Contract::factory()->for($thresholdCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-05-01 10:00:00'),
            'return_date' => Carbon::parse('2025-05-29 10:00:00'),
        ]);

        $endingSoonCar = Car::factory()->create();
        Contract::factory()->for($endingSoonCar)->status('awaiting_return')->create([
            'pickup_date' => Carbon::parse('2025-04-18 10:00:00'),
            'return_date' => Carbon::parse('2025-05-18 10:00:00'),
        ]);

        $belowThresholdCar = Car::factory()->create();
        Contract::factory()->for($belowThresholdCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-05-01 10:00:00'),
            'return_date' => Carbon::parse('2025-05-28 10:00:00'),
        ]);

        $inactiveStatusCar = Car::factory()->create();
        Contract::factory()->for($inactiveStatusCar)->status('complete')->create([
            'pickup_date' => Carbon::parse('2025-04-01 10:00:00'),
            'return_date' => Carbon::parse('2025-05-30 10:00:00'),
        ]);

        $report = $this->service->monthlyContracts();

        $this->assertSame(4, $report['summary']['total_monthly_contracts']);
        $this->assertSame(3, $report['summary']['current_month_monthly_contracts']);
        $this->assertSame(1, $report['summary']['ending_in_three_days_or_less']);

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

    public function test_fleet_performance_report_normalizes_reversed_date_window(): void
    {
        $car = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);

        Contract::factory()->for($car)->create([
            'pickup_date' => Carbon::parse('2025-03-05 10:00:00'),
            'return_date' => Carbon::parse('2025-03-06 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 500,
        ]);

        $report = $this->service->fleetPerformance([
            'date_from' => '2025-03-10',
            'date_to' => '2025-03-01',
            'ownership' => 'company',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame('2025-03-01', $report['filter_summary']['Pickup Date From']);
        $this->assertSame('2025-03-10', $report['filter_summary']['Return Date To']);
    }

    public function test_fleet_performance_report_filters_cars_with_upcoming_reservations_in_next_x_days(): void
    {
        Carbon::setTestNow('2025-03-01 09:00:00');

        $inWindowCar = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);
        Contract::factory()->for($inWindowCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-03-03 10:00:00'),
            'return_date' => Carbon::parse('2025-03-05 10:00:00'),
            'total_price' => 700,
        ]);

        $outsideWindowCar = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);
        Contract::factory()->for($outsideWindowCar)->status('reserved')->create([
            'pickup_date' => Carbon::parse('2025-03-10 10:00:00'),
            'return_date' => Carbon::parse('2025-03-12 10:00:00'),
            'total_price' => 800,
        ]);

        $report = $this->service->fleetPerformance([
            'date_from' => '2025-02-01',
            'date_to' => '2025-03-01',
            'ownership' => 'company',
            'reservation_days_ahead' => '5',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($inWindowCar->id, $report['rows'][0]['car_id']);
        $this->assertSame(1, $report['rows'][0]['contracts_count']);
        $this->assertSame('5', $report['filter_summary']['Reserved In Next Days']);

        Carbon::setTestNow();
    }

    public function test_fleet_performance_report_filters_by_pickup_from_and_return_to_fields(): void
    {
        $matchingCar = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);
        Contract::factory()->for($matchingCar)->create([
            'pickup_date' => Carbon::parse('2025-03-05 10:00:00'),
            'return_date' => Carbon::parse('2025-03-08 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 500,
        ]);

        $pickupBeforeFromCar = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);
        Contract::factory()->for($pickupBeforeFromCar)->create([
            'pickup_date' => Carbon::parse('2025-03-01 10:00:00'),
            'return_date' => Carbon::parse('2025-03-06 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 500,
        ]);

        $returnAfterToCar = Car::factory()->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);
        Contract::factory()->for($returnAfterToCar)->create([
            'pickup_date' => Carbon::parse('2025-03-06 10:00:00'),
            'return_date' => Carbon::parse('2025-03-12 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 500,
        ]);

        $report = $this->service->fleetPerformance([
            'date_from' => '2025-03-05',
            'date_to' => '2025-03-10',
            'ownership' => 'company',
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($matchingCar->id, $report['rows'][0]['car_id']);
    }

    public function test_fleet_performance_report_filters_by_operational_status_and_unavailable_reason(): void
    {
        $maintenanceCar = Car::factory()->unavailable(Car::UNAVAILABILITY_REASON_MAINTENANCE)->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);

        Contract::factory()->for($maintenanceCar)->create([
            'pickup_date' => Carbon::parse('2025-03-05 10:00:00'),
            'return_date' => Carbon::parse('2025-03-08 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 500,
        ]);

        $insuranceCar = Car::factory()->unavailable(Car::UNAVAILABILITY_REASON_INSURANCE)->create([
            'ownership_type' => 'company',
            'is_company_car' => true,
        ]);

        Contract::factory()->for($insuranceCar)->create([
            'pickup_date' => Carbon::parse('2025-03-06 10:00:00'),
            'return_date' => Carbon::parse('2025-03-07 10:00:00'),
            'current_status' => 'complete',
            'total_price' => 450,
        ]);

        $report = $this->service->fleetPerformance([
            'date_from' => '2025-03-01',
            'date_to' => '2025-03-10',
            'ownership' => 'company',
            'status' => Car::STATUS_UNAVAILABLE,
            'unavailability_reason' => Car::UNAVAILABILITY_REASON_MAINTENANCE,
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($maintenanceCar->id, $report['rows'][0]['car_id']);
        $this->assertSame('Maintenance', $report['rows'][0]['unavailability_reason_label']);
        $this->assertSame('Unavailable', $report['filter_summary']['Operational Status']);
        $this->assertSame('Maintenance', $report['filter_summary']['Unavailable Reason']);
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
