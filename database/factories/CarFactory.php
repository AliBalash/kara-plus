<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        return [
            'car_model_id' => CarModel::factory(),
            'plate_number' => strtoupper($this->faker->bothify('??-####')),
            'status' => $this->faker->randomElement(['available', 'reserved', 'under_maintenance']),
            'availability' => true,
            'mileage' => $this->faker->numberBetween(1_000, 100_000),
            'price_per_day_short' => $this->faker->randomFloat(2, 200, 1000),
            'price_per_day_mid' => $this->faker->randomFloat(2, 150, 800),
            'price_per_day_long' => $this->faker->randomFloat(2, 100, 600),
            'ldw_price_short' => $this->faker->randomFloat(2, 0, 100),
            'ldw_price_mid' => $this->faker->randomFloat(2, 0, 100),
            'ldw_price_long' => $this->faker->randomFloat(2, 0, 100),
            'scdw_price_short' => $this->faker->randomFloat(2, 0, 100),
            'scdw_price_mid' => $this->faker->randomFloat(2, 0, 100),
            'scdw_price_long' => $this->faker->randomFloat(2, 0, 100),
            'service_due_date' => $this->faker->dateTimeBetween('-1 month', '+3 months'),
            'damage_report' => null,
            'manufacturing_year' => $this->faker->numberBetween(2015, now()->year),
            'color' => $this->faker->safeColorName,
            'notes' => null,
            'chassis_number' => strtoupper($this->faker->bothify('??##########')),
            'gps' => $this->faker->boolean,
            'issue_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+1 years'),
            'passing_date' => $this->faker->dateTimeBetween('-1 years', 'now'),
            'passing_valid_for_days' => $this->faker->numberBetween(0, 365),
            'passing_status' => $this->faker->randomElement(['done', 'pending', 'failed']),
            'registration_valid_for_days' => $this->faker->numberBetween(0, 365),
            'registration_status' => $this->faker->randomElement(['done', 'pending', 'failed']),
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => [
            'status' => 'available',
            'availability' => true,
        ]);
    }
}

