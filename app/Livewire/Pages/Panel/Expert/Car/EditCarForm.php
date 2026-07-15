<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Livewire\Concerns\LogsBusinessRead;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\CarUnavailabilityPeriod;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\RefreshesFileInputs;
use App\Services\Media\DeferredImageUploadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class EditCarForm extends Component
{
    use WithFileUploads;
    use InteractsWithToasts;
    use RefreshesFileInputs;
    use LogsBusinessRead;

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
    public bool $unavailableDeskReady = false;
    public ?int $unavailability_period_id = null;
    public string $hold_reason = '';
    public string $hold_note = '';
    public ?string $hold_start_date = null;
    public ?string $hold_end_date = null;
    protected DeferredImageUploadService $deferredUploader;

    protected function rules()
    {
        return [
            'plate_number' => 'required|string|max:255',
            'status' => [
                'required',
                Rule::in(array_keys(Car::manualStatusLabels())),
                function ($attribute, $value, $fail) {
                    if ($value !== Car::MANUAL_STATUS_SOLD) {
                        return;
                    }

                    if ($this->car && $this->car->hasReservingContracts()) {
                        $fail('This car cannot be marked as sold while it has active or upcoming reservations.');
                    }
                },
            ],
            'hold_reason' => [
                Rule::requiredIf(fn () => $this->status === Car::MANUAL_STATUS_UNAVAILABLE),
                'nullable',
                Rule::in(array_keys(Car::scheduledUnavailabilityReasonLabels())),
            ],
            'hold_note' => 'nullable|string|max:1000',
            'hold_start_date' => [
                Rule::requiredIf(fn () => $this->status === Car::MANUAL_STATUS_UNAVAILABLE),
                'nullable',
                'date',
            ],
            'hold_end_date' => [
                Rule::requiredIf(fn () => $this->status === Car::MANUAL_STATUS_UNAVAILABLE),
                'nullable',
                'date',
                'after_or_equal:hold_start_date',
            ],
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
            'ownership_type' => 'required|in:company,golden_key,liverpool,safe_drive,other',
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
        'status.in' => 'The status must be one of available, unavailable, or sold.',
        'hold_reason.required_if' => 'Please choose why this vehicle is unavailable.',
        'hold_reason.in' => 'The unavailable reason is invalid.',
        'hold_start_date.required_if' => 'Please set the unavailable start date.',
        'hold_start_date.date' => 'The unavailable start date must be a valid date.',
        'hold_end_date.required_if' => 'Please set the unavailable end date.',
        'hold_end_date.date' => 'The unavailable end date must be a valid date.',
        'hold_end_date.after_or_equal' => 'The unavailable end date must be on or after the start date.',
        'hold_note.max' => 'The unavailable note cannot exceed 1000 characters.',
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
        $this->status = $this->controlStatusForCar();
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
        $this->unavailableDeskReady = CarUnavailabilityPeriod::tableExists();

        // Populate car options
        $this->car_options = $this->car->options->pluck('option_value', 'option_key')->toArray();

        // Convert boolean options
        foreach (['base_insurance', 'unlimited_km'] as $key) {
            if (isset($this->car_options[$key])) {
                $this->car_options[$key] = (bool)$this->car_options[$key];
            }
        }

        $this->loadEditableUnavailableWindow();
        $this->syncStatusPreview();
        $this->auditBusinessRead([
            'car_id' => $this->car->id,
            'plate_number' => $this->car->plate_number,
            'car_model_id' => $this->car->car_model_id,
        ]);
    }

    public function boot(DeferredImageUploadService $deferredUploader): void
    {
        $this->deferredUploader = $deferredUploader;
    }

    public function updatedStatus($status): void
    {
        if ($status === Car::MANUAL_STATUS_UNAVAILABLE) {
            $this->hold_start_date ??= Carbon::today()->toDateString();
            $this->hold_end_date ??= Carbon::today()->toDateString();
        }

        $this->syncStatusPreview();
    }

    public function saveUnavailableWindow(): void
    {
        if (! $this->unavailableDeskReady) {
            $this->toast('error', 'The unavailable desk table is not available yet. Run the SQL first.', false);

            return;
        }

        if ($this->status === Car::MANUAL_STATUS_SOLD || $this->car->resolvedManualStatus() === Car::MANUAL_STATUS_SOLD) {
            $this->addError('hold_reason', 'Sold cars cannot receive an unavailable hold window.');

            return;
        }

        $validated = $this->validate([
            'hold_reason' => ['required', Rule::in(array_keys(Car::scheduledUnavailabilityReasonLabels()))],
            'hold_note' => ['nullable', 'string', 'max:1000'],
            'hold_start_date' => ['required', 'date'],
            'hold_end_date' => ['required', 'date', 'after_or_equal:hold_start_date'],
        ]);

        $start = Carbon::parse($validated['hold_start_date'])->startOfDay();
        $end = Carbon::parse($validated['hold_end_date'])->endOfDay();

        $overlapExists = CarUnavailabilityPeriod::query()
            ->where('car_id', $this->car->id)
            ->when($this->unavailability_period_id, fn ($query) => $query->where('id', '!=', $this->unavailability_period_id))
            ->overlappingWindow($start, $end)
            ->exists();

        if ($overlapExists) {
            $this->addError('hold_start_date', 'This car already has another unavailable period overlapping this date range.');

            return;
        }

        $attributes = [
            'car_id' => $this->car->id,
            'reason' => $validated['hold_reason'],
            'note' => $validated['hold_note'] !== '' ? $validated['hold_note'] : null,
            'start_date' => $validated['hold_start_date'],
            'end_date' => $validated['hold_end_date'],
            'updated_by' => auth()->id(),
        ];

        if ($this->unavailability_period_id) {
            $period = CarUnavailabilityPeriod::query()
                ->where('car_id', $this->car->id)
                ->findOrFail($this->unavailability_period_id);
            $period->update($attributes);
        } else {
            $period = CarUnavailabilityPeriod::query()->create($attributes + [
                'created_by' => auth()->id(),
            ]);
        }

        if ($this->car->resolvedManualStatus() === Car::MANUAL_STATUS_UNAVAILABLE) {
            $this->car->forceFill([
                'manual_status' => Car::MANUAL_STATUS_AVAILABLE,
                'manual_unavailability_reason' => null,
            ])->saveQuietly();
        }

        $this->syncCarAfterOperationalChange();
        $this->loadEditableUnavailableWindow($period->id);
        $this->toast('success', 'Unavailable window saved.');
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

        if ($validated['status'] === Car::MANUAL_STATUS_UNAVAILABLE) {
            if (! $this->unavailableDeskReady) {
                $this->addError('status', 'The unavailable desk table is not available yet. Run the SQL first.');

                return;
            }

            if ($this->submittedUnavailableWindowOverlaps($validated)) {
                $this->addError('hold_start_date', 'This car already has another unavailable period overlapping this date range.');

                return;
            }
        } elseif ($this->unavailability_period_id && ! CarUnavailabilityPeriod::supportsCancellationColumns()) {
            $this->addError('status', 'Run the cancellation fields SQL before changing an unavailable car back to available or sold.');

            return;
        }

        $validated['gps'] = $this->normalizeBooleanValue($validated['gps'] ?? false);
        $storageManualStatus = $validated['status'] === Car::MANUAL_STATUS_SOLD
            ? Car::MANUAL_STATUS_SOLD
            : Car::MANUAL_STATUS_AVAILABLE;
        $manualState = Car::manualStateAttributes(
            $storageManualStatus,
            null
        );
        $operationalState = Car::synchronizedStateForReservationWindow(
            $manualState['manual_status'],
            $manualState['manual_unavailability_reason'],
            false,
            false
        );
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

        $oldImageFileName = $this->car->image?->file_name;
        $newImagePath = null;

        try {
            DB::transaction(function () use ($validated, $manualState, $operationalState, &$newImagePath) {
                $this->car->update([
                    'plate_number' => $validated['plate_number'],
                    'status' => $operationalState['status'],
                    'manual_status' => $manualState['manual_status'],
                    'manual_unavailability_reason' => $manualState['manual_unavailability_reason'],
                    'availability' => $operationalState['availability'],
                    'unavailability_reason' => $operationalState['unavailability_reason'],
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

                $this->syncUnavailableWindowForSubmittedStatus($validated);
                $this->car->syncOperationalState();

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
                    $newImagePath = $this->deferredUploader->store(
                        $this->newImage,
                        Str::slug($this->car->fullName()) . '-' . Str::uuid() . '.webp',
                        'car_pics',
                        ['quality' => 55, 'max_width' => 1920, 'max_height' => 1080, 'optimize' => false]
                    );

                    $image = $this->car->image()->updateOrCreate(
                        [
                            'imageable_id' => $this->car->id,
                            'imageable_type' => Car::class,
                        ],
                        [
                            'file_path' => 'car-pics/',
                            'file_name' => basename($newImagePath),
                        ]
                    );

                    $this->car->setRelation('image', $image);
                }

                $this->car->carModel->is_featured = $this->is_featured;
                $this->car->carModel->save();
            });
        } catch (\Throwable $exception) {
            if ($newImagePath && Storage::disk('car_pics')->exists(basename($newImagePath))) {
                Storage::disk('car_pics')->delete(basename($newImagePath));
            }

            $this->toast('error', 'Unable to update the car: ' . $exception->getMessage(), false);

            return;
        }

        if (
            $newImagePath
            && $oldImageFileName
            && $oldImageFileName !== basename($newImagePath)
            && Storage::disk('car_pics')->exists($oldImageFileName)
        ) {
            Storage::disk('car_pics')->delete($oldImageFileName);
        }

        $this->car->refresh();
        $this->status = $this->controlStatusForCar();
        $this->availability = (bool) $this->car->availability;
        $this->existingImageUrl = $this->car->load(['image', 'carModel.image'])->primaryImageUrl();
        $this->newImage = null;
        $this->loadEditableUnavailableWindow();
        $this->syncStatusPreview();
        $this->refreshFileInputs();

        $this->toast('success', 'Car updated successfully!');
    }

    public function removeImage(): void
    {
        $image = $this->car->image;

        if (! $image) {
            return;
        }

        $fileName = $image->file_name;
        $image->delete();

        if ($fileName && Storage::disk('car_pics')->exists($fileName)) {
            Storage::disk('car_pics')->delete($fileName);
        }

        $this->car->unsetRelation('image');
        $this->car->load('carModel.image');
        $this->existingImageUrl = $this->car->primaryImageUrl();
        $this->newImage = null;
        $this->refreshFileInputs();

        $this->toast('success', 'Car image removed successfully.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.edit-car-form');
    }

    public function getEffectiveStatusLabelProperty(): string
    {
        $previewState = $this->statusPreviewState();

        return Car::operationalStatusLabelFor($previewState['status'], $previewState['availability']);
    }

    public function getEffectiveUnavailabilityReasonLabelProperty(): ?string
    {
        $previewState = $this->statusPreviewState();

        if (($previewState['status'] ?? null) !== Car::STATUS_UNAVAILABLE) {
            return null;
        }

        return Car::unavailabilityReasonLabelFor($previewState['unavailability_reason'] ?? null);
    }

    public function getEffectiveStatusExplanationProperty(): ?string
    {
        $previewState = $this->statusPreviewState();
        $effectiveStatus = Car::resolveOperationalStatus($previewState['status'], $previewState['availability']);

        if (
            $previewState['status'] === Car::STATUS_UNAVAILABLE
            && ($previewState['unavailability_reason'] ?? null) === Car::UNAVAILABILITY_REASON_NEED_ACTION
        ) {
            return 'Need Action is automatic. It is used when the contract return time has passed and the contract is still open.';
        }

        if ($previewState['status'] === Car::STATUS_RESERVED) {
            return 'This vehicle has an active reservation window, so the system will keep its status on Active booking until that contract is closed.';
        }

        if ($previewState['status'] === Car::STATUS_PRE_RESERVED) {
            return 'This vehicle already has an upcoming reservation, so the system will keep its status on Upcoming booking.';
        }

        return match ($effectiveStatus) {
            Car::STATUS_AVAILABLE => 'Available vehicles stay ready for search, inventory, and reservation assignment.',
            Car::STATUS_PRE_RESERVED => 'This vehicle stays rentable, but it already has an upcoming booking.',
            Car::STATUS_RESERVED => 'This vehicle is tied to an active booking window.',
            Car::STATUS_UNAVAILABLE => 'Unavailable vehicles are blocked from reservation assignment until they are reactivated.',
            Car::STATUS_SOLD => 'Sold vehicles are always treated as unavailable for operations.',
            default => null,
        };
    }

    public function getUnavailableHistoryProperty()
    {
        if (! $this->unavailableDeskReady || ! $this->car instanceof Car || ! $this->car->exists) {
            return collect();
        }

        return CarUnavailabilityPeriod::query()
            ->with(['creator', 'updater', 'canceller'])
            ->where('car_id', $this->car->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    private function syncStatusPreview(): void
    {
        $this->availability = $this->statusPreviewState()['availability'];
    }

    private function loadEditableUnavailableWindow(?int $preferredPeriodId = null): void
    {
        if (! $this->unavailableDeskReady) {
            $this->resetUnavailableWindowForm();

            return;
        }

        $period = null;

        if ($preferredPeriodId) {
            $period = CarUnavailabilityPeriod::query()
                ->where('car_id', $this->car->id)
                ->find($preferredPeriodId);
        }

        $period ??= $this->car->activeScheduledUnavailabilityPeriod()
            ?? $this->car->upcomingScheduledUnavailabilityPeriod();

        if ($period) {
            $this->unavailability_period_id = $period->id;
            $this->hold_reason = $period->reason;
            $this->hold_note = (string) ($period->note ?? '');
            $this->hold_start_date = $period->start_date?->format('Y-m-d');
            $this->hold_end_date = $period->end_date?->format('Y-m-d');
            $this->status = Car::MANUAL_STATUS_UNAVAILABLE;

            return;
        }

        if ($this->car->resolvedManualStatus() === Car::MANUAL_STATUS_UNAVAILABLE) {
            $this->unavailability_period_id = null;
            $this->hold_reason = $this->car->resolvedManualUnavailabilityReason() ?? Car::UNAVAILABILITY_REASON_MANAGEMENT_DECISION;
            $this->hold_note = (string) ($this->car->notes ?? '');
            $this->hold_start_date = Carbon::today()->toDateString();
            $this->hold_end_date = Carbon::today()->toDateString();
            $this->status = Car::MANUAL_STATUS_UNAVAILABLE;

            return;
        }

        $this->resetUnavailableWindowForm();
    }

    private function resetUnavailableWindowForm(): void
    {
        $this->unavailability_period_id = null;
        $this->hold_reason = '';
        $this->hold_note = '';
        $this->hold_start_date = null;
        $this->hold_end_date = null;
    }

    private function syncCarAfterOperationalChange(): void
    {
        $this->car->refresh();
        $this->car->syncOperationalState();
        $this->car->refresh();
        $this->status = $this->controlStatusForCar();
        $this->availability = (bool) $this->car->availability;
        $this->syncStatusPreview();
    }

    private function controlStatusForCar(): string
    {
        if (! $this->car instanceof Car) {
            return Car::MANUAL_STATUS_AVAILABLE;
        }

        if ($this->car->resolvedManualStatus() === Car::MANUAL_STATUS_SOLD) {
            return Car::MANUAL_STATUS_SOLD;
        }

        if ($this->car->resolvedManualStatus() === Car::MANUAL_STATUS_UNAVAILABLE) {
            return Car::MANUAL_STATUS_UNAVAILABLE;
        }

        if ($this->car->activeScheduledUnavailabilityPeriod() || $this->car->upcomingScheduledUnavailabilityPeriod()) {
            return Car::MANUAL_STATUS_UNAVAILABLE;
        }

        if (
            $this->car->status === Car::STATUS_UNAVAILABLE
            && $this->car->unavailability_reason
            && $this->car->unavailability_reason !== Car::UNAVAILABILITY_REASON_NEED_ACTION
        ) {
            return Car::MANUAL_STATUS_UNAVAILABLE;
        }

        return Car::MANUAL_STATUS_AVAILABLE;
    }

    private function submittedUnavailableWindowOverlaps(array $validated): bool
    {
        $start = Carbon::parse($validated['hold_start_date'])->startOfDay();
        $end = Carbon::parse($validated['hold_end_date'])->endOfDay();

        return CarUnavailabilityPeriod::query()
            ->where('car_id', $this->car->id)
            ->when($this->unavailability_period_id, fn ($query) => $query->where('id', '!=', $this->unavailability_period_id))
            ->overlappingWindow($start, $end)
            ->exists();
    }

    private function syncUnavailableWindowForSubmittedStatus(array $validated): ?CarUnavailabilityPeriod
    {
        if ($validated['status'] !== Car::MANUAL_STATUS_UNAVAILABLE) {
            $this->cancelEditableUnavailableWindow($validated['status'] === Car::MANUAL_STATUS_SOLD
                ? 'Cancelled because car was marked sold.'
                : 'Cancelled because base status was changed to available.');

            return null;
        }

        $attributes = [
            'car_id' => $this->car->id,
            'reason' => $validated['hold_reason'],
            'note' => ($validated['hold_note'] ?? '') !== '' ? $validated['hold_note'] : null,
            'start_date' => $validated['hold_start_date'],
            'end_date' => $validated['hold_end_date'],
            'updated_by' => auth()->id(),
        ];

        if ($this->unavailability_period_id) {
            $period = CarUnavailabilityPeriod::query()
                ->where('car_id', $this->car->id)
                ->findOrFail($this->unavailability_period_id);
            $period->update($attributes);

            return $period;
        }

        return CarUnavailabilityPeriod::query()->create($attributes + [
            'created_by' => auth()->id(),
        ]);
    }

    private function cancelEditableUnavailableWindow(string $note): void
    {
        if (! $this->unavailability_period_id || ! $this->unavailableDeskReady) {
            return;
        }

        if (! CarUnavailabilityPeriod::supportsCancellationColumns()) {
            return;
        }

        $period = CarUnavailabilityPeriod::query()
            ->where('car_id', $this->car->id)
            ->whereKey($this->unavailability_period_id)
            ->first();

        $period?->cancel(auth()->id(), $note);
    }

    /**
     * @return array{status: string, availability: bool, unavailability_reason: string|null}
     */
    private function statusPreviewState(): array
    {
        $hasActiveReservation = false;
        $hasUpcomingReservation = false;
        $needsAction = false;

        if ($this->car instanceof Car && $this->car->exists) {
            $needsAction = $this->car->hasNeedActionReservationWindow();
            $hasActiveReservation = ! $needsAction && $this->car->hasActiveReservationWindow();
            $hasUpcomingReservation = ! $needsAction && ! $hasActiveReservation && $this->car->hasUpcomingReservationWindow();
        }

        $manualState = Car::manualStateAttributes(
            $this->status === Car::MANUAL_STATUS_SOLD ? Car::MANUAL_STATUS_SOLD : Car::MANUAL_STATUS_AVAILABLE,
            null
        );
        $scheduledReason = $this->previewScheduledUnavailabilityReason();

        return Car::synchronizedStateForReservationWindow(
            $manualState['manual_status'],
            $manualState['manual_unavailability_reason'],
            $hasActiveReservation,
            $hasUpcomingReservation,
            $needsAction,
            $scheduledReason
        );
    }

    private function previewScheduledUnavailabilityReason(): ?string
    {
        if ($this->hold_reason !== '' && $this->hold_start_date && $this->hold_end_date) {
            try {
                $today = Carbon::today();
                $start = Carbon::parse($this->hold_start_date)->startOfDay();
                $end = Carbon::parse($this->hold_end_date)->endOfDay();

                if ($today->betweenIncluded($start, $end)) {
                    return $this->hold_reason;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->car instanceof Car && $this->car->exists
            ? $this->car->activeScheduledUnavailabilityPeriod()?->reason
            : null;
    }

    private function formatDecimalValue($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
