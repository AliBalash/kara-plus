<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $paymentType = $this->faker->randomElement([
            'rental_fee',
            'security_deposit',
            'discount',
            'fine',
            'salik',
            'salik_4_aed',
            'salik_6_aed',
            'salik_other_revenue',
            'parking',
            'damage',
            'payment_back',
            'carwash',
            'fuel',
            'no_deposit_fee',
        ]);

        $amountInAed = $this->faker->randomFloat(2, 100, 2000);

        if (in_array($paymentType, ['salik_4_aed', 'salik_6_aed', 'salik_other_revenue'], true)) {
            $unit = match ($paymentType) {
                'salik_4_aed' => 4,
                'salik_6_aed' => 6,
                'salik_other_revenue' => 1,
            };

            $tripCount = $this->faker->numberBetween(1, 25);
            $amountInAed = $tripCount * $unit;
        }

        $currency = in_array($paymentType, ['salik', 'salik_4_aed', 'salik_6_aed', 'salik_other_revenue'], true)
            ? 'AED'
            : $this->faker->randomElement(['AED', 'USD', 'EUR']);

        return [
            'contract_id' => Contract::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'car_id' => Car::factory(),
            'amount' => $currency === 'AED' ? $amountInAed : $this->faker->randomFloat(2, 100, 2000),
            'amount_in_aed' => $amountInAed,
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'ticket']),
            'currency' => $currency,
            'payment_type' => $paymentType,
            'description' => $this->faker->sentence,
            'payment_date' => $this->faker->date(),
            'is_refundable' => $this->faker->boolean,
            'is_paid' => $this->faker->boolean,
            'rate' => $this->faker->randomFloat(2, 1, 5),
            'receipt' => null,
            'approval_status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['is_paid' => true]);
    }

    public function unpaid(): static
    {
        return $this->state(fn () => ['is_paid' => false]);
    }
}
