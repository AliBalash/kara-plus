<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'national_code' => $this->faker->unique()->numerify('##########'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'messenger_phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'birth_date' => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'passport_number' => $this->faker->unique()->bothify('???#####'),
            'passport_expiry_date' => $this->faker->date('Y-m-d', '+5 years'),
            'nationality' => $this->faker->country,
            'license_number' => $this->faker->unique()->bothify('##??##'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'registration_date' => $this->faker->date(),
        ];
    }
}
