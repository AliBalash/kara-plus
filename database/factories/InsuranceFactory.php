<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Insurance;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceFactory extends Factory
{
    protected $model = Insurance::class;

    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'expiry_date' => $this->faker->dateTimeBetween('+1 week', '+1 year'),
            'valid_days' => $this->faker->numberBetween(1, 365),
            'status' => $this->faker->randomElement(['pending', 'done', 'failed']),
            'insurance_company' => $this->faker->company,
        ];
    }
}

