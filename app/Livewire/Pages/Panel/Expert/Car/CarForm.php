<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Models\CarModel;
use Carbon\Carbon;
use Livewire\Component;


class CarForm extends Component
{

    public $car; // Car instance
    public $cars;
    public $selectedBrand; // Store the selected brand ID
    public $selectedCarId; // Store the selected car model ID    
    public $carModels;
    public $plate_number;
    public $status;
    public $availability;
    public $mileage;
    public $price_per_day;
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

    // Validation rules for form fields
    protected $rules = [
        'plate_number' => 'required|string|max:255',
        'status' => 'required|in:available,reserved,under_maintenance',
        'availability' => 'required|string|max:255',
        'mileage' => 'required|numeric',
        'price_per_day' => 'required|numeric',
        'service_due_date' => 'required|date',
        'damage_report' => 'nullable|string',
        'manufacturing_year' => 'required|numeric|min:1900',
        'color' => 'required|string|max:255',
        'chassis_number' => 'required|string|max:255',
        'gps' => 'nullable|string|max:255',
        'issue_date' => 'required|date',
        'expiry_date' => 'required|date',
        'passing_date' => 'required|date',
        'passing_valid_for_days' => 'required|numeric',
        'registration_valid_for_days' => 'required|numeric',
        'notes' => 'nullable|string',
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
            // Reset the fields if no carId is provided
            $this->resetCarData();
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
        $this->price_per_day = $car->price_per_day;
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
        $this->notes = $car->notes;
    }

    // Helper method to reset car data fields
    private function resetCarData()
    {
        $this->plate_number = '';
        $this->status = '';
        $this->mileage = '';
        $this->price_per_day = '';
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
        $this->notes = '';
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
        // if ($this->car) {
        //     $this->car->update([
        //         'plate_number' => $this->plate_number,
        //         'status' => $this->status,
        //         'availability' => $this->availability,
        //         'mileage' => $this->mileage,
        //         'price_per_day' => $this->price_per_day,
        //         'service_due_date' => $this->service_due_date,
        //         'damage_report' => $this->damage_report,
        //         'manufacturing_year' => $this->manufacturing_year,
        //         'color' => $this->color,
        //         'chassis_number' => $this->chassis_number,
        //         'gps' => $this->gps,
        //         'issue_date' => $this->issue_date,
        //         'expiry_date' => $this->expiry_date,
        //         'passing_date' => $this->passing_date,
        //         'passing_valid_for_days' => $this->passing_valid_for_days,
        //         'registration_valid_for_days' => $this->registration_valid_for_days,
        //         'notes' => $this->notes,
        //     ]);
        // }

        // Provide success feedback to the user
        session()->flash('message', 'Car details updated successfully.');
    }
    public function render()
    {
        return view('livewire.pages.panel.expert.car.car-form');
    }
}
