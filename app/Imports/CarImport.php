<?php

namespace App\Imports;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Insurance;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Row;

class CarImport implements ToModel, WithHeadingRow, OnEachRow
{
    use Importable;

    public function model(array $row)
    {

        // بررسی خالی بودن مقادیر کلیدی
    if (empty($row['no']) || empty($row['plate_no']) || empty($row['chassis_no']) || empty($row['make_year'])) {
        return null; // رد کردن سطرهای خالی
    }

        // تبدیل تاریخ‌های عددی اکسل به فرمت تاریخ میلادی
        $insuranceExpiryDate = $this->transformDate($row['expiry_insurance_date']);
        $issueDate = $this->transformDate($row['issue_date']);
        $expiryDate = $this->transformDate($row['expiry_date']);
        $passingDate = $this->transformDate($row['passing_date']);
        // Extract brand and model
        $parts = explode(' ', trim($row['model']), 2);
        $brand = $parts[0];
        $model = $parts[1] ?? '';
        // Find or create the car model
        $carModel = CarModel::firstOrCreate([
            'brand' => $brand,
            'model' => $model,
        ], [
            'brand_icon' => $row['brand_icon'] ?? null, // آیکون برند
            'images' => $row['car_model_images'] ?? null, // تصاویر مدل
        ]);
        // Check if the car already exists
        $existingCar = Car::where('plate_number', $row['plate_no'])
            ->orWhere('chassis_number', $row['chassis_no'])
            ->first();

        if ($existingCar) {
            // Skip the row if the car already exists
            return null;
        }
        // Create the car record
        $car = Car::create([
            'car_model_id' => $carModel->id,
            'plate_number' => $row['plate_no'],
            'chassis_number' => $row['chassis_no'],
            'manufacturing_year' => $row['make_year'],
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'registration_valid_for_days' => $row['registration_valid_for_days'],
            'registration_status' => $row['registration_status'],
            'passing_date' => $passingDate,
            'passing_status' => $row['passing_status'],
            'passing_valid_for_days' => $row['passing_valid_for_days'],
            'gps' => $row['gps'] === 'DONE',
            'status' => Car::STATUS_AVAILABLE,
            'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
            'manual_unavailability_reason' => null,
            'availability' => true,
            'unavailability_reason' => null,
            'mileage' => 0,
            'price_per_day' => 0.0,
            'service_due_date' => null,
            'damage_report' => null,
            'color' => null,
            'notes' => null,
        ]);

        // Create the insurance record
        Insurance::create([
            'car_id' => $car->id,
            'expiry_date' => $insuranceExpiryDate,
            'valid_days' => $row['insurance_valid_for_days'],
            'status' => $row['insurance'] === 'DONE' ? 'done' : 'pending',
            'insurance_company' => $row['insurance_co'],
        ]);

        return $car;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        // Call the model method to process and insert into the database
        $this->model($row);
    }

    /**
     * تبدیل تاریخ اکسل به فرمت میلادی
     */
    private function transformDate($excelDate)
    {
        if (is_numeric($excelDate)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($excelDate - 25569) * 86400))->toDateString();
        }
        return null; // اگر تاریخ معتبر نبود
    }

    
}
