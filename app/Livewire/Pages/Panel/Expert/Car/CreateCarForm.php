<?php

namespace App\Livewire\Pages\Panel\Expert\Car;


use App\Models\Car;
use App\Models\CarModel;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateCarForm extends Component
{
    use WithFileUploads;

    // Removed $car property (no editing existing car)
    public $carModels;
    public $status = 'available';
    public $availability = 'true';
    public $mileage = 0;
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
    public $passing_status = 'done';
    public $registration_status = 'done';
    public $car_options = [];
    public $is_featured = false;
    public $brands; // لیست برندها
    public $models; // لیست مدل‌های برند انتخاب شده
    public $selectedBrand = ''; // برند انتخاب شده
    public $selectedModelId = ''; // مدل انتخاب شده
    public $plate_number;


    protected $rules = [
        'selectedBrand' => 'required',
        'selectedModelId' => 'required|exists:car_models,id',
        'plate_number' => 'required|string|max:255|unique:cars,plate_number',
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
        'color' => 'string|max:255',
        'chassis_number' => 'required|string|max:255|unique:cars,chassis_number',
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


    public function mount()
    {
        $this->loadBrands();
        $this->resetCarData();
    }

    private function loadBrands()
    {
        // دریافت برندهای منحصر به فرد به ترتیب الفبا
        $this->brands = CarModel::distinct()
            ->pluck('brand')
            ->filter() // حذف null یا رشته‌های خالی
            ->unique()
            ->sort()
            ->values();
    }

    public function updatedSelectedBrand($brand)
    {
        // هنگام تغییر برند، مدل‌های مربوطه را بارگیری کنید
        $this->models = CarModel::where('brand', $brand)
            ->orderBy('model') // مرتب‌سازی حروف الفبا
            ->get();
        $this->selectedModelId = ''; // ریست انتخاب مدل
    }

    private function resetCarData()
    {
        // Reset all properties to default values
        $this->plate_number = '';
        $this->status = 'available';
        $this->mileage = '';
        $this->price_per_day_short = '';
        $this->price_per_day_mid = '';
        $this->price_per_day_long = '';
        $this->ldw_price = '';
        $this->scdw_price = '';
        $this->service_due_date = '';
        $this->availability = 'true';
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
            'fuel_type' => '',
            'engine_size' => '',
            'unlimited_km' => false,
            'base_insurance' => false,
            'air_conditioning' => false,
        ];
        $this->selectedBrand = '';
        $this->selectedModelId = '';
        $this->is_featured = '';
        $this->models = collect();
    }

    public function submit()
    {
        $this->validate();

        // Create new car
        $car = Car::create([
            'car_model_id' => $this->selectedModelId,
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

        // Save car options
        foreach ($this->car_options as $key => $value) {
            if ($value !== null) {
                $car->options()->create([
                    'option_key' => $key,
                    'option_value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                ]);
            }
        }

        $carModel = CarModel::find($this->selectedModelId);
        $carModel->is_featured = $this->is_featured;
        $carModel->save();


        session()->flash('message', 'Car added successfully!');
        $this->resetCarData();
        $this->loadBrands(); // بارگیری مجدد برندها پس از ریست
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.car.create-car-form');
    }
}
