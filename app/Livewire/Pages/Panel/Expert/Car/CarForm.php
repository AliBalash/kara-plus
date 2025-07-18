<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;


class CarForm extends Component
{
    use WithFileUploads;

    public $car; // Car instance
    public $cars;
    public $selectedBrand; // Store the selected brand ID
    public $selectedCarId; // Store the selected car model ID    
    public $carModels;
    public $plate_number;
    public $status;
    public $availability;
    public $mileage;
    public $price_per_day_short;
    public $price_per_day_mid;
    public $price_per_day_long;
    public $ldw_price;
    public $scdw_price;
    public $service_due_date;
    public $damage_report;
    public $manufacturing_year;
    public $color;
    public $chassis_number;
    public $gps;
    public $issue_date;
    public $expiry_date;
    public $passing_date;
    public $passing_valid_for_days;
    public $registration_valid_for_days;
    public $notes;
    public $newImage; // تصویر جدید آپلود شده
    public $passing_status;
    public $registration_status;
    public $car_options = [];




    // Validation rules for form fields
    protected $rules = [
        'plate_number' => 'required|string|max:255',
        'status' => 'required|in:available,reserved,under_maintenance',
        'availability' => 'required|max:255',
        'mileage' => 'required|numeric',
        'price_per_day_short' => 'required|numeric|min:0',
        'price_per_day_mid' => 'nullable|numeric|min:0',
        'price_per_day_long' => 'nullable|numeric|min:0',
        'ldw_price' => 'nullable|numeric|min:0',
        'scdw_price' => 'nullable|numeric|min:0',
        'service_due_date' => 'date|nullable',
        'damage_report' => 'nullable|string',
        'manufacturing_year' => 'required|numeric|min:1900',
        'color' => 'required|string|max:255',
        'chassis_number' => 'required|string|max:255',
        'gps' => 'nullable|max:255',
        'issue_date' => 'nullable|date',
        'expiry_date' => 'nullable|date',
        'passing_date' => 'nullable|date',
        'passing_valid_for_days' => 'nullable|numeric',
        'registration_valid_for_days' => 'nullable|numeric',
        'notes' => 'nullable|string',
        'passing_status' => 'nullable|in:done,pending,failed',
        'registration_status' => 'nullable|in:done,pending,failed',
        'newImage' => 'nullable|image|max:10240', // حداکثر 10MB

    ];

    // Mount method to load initial data
    public function mount($carId = null)
    {
        $this->carModels = CarModel::all();

        if ($carId) {
            $this->selectedCarId = $carId;
            $this->car = Car::findOrFail($this->selectedCarId);

            // Set initial selected values based on the car's data
            $this->selectedBrand = $this->car->carModel->id;

            // Load the car data into form fields
            $this->populateCarData($this->car);

            // Fetch cars based on the selected brand
            $this->filterCarsByBrand($this->selectedBrand);
        } else {
            abort(404);
        }
    }

    public function updatedSelectedCarId($carId)
    {
        // If a car is selected, populate the form fields
        if ($carId) {
            $this->car = Car::findOrFail($carId);
            $this->populateCarData($this->car);
        } else {
            // Reset form fields if no car is selected
            $this->resetCarData();
        }
    }

    // Helper method to populate car data in the form
    private function populateCarData($car)
    {
        $this->plate_number = $car->plate_number;
        $this->status = $car->status;
        $this->availability = $car->availability;
        $this->mileage = $car->mileage;
        $this->price_per_day_short = $car->price_per_day_short;
        $this->price_per_day_mid = $car->price_per_day_mid;
        $this->price_per_day_long = $car->price_per_day_long;
        $this->ldw_price = $car->ldw_price;
        $this->scdw_price = $car->scdw_price;
        $this->service_due_date = $this->car->service_due_date ? Carbon::parse($this->car->service_due_date)->toDateString() : null;
        $this->damage_report = $car->damage_report;
        $this->manufacturing_year = $car->manufacturing_year;
        $this->color = $car->color;
        $this->chassis_number = $car->chassis_number;
        $this->gps = $car->gps;
        $this->issue_date = $car->issue_date;
        $this->expiry_date = $car->expiry_date;
        $this->passing_date = $car->passing_date;
        $this->passing_valid_for_days = $car->passing_valid_for_days;
        $this->registration_valid_for_days = $car->registration_valid_for_days;
        $this->passing_status = $car->passing_status;
        $this->registration_status = $car->registration_status;
        $this->car_options = $car->options->pluck('option_value', 'option_key')->toArray();
        // Ensure booleans are correctly cast for Livewire checkboxes
        foreach (['base_insurance', 'unlimited_km', ] as $key) {
            if (isset($this->car_options[$key])) {
                $this->car_options[$key] = $this->car_options[$key] == '1' ? true : false;
            }
        }
        $this->notes = $car->notes;
    }

