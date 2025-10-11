<?php

namespace Database\Factories;

use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarModelFactory extends Factory
{
    protected $model = CarModel::class;

    public function definition(): array
    {
        return [
            'brand' => $this->faker->company, // برند خودرو
            'model' => $this->faker->word, // مدل خودرو
            'brand_icon' => null,
            'is_featured' => false,
        ];
    }
}
