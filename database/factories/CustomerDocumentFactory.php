<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerDocumentFactory extends Factory
{
    protected $model = CustomerDocument::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'contract_id' => Contract::factory(),
            'visa' => $this->faker->filePath(),
            'passport' => $this->faker->filePath(),
            'license' => $this->faker->filePath(),
            'ticket' => $this->faker->filePath(),
        ];
    }
}