    // Helper method to reset car data fields
    private function resetCarData()
    {
        $this->plate_number = '';
        $this->status = '';
        $this->mileage = '';
        $this->price_per_day_short = '';
        $this->price_per_day_mid = '';
        $this->price_per_day_long = '';
        $this->ldw_price = '';
        $this->scdw_price = '';
        $this->service_due_date = '';
        $this->availability = '';
        $this->damage_report = '';
        $this->manufacturing_year = '';
        $this->color = '';
        $this->chassis_number = '';
        $this->gps = '';
        $this->issue_date = '';
        $this->expiry_date = '';
        $this->passing_date = '';
        $this->passing_valid_for_days = '';
        $this->registration_valid_for_days = '';
        $this->passing_status = 'done';
        $this->registration_status = 'done';
        $this->notes = '';
        $this->car_options = [
            'gear' => '',
            'seats' => '',
            'doors' => '',
            'luggage' => '',
            'min_days' => '',
            'unlimited_km' => false,
            'base_insurance' => false,
        ];
    }



    // Method to filter cars by the selected brand
    public function updatedSelectedBrand($value)
    {
        // Filter cars based on the selected brand
        $this->filterCarsByBrand($value);

        // Reset car selection when the brand changes
        $this->selectedCarId = null;
    }


    // Method to filter car models based on the selected brand
    public function filterCarsByBrand($brand)
    {
        if ($brand) {
            $this->cars = Car::whereHas('carModel', function ($query) use ($brand) {
                $query->where('id', $brand);
            })->get();
        } else {
            $this->cars = [];
        }
    }


    // Submit form and update car details
    public function submit()
    {
        $this->validate();

        // Update the car record in the database
        if ($this->car) {
            $this->car->update([
                'plate_number' => $this->plate_number,
                'status' => $this->status,
                'availability' => $this->availability,
                'mileage' => $this->mileage,
                'price_per_day_short' => $this->price_per_day_short,
                'price_per_day_mid' => $this->price_per_day_mid,
                'price_per_day_long' => $this->price_per_day_long,
                'ldw_price' => $this->ldw_price,
                'scdw_price' => $this->scdw_price,
                'service_due_date' => $this->service_due_date,
                'damage_report' => $this->damage_report,
                'manufacturing_year' => $this->manufacturing_year,
                'color' => $this->color,
                'chassis_number' => $this->chassis_number,
                'gps' => $this->gps,
                'issue_date' => $this->issue_date,
                'expiry_date' => $this->expiry_date,
                'passing_date' => $this->passing_date,
                'passing_valid_for_days' => $this->passing_valid_for_days,
                'registration_valid_for_days' => $this->registration_valid_for_days,
                'passing_status' => $this->passing_status,
                'registration_status' => $this->registration_status,
                'notes' => $this->notes,
            ]);

            $this->car->options()->delete();

            foreach ($this->car_options as $key => $value) {
                if ($value !== null && $value !== '') {
                    $this->car->options()->create([
                        'option_key' => $key,
                        'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                    ]);
                }
            }
        }
        if ($this->newImage) {
            $imageModel = $this->car->carModel->image;

            // حذف تصویر قبلی
            if ($imageModel && $imageModel->file_name && Storage::disk('car_pics')->exists($imageModel->file_name)) {
                Storage::disk('car_pics')->delete($imageModel->file_name);
            }

            // تولید نام فایل بر اساس نام ماشین
            $extension = $this->newImage->getClientOriginalExtension();
            $carName = $this->car->carModel->fullname();
            $safeName = Str::slug($carName) . '.' . $extension;

            // ذخیره فایل
            Storage::disk('car_pics')->putFileAs('', $this->newImage, $safeName);

            // ذخیره اطلاعات در دیتابیس
            if (!$imageModel) {
                $imageModel = new \App\Models\Image();
                $imageModel->imageable_id = $this->car->carModel->id;
                $imageModel->imageable_type = \App\Models\CarModel::class;
            }

            $imageModel->file_path = 'car-pics/';
            $imageModel->file_name = $safeName;
            $imageModel->save();
        }

        

        // Provide success feedback to the user
        session()->flash('message', 'Car details updated successfully.');
    }
    public function render()
    {
        return view('livewire.pages.panel.expert.car.car-form');
    }
}
