<?php

namespace Tests\Feature\Livewire\Customer;

use App\Livewire\Pages\Panel\Expert\Customer\CustomerDebt;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerDebtorList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\ContractBalanceTransfer;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class CustomerDebtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_customer_debt_ignores_cancelled_contracts(): void
    {
        $customer = Customer::factory()->create();
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
            'amount' => 400,
            'amount_in_aed' => 400,
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

        $component = app(CustomerDebt::class);
        $component->mount($customer->id);

        $this->assertSame(600.0, $component->debtTotals['total_outstanding']);
        $this->assertSame(0, $component->debtTotals['open_contracts']);
        $this->assertSame(1, $component->debtTotals['overdue_contracts']);
        $this->assertSame([$activeContract->id], collect($component->debtContracts)->pluck('id')->all());
        $this->assertNotContains($cancelledContract->id, collect($component->debtContracts)->pluck('id')->all());
    }

    public function test_customer_debtor_list_ignores_cancelled_contracts(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Debt',
            'last_name' => 'Customer',
        ]);
        $car = Car::factory()->create();

        $activeContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->status('payment')
            ->create(['total_price' => 1000]);

        Payment::factory()->for($activeContract)->for($customer)->for($car)->paid()->create([
            'payment_type' => 'rental_fee',
            'amount' => 400,
            'amount_in_aed' => 400,
        ]);

        $cancelledContract = Contract::factory()
            ->for($customer)
            ->for($car)
            ->status('cancelled')
            ->create(['total_price' => 7000]);

        $component = app(CustomerDebtorList::class);
        $component->mount();

        $summary = $this->invokeProtected($component, 'buildDebtorSummary');

        $this->assertCount(1, $summary);
        $this->assertSame('Debt Customer', $summary[0]['name']);
        $this->assertSame(600.0, $summary[0]['total_outstanding']);
        $this->assertSame($activeContract->id, $summary[0]['primary_contract']['id']);
        $this->assertNotSame($cancelledContract->id, $summary[0]['primary_contract']['id']);
    }

    public function test_date_window_only_aggregates_matching_eligible_contracts_and_preserves_credits(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));
        $customer = Customer::factory()->create(['first_name' => 'WindowMatch']);
        $first = $this->debtContract($customer, 1000, ['pickup_date' => '2026-04-01 00:00:00']);
        $this->debtContract($customer, 300, ['pickup_date' => '2026-04-30 23:59:59']);
        $this->debtContract($customer, 9000, ['pickup_date' => '2026-03-31 23:59:59']);
        $this->debtContract($customer, 8000, ['pickup_date' => '2026-05-01 00:00:00']);
        foreach (Contract::CUSTOMER_BALANCE_EXCLUDED_STATUSES as $status) {
            $this->debtContract($customer, 7000, ['current_status' => $status]);
        }
        $credit = $this->debtContract($customer, 100);
        $this->payRental($first, 400);
        $this->payRental($credit, 500);
        $outside = Customer::factory()->create();
        $this->debtContract($outside, 5000, ['pickup_date' => '2026-05-01 00:00:00']);
        $cancelledOnly = Customer::factory()->create();
        $this->debtContract($cancelledOnly, 6000, ['current_status' => 'cancelled']);

        Livewire::test(CustomerDebtorList::class)
            ->set('filterInputs.dateFrom', '2026-04-01')
            ->set('filterInputs.dateTo', '2026-04-30')
            ->assertSet('dateFrom', '')
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 2)
            ->call('applyFilters')
            ->assertHasNoErrors()
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 1
                && $rows->first()['total_outstanding'] === 900.0
                && $rows->first()['credit'] === 400.0
                && $rows->first()['primary_contract']['id'] === $first->id)
            ->assertViewHas('overview', fn ($overview) => $overview['total_debt'] === 900.0
                && $overview['credit'] === 400.0 && $overview['overdue'] === 2);
    }

    #[DataProvider('dateWindows')]
    public function test_date_bases_and_independent_inclusive_boundaries(string $basis, string $from, string $to, array $expected): void
    {
        $customer = Customer::factory()->create();
        $dates = ['2026-03-31 23:59:59', '2026-04-01 00:00:00', '2026-04-30 23:59:59', '2026-05-01 00:00:00'];
        foreach ($dates as $i => $date) {
            $this->debtContract($customer, ($i + 1) * 100, [
                $basis => $date,
                $basis === 'pickup_date' ? 'created_at' : 'pickup_date' => '2025-01-01 10:00:00',
            ]);
        }

        Livewire::test(CustomerDebtorList::class)
            ->set('filterInputs.dateField', $basis)
            ->set('filterInputs.dateFrom', $from)
            ->set('filterInputs.dateTo', $to)
            ->call('applyFilters')
            ->assertHasNoErrors()
            ->assertViewHas('debtors', fn ($rows) => $rows->first()['total_outstanding'] === (float) array_sum($expected));
    }

    public static function dateWindows(): array
    {
        return [
            ['pickup_date', '2026-04-01', '2026-04-30', [200, 300]],
            ['created_at', '2026-04-01', '2026-04-30', [200, 300]],
            ['pickup_date', '2026-04-01', '', [200, 300, 400]],
            ['created_at', '', '2026-04-30', [100, 200, 300]],
        ];
    }

    #[DataProvider('amountWindows')]
    public function test_amount_boundaries_apply_to_positive_outstanding(string $min, string $max, string $status, array $expected): void
    {
        foreach ([100.25, 200.50, 300.75, 0] as $balance) {
            $customer = Customer::factory()->create();
            $contract = $this->debtContract($customer, 1000);
            $this->payRental($contract, $balance === 0 ? 1100 : 1000 - $balance);
        }

        Livewire::test(CustomerDebtorList::class)
            ->set('status', $status)
            ->set('filterInputs.minOutstanding', $min)
            ->set('filterInputs.maxOutstanding', $max)
            ->call('applyFilters')
            ->assertHasNoErrors()
            ->assertViewHas('debtors', fn ($rows) => $rows->pluck('total_outstanding')->all() === $expected)
            ->assertViewHas('overview', fn ($overview) => $overview['customers'] === count($expected)
                && $overview['total_debt'] === (float) array_sum($expected));
    }

    public static function amountWindows(): array
    {
        return [
            'default' => ['', '', 'all', [300.75, 200.5, 100.25]],
            'minimum' => ['200.50', '', 'all', [300.75, 200.5]],
            'maximum' => ['', '200.50', 'all', [200.5, 100.25]],
            'both exact' => ['200.50', '200.50', 'all', [200.5]],
            'zero minimum' => ['0', '', 'all', [300.75, 200.5, 100.25]],
            'zero maximum' => ['', '0', 'all', []],
            'credit zero' => ['0', '0.00', 'credit', [0.0]],
            'credit positive minimum' => ['0.01', '', 'credit', []],
        ];
    }

    public function test_combined_filters_overview_pagination_and_reset(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));
        foreach (range(1, 12) as $i) {
            $customer = Customer::factory()->create(['first_name' => 'Matched', 'last_name' => (string) $i]);
            $this->debtContract($customer, 100 + $i, ['return_date' => '2026-07-01 10:00:00']);
        }
        $other = Customer::factory()->create(['first_name' => 'Other', 'last_name' => 'Customer']);
        $this->debtContract($other, 105, ['return_date' => '2026-07-01 10:00:00']);
        $tooLarge = Customer::factory()->create(['first_name' => 'Matched']);
        $this->debtContract($tooLarge, 800, ['return_date' => '2026-07-01 10:00:00']);
        $overdue = Customer::factory()->create(['first_name' => 'Matched']);
        $this->debtContract($overdue, 105);
        // A search match outside the date window must not escape the scoped search/date constraints.
        $old = Customer::factory()->create(['first_name' => 'Matched']);
        $this->debtContract($old, 150, ['pickup_date' => '2025-01-01 10:00:00']);

        Livewire::test(CustomerDebtorList::class)
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 16 && $rows->count() === 10)
            ->call('setPage', 2)
            ->set('searchInput', 'Matched')
            ->set('status', 'open')
            ->set('filterInputs.dateFrom', '2026-04-01')
            ->set('filterInputs.dateTo', '2026-04-30')
            ->set('filterInputs.minOutstanding', '101')
            ->set('filterInputs.maxOutstanding', '112')
            ->call('applyFilters')
            ->assertSet('paginators.page', 1)
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 12 && $rows->count() === 10
                && $rows->first()['total_outstanding'] === 112.0)
            ->assertViewHas('overview', fn ($overview) => $overview['total_debt'] === 1278.0
                && $overview['customers'] === 12 && $overview['open'] === 12 && $overview['overdue'] === 0)
            ->call('setPage', 2)
            ->assertViewHas('debtors', fn ($rows) => $rows->count() === 2)
            ->set('status', 'credit')
            ->assertSet('paginators.page', 1)
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 0)
            ->assertSee('No customers match the applied filters.')
            ->call('resetFilters')
            ->assertSet('paginators.page', 1)
            ->assertSet('search', '')->assertSet('searchInput', '')->assertSet('status', 'all')
            ->assertSet('dateField', 'pickup_date')->assertSet('dateFrom', '')->assertSet('dateTo', '')
            ->assertSet('minOutstanding', '')->assertSet('maxOutstanding', '')
            ->assertHasNoErrors()
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 16);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_inputs_do_not_replace_applied_filters(array $inputs, string $error): void
    {
        Livewire::test(CustomerDebtorList::class)
            ->set('filterInputs.dateFrom', '2026-04-01')
            ->call('applyFilters')
            ->set('filterInputs', array_merge([
                'dateField' => 'pickup_date', 'dateFrom' => '', 'dateTo' => '',
                'minOutstanding' => '', 'maxOutstanding' => '',
            ], $inputs))
            ->call('applyFilters')
            ->assertHasErrors(['filterInputs.'.$error])
            ->assertSet('dateFrom', '2026-04-01');
    }

    public static function invalidFilters(): array
    {
        return [
            [['dateFrom' => '2026-02-30'], 'dateFrom'],
            [['dateFrom' => ['bad'], 'dateTo' => '2026-04-30'], 'dateFrom'],
            [['dateTo' => ['bad']], 'dateTo'],
            [['minOutstanding' => ['bad'], 'maxOutstanding' => '10'], 'minOutstanding'],
            [['dateTo' => 'not-a-date'], 'dateTo'],
            [['dateFrom' => '2026-05-01', 'dateTo' => '2026-04-01'], 'dateTo'],
            [['dateField' => 'unknown'], 'dateField'],
            [['minOutstanding' => '-1'], 'minOutstanding'],
            [['maxOutstanding' => '-0.01'], 'maxOutstanding'],
            [['minOutstanding' => '12.345'], 'minOutstanding'],
            [['maxOutstanding' => '1e3'], 'maxOutstanding'],
            [['minOutstanding' => 'abc'], 'minOutstanding'],
            [['minOutstanding' => '10', 'maxOutstanding' => '9.99'], 'maxOutstanding'],
        ];
    }

    public function test_url_filters_are_restored_and_malformed_parameters_are_safe(): void
    {
        $customer = Customer::factory()->create();
        $this->debtContract($customer, 250);
        Livewire::withQueryParams(['dateFrom' => '2026-04-01', 'maxOutstanding' => '250'])
            ->test(CustomerDebtorList::class)
            ->assertSet('filterInputs.dateFrom', '2026-04-01')
            ->assertSet('filterInputs.maxOutstanding', '250')
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 1);

        Livewire::withQueryParams([
            'search' => ['bad'], 'status' => ['bad'], 'dateField' => ['bad'],
            'dateFrom' => ['bad'], 'dateTo' => 'not-a-date',
            'minOutstanding' => ['bad'], 'maxOutstanding' => '-1',
        ])->test(CustomerDebtorList::class)
            ->assertHasErrors(['filterInputs.dateField', 'filterInputs.dateFrom', 'filterInputs.dateTo', 'filterInputs.minOutstanding', 'filterInputs.maxOutstanding'])
            ->assertSet('dateField', 'pickup_date')->assertSet('dateFrom', '')
            ->assertViewHas('debtors', fn ($rows) => $rows->total() === 1)
            ->call('resetFilters')->assertHasNoErrors();
    }

    public function test_transfers_across_date_window_are_preserved_without_per_contract_queries(): void
    {
        $customer = Customer::factory()->create();
        $inside = $this->debtContract($customer, 1000);
        $outside = $this->debtContract($customer, 1000, ['pickup_date' => '2025-01-01 10:00:00']);
        ContractBalanceTransfer::create([
            'customer_id' => $customer->id, 'from_contract_id' => $inside->id,
            'to_contract_id' => $outside->id, 'amount' => 200, 'currency' => 'AED',
            'created_by' => $inside->user_id, 'transferred_at' => '2026-04-15 12:00:00',
        ]);
        ContractBalanceTransfer::create([
            'customer_id' => $customer->id, 'from_contract_id' => $outside->id,
            'to_contract_id' => $inside->id, 'amount' => 50, 'currency' => 'AED',
            'created_by' => $inside->user_id, 'transferred_at' => '2026-04-15 12:00:00',
        ]);
        $component = app(CustomerDebtorList::class);
        $component->mount();
        $component->filterInputs['dateFrom'] = '2026-04-01';
        $component->applyFilters();
        DB::enableQueryLog();
        try {
            $summary = $this->invokeProtected($component, 'buildDebtorSummary');
            $transferQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'contract_balance_transfers'));
            $this->assertSame(850.0, $summary->first()['total_outstanding']);
            $this->assertCount(2, $transferQueries);
            $this->assertFalse($transferQueries->contains(fn ($query) => str_contains(strtolower($query['query']), 'sum(')));
        } finally {
            DB::disableQueryLog();
        }
    }

    private function debtContract(Customer $customer, float $total, array $attributes = []): Contract
    {
        return Contract::factory()->for($customer)->status('payment')->create(array_merge([
            'total_price' => $total, 'pickup_date' => '2026-04-15 10:00:00',
            'return_date' => '2026-04-20 10:00:00', 'created_at' => '2026-03-01 10:00:00',
        ], $attributes));
    }

    private function payRental(Contract $contract, float $amount): void
    {
        Payment::factory()->for($contract)->create([
            'customer_id' => $contract->customer_id, 'car_id' => $contract->car_id,
            'payment_type' => 'rental_fee', 'amount' => $amount, 'amount_in_aed' => $amount,
            'currency' => 'AED', 'is_paid' => true, 'approval_status' => 'approved',
            'payment_date' => '2026-04-16',
        ]);
    }

    protected function invokeProtected(object $object, string $method): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($object);
    }
}
