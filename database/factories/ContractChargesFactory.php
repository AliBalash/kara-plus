<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractCharges;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractChargesFactory extends Factory
{
    protected $model = ContractCharges::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'title' => $this->faker->words(2, true),
            'amount' => $this->faker->randomFloat(2, 50, 500),
            'type' => $this->faker->randomElement(['addon', 'discount', 'fee']),
            'description' => $this->faker->optional()->sentence,
        ];
    }
}

