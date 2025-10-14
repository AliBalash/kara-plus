<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\PickupDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupDocumentFactory extends Factory
{
    protected $model = PickupDocument::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'user_id' => User::factory(),
            'tars_contract' => 'PickupDocument/tars_contract_sample.jpg',
            'kardo_contract' => 'PickupDocument/kardo_contract_sample.jpg',
            'kardo_contract_number' => $this->faker->numerify('########'),
            'factor_contract' => null,
            'car_dashboard' => null,
            'car_inside_photos' => [],
            'car_outside_photos' => [],
            'fuelLevel' => (string) $this->faker->numberBetween(0, 100),
            'mileage' => $this->faker->numberBetween(1_000, 50_000),
            'note' => $this->faker->sentence,
            'driver_note' => $this->faker->sentence,
        ];
    }
}
