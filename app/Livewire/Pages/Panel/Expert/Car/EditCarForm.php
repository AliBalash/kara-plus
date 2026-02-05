<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Media\DeferredImageUploadService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class EditCarForm extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;

    public $car;
    public $carModels;
    public $selectedBrand;
    public $plate_number;
    public $status;
    public $availability;
    public $mileage;
    public $price_per_day_short;
    public $price_per_day_mid;
    public $price_per_day_long;
    public $ldw_price_short;
    public $ldw_price_mid;
    public $ldw_price_long;
    public $scdw_price_short;
    public $scdw_price_mid;
    public $scdw_price_long;
    public $service_due_date;
    public $damage_report;
    public $manufacturing_year;
    public $color;
    public $chassis_number;
    public $gps;
    public $ownership_type;
    public $issue_date;
    public $expiry_date;
    public $passing_date;
    public $passing_valid_for_days;
    public $registration_valid_for_days;
    public $notes;
    public $passing_status;
    public $registration_status;
    public $car_options = [];
    public $existingImageUrl;
    public $is_featured = false;
    public $newImage;
    protected DeferredImageUploadService $deferredUploader;

    protected function rules()
    {
        return [
            'plate_number' => 'required|string|max:255',
            'status' => 'required|in:available,pre_reserved,reserved,under_maintenance',
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
            'chassis_number' => 'required|string|min:1|max:255',
            'gps' => 'boolean',
            'ownership_type' => 'required|in:company,golden_key,liverpool,other',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'passing_date' => 'nullable|date',
            'passing_valid_for_days' => 'nullable|integer|min:0',
            'registration_valid_for_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'passing_status' => 'nullable|in:done,pending,failed',
            'registration_status' => 'nullable|in:done,pending,failed',
            'car_options.gear' => 'nullable|in:automatic,manual',
            'car_options.seats' => 'nullable|integer|min:1',
            'car_options.doors' => 'nullable|integer|min:1',
            'car_options.luggage' => 'nullable|integer|min:0',
            'car_options.min_days' => 'nullable|integer|min:1',
            'car_options.fuel_type' => 'nullable|in:petrol,diesel,hybrid,electric',
            'car_options.unlimited_km' => 'boolean',
            'car_options.base_insurance' => 'boolean',
            'newImage' => 'nullable|image|max:10240',
        ];
    }

    protected $messages = [
        'plate_number.required' => 'The plate number is required.',
        'plate_number.max' => 'The plate number cannot exceed 255 characters.',
        'status.required' => 'The status is required.',
        'status.in' => 'The status must be one of available, pre-reserved, reserved, or under maintenance.',
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
        'newImage.image' => 'The uploaded file must be an image.',
        'newImage.max' => 'The image size cannot exceed 10MB.',
    ];

    public function mount($carId)
    {
        $this->car = Car::findOrFail($carId);
        $this->carModels = CarModel::all();
        $this->selectedBrand = $this->car->car_model_id;

        // Populate form fields, set defaults to 0 if null
        $this->plate_number = $this->car->plate_number;
        $this->status = $this->car->status;
        $this->availability = $this->car->availability;
        $this->mileage = $this->car->mileage;
        $this->price_per_day_short = $this->formatDecimalValue($this->car->price_per_day_short);
        $this->price_per_day_mid = $this->formatDecimalValue($this->car->price_per_day_mid);
        $this->price_per_day_long = $this->formatDecimalValue($this->car->price_per_day_long);
        $this->ldw_price_short = $this->formatDecimalValue($this->car->ldw_price_short);
        $this->ldw_price_mid = $this->formatDecimalValue($this->car->ldw_price_mid);
        $this->ldw_price_long = $this->formatDecimalValue($this->car->ldw_price_long);
        $this->scdw_price_short = $this->formatDecimalValue($this->car->scdw_price_short);
        $this->scdw_price_mid = $this->formatDecimalValue($this->car->scdw_price_mid);
        $this->scdw_price_long = $this->formatDecimalValue($this->car->scdw_price_long);
        $this->service_due_date = $this->car->service_due_date;
        $this->damage_report = $this->car->damage_report;
        $this->manufacturing_year = $this->car->manufacturing_year;
        $this->color = $this->car->color;
        $this->chassis_number = $this->car->chassis_number;
        $this->gps = $this->car->gps;
        $this->ownership_type = $this->car->ownershipType();
        $this->issue_date = $this->car->issue_date;
        $this->expiry_date = $this->car->expiry_date;
        $this->passing_date = $this->car->passing_date;
        $this->passing_valid_for_days = $this->car->passing_valid_for_days;
        $this->registration_valid_for_days = $this->car->registration_valid_for_days;
        $this->passing_status = $this->car->passing_status;
        $this->registration_status = $this->car->registration_status;
        $this->notes = $this->car->notes;
        $this->is_featured = $this->car->carModel->is_featured;

        $this->existingImageUrl = $this->car->primaryImageUrl();

        // Populate car options
        $this->car_options = $this->car->options->pluck('option_value', 'option_key')->toArray();

        // Convert boolean options
        foreach (['base_insurance', 'unlimited_km'] as $key) {
            if (isset($this->car_options[$key])) {
                $this->car_options[$key] = (bool)$this->car_options[$key];
            }
        }
    }

    public function boot(DeferredImageUploadService $deferredUploader): void
    {
        $this->deferredUploader = $deferredUploader;
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

        foreach ($decimalFields as $field) {
            if ($attributes[$field] === '' || $attributes[$field] === null) {
                $attributes[$field] = 0;
            } else {
                $attributes[$field] = round((float) $attributes[$field], 2);
            }
        }

        return $attributes;
    }

    public function submit()
    {
        $validated = $this->validate();

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
            $validated[$field] = round((float) $validated[$field], 2);
        }

        $this->car->update([
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
            'is_company_car' => $validated['ownership_type'] === 'company',
            'ownership_type' => $validated['ownership_type'],
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'passing_date' => $validated['passing_date'],
            'passing_valid_for_days' => $validated['passing_valid_for_days'],
            'registration_valid_for_days' => $validated['registration_valid_for_days'],
            'passing_status' => $validated['passing_status'],
            'registration_status' => $validated['registration_status'],
            'notes' => $validated['notes'],
        ]);

        $this->car->options()->delete();
        foreach ($validated['car_options'] as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->car->options()->create([
                    'option_key' => $key,
                    'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                ]);
            }
        }

        if ($this->newImage) {
            if ($this->car->image && Storage::disk('car_pics')->exists($this->car->image->file_name)) {
                Storage::disk('car_pics')->delete($this->car->image->file_name);
            }

            $safeName = Str::slug($this->car->fullName()) . '-' . time() . '.webp';
            $storedPath = $this->deferredUploader->store(
                $this->newImage,
                $safeName,
                'car_pics',
                ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080, 'optimize' => false]
            );
            $safeName = basename($storedPath);

            $image = $this->car->image()->updateOrCreate(
                [
                    'imageable_id' => $this->car->id,
                    'imageable_type' => Car::class,
                ],
                [
                    'file_path' => 'car-pics/',
                    'file_name' => $safeName,
                ]
            );

            $this->car->setRelation('image', $image);
            $this->existingImageUrl = $this->car->primaryImageUrl();
        }

        $this->car->carModel->is_featured = $this->is_featured;
        $this->car->carModel->save();

        $this->toast('success', 'Car updated successfully!');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.edit-car-form');
    }

    private function formatDecimalValue($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
