<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class CreateCarForm extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;

    public $carModels;
    public $brands;
    public $models;
    public $selectedBrand = '';
    public $selectedModelId = '';
    public $plate_number;
    public $status = 'available';
    public $availability = true;
    public $mileage = 0;
    public $price_per_day_short = 0;
    public $price_per_day_mid = 0;
    public $price_per_day_long = 0;
    public $ldw_price_short = 0;
    public $ldw_price_mid = 0;
    public $ldw_price_long = 0;
    public $scdw_price_short = 0;
    public $scdw_price_mid = 0;
    public $scdw_price_long = 0;
    public $service_due_date;
    public $damage_report;
    public $manufacturing_year;
    public $color;
    public $chassis_number;
    public $gps = false;
    public $issue_date;
    public $expiry_date;
    public $passing_date;
    public $passing_valid_for_days;
    public $registration_valid_for_days;
    public $notes;
    public $newImage;
    public $passing_status = 'done';
    public $registration_status = 'done';
    public $is_featured = false;
    public $car_options = [
        'gear' => '',
        'seats' => '',
        'doors' => '',
        'luggage' => '',
        'min_days' => '',
        'fuel_type' => '',
        'unlimited_km' => false,
        'base_insurance' => false,
    ];

    protected function rules()
    {
        return [
            'selectedBrand' => 'required|string',
            'selectedModelId' => 'required|exists:car_models,id',
            'plate_number' => 'required|string|max:255|unique:cars,plate_number',
            'status' => 'required|in:available,reserved,under_maintenance',
            'availability' => 'required|boolean',
            'mileage' => 'required|numeric|min:0',
            'price_per_day_short' => 'required|numeric|min:0',
            'price_per_day_mid' => 'required|numeric|min:0',
            'price_per_day_long' => 'required|numeric|min:0',
            'ldw_price_short' => 'required|numeric|min:0',
            'ldw_price_mid' => 'required|numeric|min:0',
            'ldw_price_long' => 'required|numeric|min:0',
            'scdw_price_short' => 'required|numeric|min:0',
            'scdw_price_mid' => 'required|numeric|min:0',
            'scdw_price_long' => 'required|numeric|min:0',
            'service_due_date' => 'nullable|date',
            'damage_report' => 'nullable|string|max:1000',
            'manufacturing_year' => 'required|integer|min:1900|max:2155',
            'color' => 'required|string|min:1|max:255',
            'chassis_number' => 'required|string|min:1|max:255|unique:cars,chassis_number',
            'gps' => 'boolean',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'passing_date' => 'nullable|date',
            'passing_valid_for_days' => 'nullable|integer|min:0',
            'registration_valid_for_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'passing_status' => 'nullable|in:done,pending,failed',
            'registration_status' => 'nullable|in:done,pending,failed',
            'newImage' => 'nullable|image|max:10240',
            'car_options.gear' => 'nullable|in:automatic,manual',
            'car_options.seats' => 'nullable|integer|min:1',
            'car_options.doors' => 'nullable|integer|min:1',
            'car_options.luggage' => 'nullable|integer|min:0',
            'car_options.min_days' => 'nullable|integer|min:1',
            'car_options.fuel_type' => 'nullable|in:petrol,diesel,hybrid,electric',
            'car_options.unlimited_km' => 'boolean',
            'car_options.base_insurance' => 'boolean',
        ];
    }

    protected $messages = [
        'selectedBrand.required' => 'The car brand is required.',
        'selectedModelId.required' => 'The car model is required.',
        'selectedModelId.exists' => 'The selected car model is invalid.',
        'plate_number.required' => 'The plate number is required.',
        'plate_number.max' => 'The plate number cannot exceed 255 characters.',
        'plate_number.unique' => 'The plate number is already in use.',
        'status.required' => 'The status is required.',
        'status.in' => 'The status must be one of available, reserved, or under maintenance.',
        'availability.required' => 'The availability is required.',
        'mileage.required' => 'The mileage is required.',
        'mileage.numeric' => 'The mileage must be a number.',
        'mileage.min' => 'The mileage cannot be negative.',
        'price_per_day_short.required' => 'The short-term daily price is required.',
        'price_per_day_short.numeric' => 'The short-term daily price must be a number.',
        'price_per_day_short.min' => 'The short-term daily price cannot be negative.',
        'price_per_day_mid.required' => 'The mid-term daily price is required.',
        'price_per_day_mid.numeric' => 'The mid-term daily price must be a number.',
        'price_per_day_mid.min' => 'The mid-term daily price cannot be negative.',
        'price_per_day_long.required' => 'The long-term daily price is required.',
        'price_per_day_long.numeric' => 'The long-term daily price must be a number.',
        'price_per_day_long.min' => 'The long-term daily price cannot be negative.',
        'ldw_price_short.required' => 'The LDW short-term price is required.',
        'ldw_price_short.numeric' => 'The LDW short-term price must be a number.',
        'ldw_price_short.min' => 'The LDW short-term price cannot be negative.',
        'ldw_price_mid.required' => 'The LDW mid-term price is required.',
        'ldw_price_mid.numeric' => 'The LDW mid-term price must be a number.',
        'ldw_price_mid.min' => 'The LDW mid-term price cannot be negative.',
        'ldw_price_long.required' => 'The LDW long-term price is required.',
        'ldw_price_long.numeric' => 'The LDW long-term price must be a number.',
        'ldw_price_long.min' => 'The LDW long-term price cannot be negative.',
        'scdw_price_short.required' => 'The SCDW short-term price is required.',
        'scdw_price_short.numeric' => 'The SCDW short-term price must be a number.',
        'scdw_price_short.min' => 'The SCDW short-term price cannot be negative.',
        'scdw_price_mid.required' => 'The SCDW mid-term price is required.',
        'scdw_price_mid.numeric' => 'The SCDW mid-term price must be a number.',
        'scdw_price_mid.min' => 'The SCDW mid-term price cannot be negative.',
        'scdw_price_long.required' => 'The SCDW long-term price is required.',
        'scdw_price_long.numeric' => 'The SCDW long-term price must be a number.',
        'scdw_price_long.min' => 'The SCDW long-term price cannot be negative.',
        'service_due_date.date' => 'The service due date must be a valid date.',
        'damage_report.max' => 'The damage report cannot exceed 1000 characters.',
        'manufacturing_year.required' => 'The manufacturing year is required.',
        'manufacturing_year.integer' => 'The manufacturing year must be an integer.',
        'manufacturing_year.min' => 'The manufacturing year cannot be before 1900.',
        'manufacturing_year.max' => 'The manufacturing year cannot be after 2155.',
        'color.required' => 'The color is required.',
        'color.min' => 'The color field cannot be empty.',
        'color.max' => 'The color cannot exceed 255 characters.',
        'chassis_number.required' => 'The chassis number is required.',
        'chassis_number.min' => 'The chassis number cannot be empty.',
        'chassis_number.max' => 'The chassis number cannot exceed 255 characters.',
        'chassis_number.unique' => 'The chassis number is already in use.',
        'gps.boolean' => 'The GPS status must be yes or no.',
        'issue_date.date' => 'The issue date must be a valid date.',
        'expiry_date.date' => 'The expiry date must be a valid date.',
        'expiry_date.after_or_equal' => 'The expiry date must be on or after the issue date.',
        'passing_date.date' => 'The passing date must be a valid date.',
        'passing_valid_for_days.integer' => 'The passing validity days must be an integer.',
        'passing_valid_for_days.min' => 'The passing validity days cannot be negative.',
        'registration_valid_for_days.integer' => 'The registration validity days must be an integer.',
        'registration_valid_for_days.min' => 'The registration validity days cannot be negative.',
        'notes.max' => 'The notes cannot exceed 1000 characters.',
        'passing_status.in' => 'The passing status must be one of done, pending, or failed.',
        'registration_status.in' => 'The registration status must be one of done, pending, or failed.',
        'newImage.image' => 'The uploaded file must be an image.',
        'newImage.max' => 'The image size cannot exceed 10MB.',
        'car_options.gear.in' => 'The gear type must be either automatic or manual.',
        'car_options.seats.integer' => 'The number of seats must be an integer.',
        'car_options.seats.min' => 'The number of seats must be at least 1.',
        'car_options.doors.integer' => 'The number of doors must be an integer.',
        'car_options.doors.min' => 'The number of doors must be at least 1.',
        'car_options.luggage.integer' => 'The number of luggage must be an integer.',
        'car_options.luggage.min' => 'The number of luggage cannot be negative.',
        'car_options.min_days.integer' => 'The minimum rental days must be an integer.',
        'car_options.min_days.min' => 'The minimum rental days must be at least 1.',
        'car_options.fuel_type.in' => 'The fuel type must be one of petrol, diesel, hybrid, or electric.',
    ];

    public function mount()
    {
        $this->loadBrands();
        $this->resetCarData();
        $this->carModels = CarModel::all();
    }

    private function loadBrands()
    {
        $this->brands = CarModel::distinct()
            ->pluck('brand')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $this->models = collect();
    }

    public function updatedSelectedBrand($brand)
    {
        $this->models = CarModel::where('brand', $brand)
            ->orderBy('model')
            ->get();
        $this->selectedModelId = '';
    }

    private function resetCarData()
    {
        $this->plate_number = '';
        $this->status = 'available';
        $this->availability = true;
        $this->mileage = 0;
        $this->price_per_day_short = 0;
        $this->price_per_day_mid = 0;
        $this->price_per_day_long = 0;
        $this->ldw_price_short = 0;
        $this->ldw_price_mid = 0;
        $this->ldw_price_long = 0;
        $this->scdw_price_short = 0;
        $this->scdw_price_mid = 0;
        $this->scdw_price_long = 0;
        $this->service_due_date = null;
        $this->damage_report = '';
        $this->manufacturing_year = '';
        $this->color = '';
        $this->chassis_number = '';
        $this->gps = false;
        $this->issue_date = null;
        $this->expiry_date = null;
        $this->passing_date = null;
        $this->passing_valid_for_days = '';
        $this->registration_valid_for_days = '';
        $this->notes = '';
        $this->passing_status = 'done';
        $this->registration_status = 'done';
        $this->is_featured = false;
        $this->newImage = null;
        $this->selectedBrand = '';
        $this->selectedModelId = '';
        $this->models = collect();
        $this->car_options = [
            'gear' => '',
            'seats' => '',
            'doors' => '',
            'luggage' => '',
            'min_days' => '',
            'fuel_type' => '',
            'unlimited_km' => false,
            'base_insurance' => false,
        ];
    }

    protected function prepareForValidation($attributes)
    {
        $decimalFields = [
            'price_per_day_short',
            'price_per_day_mid',
            'price_per_day_long',
            'ldw_price_short',
            'ldw_price_mid',
            'ldw_price_long',
            'scdw_price_short',
            'scdw_price_mid',
            'scdw_price_long',
        ];

        foreach ($decimalFields as $field) {
            if ($attributes[$field] === '' || $attributes[$field] === null) {
                $attributes[$field] = 0;
            }
        }

        if ($attributes['manufacturing_year'] === '' || $attributes['manufacturing_year'] === null) {
            $attributes['manufacturing_year'] = null;
        } else {
            $attributes['manufacturing_year'] = (int) $attributes['manufacturing_year'];
        }

        if ($attributes['color'] === '') {
            $attributes['color'] = null;
        }

        if ($attributes['chassis_number'] === '') {
            $attributes['chassis_number'] = null;
        }

        $dateFields = ['service_due_date', 'issue_date', 'expiry_date', 'passing_date'];
        foreach ($dateFields as $field) {
            if ($attributes[$field] === '') {
                $attributes[$field] = null;
            }
        }

        $intFields = ['passing_valid_for_days', 'registration_valid_for_days', 'mileage'];
        foreach ($intFields as $field) {
            if ($attributes[$field] === '' || $attributes[$field] === null) {
                $attributes[$field] = 0;
            } else {
                $attributes[$field] = (int) $attributes[$field];
            }
        }

        return $attributes;
    }

    public function submit()
    {
        $validated = $this->validate();

        $car = Car::create([
            'car_model_id' => $validated['selectedModelId'],
            'plate_number' => $validated['plate_number'],
            'status' => $validated['status'],
            'availability' => $validated['availability'],
            'mileage' => $validated['mileage'],
            'price_per_day_short' => $validated['price_per_day_short'],
            'price_per_day_mid' => $validated['price_per_day_mid'],
            'price_per_day_long' => $validated['price_per_day_long'],
            'ldw_price_short' => $validated['ldw_price_short'],
            'ldw_price_mid' => $validated['ldw_price_mid'],
            'ldw_price_long' => $validated['ldw_price_long'],
            'scdw_price_short' => $validated['scdw_price_short'],
            'scdw_price_mid' => $validated['scdw_price_mid'],
            'scdw_price_long' => $validated['scdw_price_long'],
            'service_due_date' => $validated['service_due_date'],
            'damage_report' => $validated['damage_report'],
            'manufacturing_year' => $validated['manufacturing_year'],
            'color' => $validated['color'],
            'chassis_number' => $validated['chassis_number'],
            'gps' => $validated['gps'],
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'passing_date' => $validated['passing_date'],
            'passing_valid_for_days' => $validated['passing_valid_for_days'],
            'registration_valid_for_days' => $validated['registration_valid_for_days'],
            'passing_status' => $validated['passing_status'],
            'registration_status' => $validated['registration_status'],
            'notes' => $validated['notes'],
        ]);

        foreach ($validated['car_options'] as $key => $value) {
            if ($value !== null && $value !== '') {
                $car->options()->create([
                    'option_key' => $key,
                    'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                ]);
            }
        }

        if ($this->newImage) {
            $extension = $this->newImage->getClientOriginalExtension();
            $carName = CarModel::find($validated['selectedModelId'])->fullname();
            $safeName = Str::slug($carName) . '.' . $extension;

            Storage::disk('car_pics')->putFileAs('', $this->newImage, $safeName);

            $imageModel = new \App\Models\Image();
            $imageModel->imageable_id = $car->car_model_id;
            $imageModel->imageable_type = \App\Models\CarModel::class;
            $imageModel->file_path = 'car-pics/';
            $imageModel->file_name = $safeName;
            $imageModel->save();
        }

        $carModel = CarModel::find($validated['selectedModelId']);
        $carModel->is_featured = $this->is_featured;
        $carModel->save();

        $this->toast('success', 'Car added successfully!');
        $this->resetCarData();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.create-car-form');
    }
}
