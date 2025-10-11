<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractStatusFactory extends Factory
{
    protected $model = ContractStatus::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'status' => $this->faker->randomElement([
                'pending',
                'assigned',
                'under_review',
                'reserved',
                'delivery',
                'agreement_inspection',
                'awaiting_return',
                'returned',
                'payment',
                'complete',
                'cancelled',
                'rejected',
            ]),
            'user_id' => User::factory(),
            'notes' => $this->faker->optional()->sentence,
        ];
    }
}

