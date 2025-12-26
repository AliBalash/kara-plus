<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $pickup = $this->faker->dateTimeBetween('-3 days', 'now');
        $return = (clone $pickup)->modify('+'. $this->faker->numberBetween(1, 14).' days');

        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'car_id' => Car::factory(),
            'agent_id' => Agent::factory(),
            'submitted_by_name' => $this->faker->name,
            'pickup_date' => $pickup,
            'pickup_location' => $this->faker->city,
            'return_location' => $this->faker->city,
            'return_date' => $return,
            'total_price' => $this->faker->randomFloat(2, 1000, 10000),
            'kardo_required' => $this->faker->boolean,
            'current_status' => 'pending',
            'notes' => $this->faker->sentence,
            'meta' => [],
            'payment_on_delivery' => $this->faker->boolean,
            'used_daily_rate' => $this->faker->randomFloat(2, 100, 1000),
            'discount_note' => $this->faker->optional()->sentence,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['current_status' => $status]);
    }

    public function withoutPickupDate(): static
    {
        return $this->state(fn () => ['pickup_date' => null]);
    }
}
