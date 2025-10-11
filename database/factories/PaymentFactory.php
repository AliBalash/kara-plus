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
        return [
            'contract_id' => Contract::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'car_id' => Car::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 2000),
            'amount_in_aed' => $this->faker->randomFloat(2, 100, 2000),
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'ticket']),
            'currency' => $this->faker->randomElement(['AED', 'USD', 'EUR']),
            'payment_type' => $this->faker->randomElement(['rental_fee', 'security_deposit', 'discount', 'fine', 'salik', 'parking', 'damage']),
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
