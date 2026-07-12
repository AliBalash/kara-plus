<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\CarModel;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Car::create([
            'car_model_id' => CarModel::first()->id, // ارجاع به اولین مدل خودرو
            'plate_number' => '123-XYZ',
            'status' => Car::STATUS_AVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
            'manual_unavailability_reason' => null,
            'availability' => true,
            'unavailability_reason' => null,
            'mileage' => 1000,
            'price_per_day' => 100.00,
            'insurance_expiry_date' => now()->addYear(),
            'service_due_date' => now()->addMonths(6),
            'damage_report' => 'No damages',
            'manufacturing_year' => 2020,
            'color' => 'red',
            'notes' => 'No special notes',
        ]);
    }
}
