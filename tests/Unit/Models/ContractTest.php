<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected function createBaselineContract(array $overrides = []): Contract
    {
        $user = $overrides['user'] ?? User::factory()->create();
        $customer = $overrides['customer'] ?? Customer::factory()->create();
        $car = $overrides['car'] ?? Car::factory()->create();

        return Contract::factory()
            ->for($user)
            ->for($customer)
            ->for($car)
            ->create(array_diff_key($overrides, array_flip(['user', 'customer', 'car'])));
    }

    public function test_calculate_remaining_balance_accounts_for_all_payment_types(): void
    {
        $contract = $this->createBaselineContract(['total_price' => 1500]);
        $customer = $contract->customer;
        $car = $contract->car;
        $user = $contract->user;

        $payments = collect([
            ['payment_type' => 'rental_fee', 'amount_in_aed' => 600],
            ['payment_type' => 'discount', 'amount_in_aed' => 100],
            ['payment_type' => 'security_deposit', 'amount_in_aed' => 200],
            ['payment_type' => 'fine', 'amount_in_aed' => 150],
            ['payment_type' => 'salik_4_aed', 'amount_in_aed' => 20], // 5 trips
            ['payment_type' => 'salik_6_aed', 'amount_in_aed' => 18], // 3 trips
            ['payment_type' => 'salik_other_revenue', 'amount_in_aed' => 8],
            ['payment_type' => 'salik', 'amount_in_aed' => 40],
            ['payment_type' => 'parking', 'amount_in_aed' => 30],
            ['payment_type' => 'damage', 'amount_in_aed' => 70],
            ['payment_type' => 'payment_back', 'amount_in_aed' => 50],
            ['payment_type' => 'carwash', 'amount_in_aed' => 25],
            ['payment_type' => 'fuel', 'amount_in_aed' => 40],
        ]);

        $payments->each(function (array $attributes) use ($contract, $customer, $car, $user) {
            Payment::factory()
                ->for($contract)
                ->for($customer)
                ->for($car)
                ->for($user)
                ->create(array_merge($attributes, [
                    'amount' => $attributes['amount_in_aed'],
                    'is_paid' => true,
                ]));
        });

        $effectivePaid = 600 - 50;
        $salikTripCharges = 20 + 18; // 38 AED from salik 4/6 entries
        $salikOtherRevenue = 8;
        $legacySalik = 40;

        $expectedBalance = 1500 - ($effectivePaid + 100 + 200)
            + (150 + $salikTripCharges + $salikOtherRevenue + $legacySalik + 25 + 40 + 30 + 70);

        $this->assertEquals($expectedBalance, $contract->fresh()->calculateRemainingBalance());

        $contractWithPayments = $contract->fresh()->load('payments');
        $this->assertEquals(
            $expectedBalance,
            $contractWithPayments->calculateRemainingBalance($contractWithPayments->payments)
        );
    }

    public function test_is_active_matches_configured_statuses(): void
    {
        foreach (['assigned', 'under_review', 'delivery'] as $status) {
            $contract = $this->createBaselineContract(['current_status' => $status]);
            $this->assertTrue($contract->isActive(), "Expected status {$status} to be active");
        }

        $inactiveContract = $this->createBaselineContract(['current_status' => 'complete']);
        $this->assertFalse($inactiveContract->isActive());
    }

    public function test_is_completed_returns_true_only_for_complete_status(): void
    {
        $completed = $this->createBaselineContract(['current_status' => 'complete']);
        $ongoing = $this->createBaselineContract(['current_status' => 'reserved']);

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($ongoing->isCompleted());
    }

    public function test_change_status_persists_history_and_updates_contract_fields(): void
    {
        Carbon::setTestNow('2025-01-01 10:00:00');
        $contract = $this->createBaselineContract(['current_status' => 'pending']);
        $userId = $contract->user_id;

        Carbon::setTestNow('2025-01-03 14:30:00');
        $contract->changeStatus('complete', $userId, 'Finished successfully');

        $contract->refresh();

        $this->assertEquals('complete', $contract->current_status);
        $this->assertEquals('2025-01-03 14:30:00', $contract->return_date->format('Y-m-d H:i:s'));

        $latestStatus = $contract->statuses()->latest('id')->first();
        $this->assertNotNull($latestStatus);
        $this->assertEquals('complete', $latestStatus->status);
        $this->assertEquals($userId, $latestStatus->user_id);
        $this->assertEquals('Finished successfully', $latestStatus->notes);

        Carbon::setTestNow();
    }

    public function test_calculate_total_price_uses_car_daily_rate_and_date_difference(): void
    {
        Carbon::setTestNow('2025-02-01 00:00:00');

        $pickup = Carbon::now()->subDays(3);
        $return = Carbon::now();

        $contract = $this->createBaselineContract([
            'pickup_date' => $pickup,
            'return_date' => $return,
            'total_price' => 0,
        ]);

        $contract->setRelation('car', $contract->car);
        $contract->car->setAttribute('price_per_day', 250);

        $this->assertEquals(3 * 250, $contract->calculateTotalPrice());

        Carbon::setTestNow();
    }
}
