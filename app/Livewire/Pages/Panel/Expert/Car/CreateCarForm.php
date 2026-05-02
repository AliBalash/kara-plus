<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\RefreshesFileInputs;
use App\Services\Media\DeferredImageUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class CreateCarForm extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;
    use RefreshesFileInputs;

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
    public $ownership_type = 'company';
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
    protected ?DeferredImageUploadService $deferredUploader = null;
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
            'status' => ['required', Rule::in(['available', 'pre_reserved', 'reserved', 'under_maintenance', 'sold'])],
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
            'ownership_type' => 'required|in:company,golden_key,liverpool,safe_drive,other',
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
        'status.in' => 'The status must be one of available, pre-reserved, reserved, under maintenance, or sold.',
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

    public function boot(DeferredImageUploadService $deferredUploader): void
    {
        $this->deferredUploader = $deferredUploader;
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

    public function updatedStatus($status): void
    {
        $this->syncStatusPreview();
    }

    private function resetCarData()
    {
        $this->plate_number = '';
        $this->status = 'available';
        $this->availability = true;
        $this->mileage = 0;
        $this->price_per_day_short = $this->formatDecimalValue(0);
        $this->price_per_day_mid = $this->formatDecimalValue(0);
        $this->price_per_day_long = $this->formatDecimalValue(0);
        $this->ldw_price_short = $this->formatDecimalValue(0);
        $this->ldw_price_mid = $this->formatDecimalValue(0);
        $this->ldw_price_long = $this->formatDecimalValue(0);
        $this->scdw_price_short = $this->formatDecimalValue(0);
        $this->scdw_price_mid = $this->formatDecimalValue(0);
        $this->scdw_price_long = $this->formatDecimalValue(0);
        $this->service_due_date = null;
        $this->damage_report = '';
        $this->manufacturing_year = '';
        $this->color = '';
        $this->chassis_number = '';
        $this->gps = false;
        $this->ownership_type = 'company';
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

        $this->syncStatusPreview();
    }

    private function formatDecimalValue($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    public function getEffectiveStatusLabelProperty(): string
    {
        $previewState = $this->statusPreviewState();

        return Car::operationalStatusLabelFor($previewState['status'], $previewState['availability']);
    }

    public function getEffectiveStatusExplanationProperty(): ?string
    {
        $previewState = $this->statusPreviewState();
        $effectiveStatus = Car::resolveOperationalStatus($previewState['status'], $previewState['availability']);

        if (
            in_array($this->status, ['reserved', 'pre_reserved'], true)
            && $previewState['status'] === 'available'
        ) {
            return 'Booked statuses are synchronized from contract dates. A newly created vehicle with no contract will be saved as Ready.';
        }

        return match ($effectiveStatus) {
            'available' => 'Ready vehicles stay available for search, Fleet Inventory, and reservation assignment.',
            'pre_reserved' => 'This vehicle is currently rentable, but it already has an upcoming booking.',
            'reserved' => 'This vehicle is tied to an active booking window.',
            'under_maintenance' => 'Under maintenance vehicles are always blocked from reservation assignment until they are returned to Ready.',
            'sold' => 'Sold vehicles are always treated as unavailable for operations.',
            default => null,
        };
    }

    protected function prepareForValidation($attributes)
    {
        $attributes['gps'] = $this->normalizeBooleanValue($attributes['gps'] ?? $this->gps);
        $attributes['car_options']['unlimited_km'] = $this->normalizeBooleanValue(
            $attributes['car_options']['unlimited_km'] ?? $this->car_options['unlimited_km'] ?? false
        );
        $attributes['car_options']['base_insurance'] = $this->normalizeBooleanValue(
            $attributes['car_options']['base_insurance'] ?? $this->car_options['base_insurance'] ?? false
        );

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
            } else {
                $attributes[$field] = round((float) $attributes[$field], 2);
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

    private function normalizeBooleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
    }

    public function submit()
    {
        $validated = $this->validate();
        $validated['availability'] = Car::availabilityForStatus($validated['status']);
        $validated['gps'] = $this->normalizeBooleanValue($validated['gps'] ?? false);
        $validated['car_options'] = is_array($validated['car_options'] ?? null)
            ? $validated['car_options']
            : (is_array($this->car_options) ? $this->car_options : []);
        $validated['car_options']['unlimited_km'] = $this->normalizeBooleanValue(
            $validated['car_options']['unlimited_km'] ?? false
        );
        $validated['car_options']['base_insurance'] = $this->normalizeBooleanValue(
            $validated['car_options']['base_insurance'] ?? false
        );

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

        $uploadedImagePath = null;

        try {
            DB::transaction(function () use ($validated, &$uploadedImagePath) {
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

                $car->syncOperationalState();

                foreach ($validated['car_options'] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $car->options()->create([
                            'option_key' => $key,
                            'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                        ]);
                    }
                }

                if ($this->newImage) {
                    $uploadedImagePath = $this->deferredUploader->store(
                        $this->newImage,
                        Str::slug($car->fullName()) . '-' . Str::uuid() . '.webp',
                        'car_pics',
                        ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080, 'optimize' => false]
                    );

                    $car->image()->create([
                        'imageable_id' => $car->id,
                        'imageable_type' => Car::class,
                        'file_path' => 'car-pics/',
                        'file_name' => basename($uploadedImagePath),
                    ]);
                }

                $carModel = CarModel::find($validated['selectedModelId']);
                $carModel->is_featured = $this->is_featured;
                $carModel->save();
            });
        } catch (\Throwable $exception) {
            if ($uploadedImagePath && Storage::disk('car_pics')->exists(basename($uploadedImagePath))) {
                Storage::disk('car_pics')->delete(basename($uploadedImagePath));
            }

            $this->toast('error', 'Unable to add the car: ' . $exception->getMessage(), false);

            return;
        }

        $this->toast('success', 'Car added successfully!');
        $this->resetCarData();
        $this->refreshFileInputs();
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.create-car-form');
    }

    private function syncStatusPreview(): void
    {
        $this->availability = $this->statusPreviewState()['availability'];
    }

    /**
     * @return array{status: string|null, availability: bool}
     */
    private function statusPreviewState(): array
    {
        return Car::synchronizedStateForReservationWindow($this->status, false, false);
    }
}
