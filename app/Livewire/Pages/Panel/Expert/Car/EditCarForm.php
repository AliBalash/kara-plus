<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class EditCarForm extends Component
{
    use WithFileUploads;

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
    public $passing_status;
    public $registration_status;
    public $car_options = [];
    public $existingImageUrl;
    public $is_featured = false;


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
        'service_due_date' => 'nullable|date',
        'damage_report' => 'nullable|string',
        'manufacturing_year' => 'required|numeric|min:1900',
        'color' => 'string|max:255',
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
    ];

    public function mount($carId)
    {
        $this->car = Car::findOrFail($carId);
        $this->carModels = CarModel::all();
        $this->selectedBrand = $this->car->car_model_id;

        // Populate form fields
        $this->plate_number = $this->car->plate_number;
        $this->status = $this->car->status;
        $this->availability = $this->car->availability;
        $this->mileage = $this->car->mileage;
        $this->price_per_day_short = $this->car->price_per_day_short;
        $this->price_per_day_mid = $this->car->price_per_day_mid;
        $this->price_per_day_long = $this->car->price_per_day_long;
        $this->ldw_price = $this->car->ldw_price;
        $this->scdw_price = $this->car->scdw_price;
        $this->service_due_date = $this->car->service_due_date;
        $this->damage_report = $this->car->damage_report;
        $this->manufacturing_year = $this->car->manufacturing_year;
        $this->color = $this->car->color;
        $this->chassis_number = $this->car->chassis_number;
        $this->is_featured = $this->car->carModel->is_featured;

        $this->gps = $this->car->gps;
        $this->issue_date = $this->car->issue_date;
        $this->expiry_date = $this->car->expiry_date;
        $this->passing_date = $this->car->passing_date;
        $this->passing_valid_for_days = $this->car->passing_valid_for_days;
        $this->registration_valid_for_days = $this->car->registration_valid_for_days;
        $this->passing_status = $this->car->passing_status;
        $this->registration_status = $this->car->registration_status;
        $this->notes = $this->car->notes;

        // Set existing image URL
        if ($this->car->carModel->image) {
            $this->existingImageUrl = asset('assets/' .
                $this->car->carModel->image->file_path .
                $this->car->carModel->image->file_name);
        }

        // Populate car options
        $this->car_options = $this->car->options->pluck('option_value', 'option_key')->toArray();

        // Convert boolean options
        foreach (['base_insurance', 'unlimited_km'] as $key) {
            if (isset($this->car_options[$key])) {
                $this->car_options[$key] = (bool)$this->car_options[$key];
            }
        }
    }

    public function submit()
    {
        $this->validate();

        // Update car
        $this->car->update([
            'plate_number' => $this->plate_number,
            'status' => $this->status,
            'availability' => filter_var($this->availability, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'mileage' => $this->mileage,
            'price_per_day_short' => $this->price_per_day_short,
            'price_per_day_mid' => $this->price_per_day_mid,
            'price_per_day_long' => $this->price_per_day_long,
            'ldw_price' => $this->ldw_price,
            'scdw_price' => $this->scdw_price,
            'damage_report' => $this->damage_report,
            'manufacturing_year' => $this->manufacturing_year,
            'color' => $this->color,
            'chassis_number' => $this->chassis_number,
            'gps' => filter_var($this->gps, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,

            'issue_date' => $this->issue_date ?: null,
            'expiry_date' => $this->expiry_date ?: null,
            'passing_date' => $this->passing_date ?: null,
            'service_due_date' => $this->service_due_date ?: null,
            'passing_valid_for_days' => $this->passing_valid_for_days !== '' ? $this->passing_valid_for_days : null,
            'registration_valid_for_days' => $this->registration_valid_for_days !== '' ? $this->registration_valid_for_days : null,
            'passing_status' => $this->passing_status,
            'registration_status' => $this->registration_status,
            'notes' => $this->notes,
        ]);



        // Update car options
        $this->car->options()->delete();
        foreach ($this->car_options as $key => $value) {
            if ($value !== null) {
                $this->car->options()->create([
                    'option_key' => $key,
                    'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                ]);
            }
        }

        $this->car->carModel->is_featured = $this->is_featured;
        $this->car->carModel->save();

        session()->flash('message', 'Car updated successfully!');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.edit-car-form');
    }
}
