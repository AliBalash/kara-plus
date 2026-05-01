<?php

namespace Tests\Feature\Livewire\Customer;

use App\Livewire\Pages\Panel\Expert\Customer\CustomerDebt;
use App\Livewire\Pages\Panel\Expert\Customer\CustomerDebtorList;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class CustomerDebtTest extends TestCase
{
    use RefreshDatabase;

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

    protected function invokeProtected(object $object, string $method): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($object);
    }
}
