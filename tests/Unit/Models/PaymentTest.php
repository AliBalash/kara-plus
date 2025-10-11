<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Car $car;
    protected Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->car = Car::factory()->create();
        $this->contract = Contract::factory()
            ->for($this->user)
            ->for($this->customer)
            ->for($this->car)
            ->create();
    }

    protected function createPayment(array $attributes): Payment
    {
        return Payment::factory()
            ->for($this->contract)
            ->for($this->customer)
            ->for($this->car)
            ->for($this->user)
            ->create($attributes);
    }

    public function test_scoped_collections_return_expected_records(): void
    {
        $paid = $this->createPayment(['is_paid' => true, 'is_refundable' => false]);
        $unpaid = $this->createPayment(['is_paid' => false, 'is_refundable' => false]);
        $refundable = $this->createPayment(['is_paid' => true, 'is_refundable' => true]);

        $this->assertTrue(Payment::getPaidPayments()->contains($paid));
        $this->assertFalse(Payment::getPaidPayments()->contains($unpaid));

        $this->assertTrue(Payment::getUnpaidPayments()->contains($unpaid));
        $this->assertFalse(Payment::getUnpaidPayments()->contains($paid));

        $this->assertTrue(Payment::getRefundablePayments()->contains($refundable));
        $this->assertFalse(Payment::getRefundablePayments()->contains($paid));
    }

    public function test_it_calculates_total_payment_amounts_for_contract_and_customer(): void
    {
        $this->createPayment(['amount' => 300, 'amount_in_aed' => 300]);
        $this->createPayment(['amount' => 450, 'amount_in_aed' => 450]);

        $this->assertEquals(750, Payment::getTotalPaymentsForContract($this->contract->id));
        $this->assertEquals(750, Payment::getTotalPaymentsForCustomer($this->customer->id));
    }
}

