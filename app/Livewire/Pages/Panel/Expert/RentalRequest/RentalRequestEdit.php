<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Car;
use App\Models\CarModel;
use Livewire\Component;
use App\Models\Contract;

class RentalRequestEdit extends Component
{
    public $contract;  // Contract data
    public $cars;
    public $carModels;
    public $selectedBrand; // Store the selected brand ID
    public $selectedCarId; // Store the selected car model ID

    public $start_date;
    public $end_date;
    public $total_price;
    public $status;
    public $note;

    public $first_name, $last_name, $email, $phone;
    public $address;
    public $national_code;
    public $passport_number;
    public $passport_expiry_date;
    public $nationality;
    public $license_number;

    // Add other fields for car and customer information as necessary
    protected $rules = [
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after:start_date',
        'total_price' => 'required|numeric|min:0',
        'status' => 'required|in:active,completed,cancelled,pending',
        'selectedBrand' => 'required|exists:car_models,id',  // Check if selectedBrand exists in the 'car_models' table for the given model's brand
        'selectedCarId' => 'required|exists:cars,id',
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|regex:/^[0-9]{10,15}$/',
        'address' => 'required|string|max:255',
        'national_code' => 'required|regex:/^[0-9]{10}$/', // Assuming National Code is a 10-digit number
        'passport_number' => 'required|string|max:50', // Adjust this based on passport number format
        'passport_expiry_date' => 'required|date|after_or_equal:today', // Passport expiry should not be in the past
        'nationality' => 'required|string|max:100', // You can adjust the length if needed
        'license_number' => 'required|string|max:50', // Adjust based on the format of the license number

    ];

    protected $messages = [
        'start_date.required' => 'Start date is required.',
        'start_date.after_or_equal' => 'Start date cannot be in the past.',
        'end_date.required' => 'End date is required.',
        'end_date.after' => 'End date must be later than start date.',
        'total_price.required' => 'The total price field is required.',
        'total_price.numeric' => 'The total price must be a valid number.',
        'total_price.min' => 'The total price cannot be negative.',
        'status.required' => 'The status field is required.',
        'status.in' => 'The selected status is invalid.',
        'selectedBrand.required' => 'The car brand field is required.',
        'selectedBrand.exists' => 'The selected car brand is invalid.',
        'selectedCarId.required' => 'The car model field is required.',
        'selectedCarId.exists' => 'The selected car model is invalid.',
        'first_name.required' => 'First name is required.',
        'first_name.string' => 'First name must be a string.',
        'first_name.max' => 'First name cannot be longer than 255 characters.',

        'last_name.required' => 'Last name is required.',
        'last_name.string' => 'Last name must be a string.',
        'last_name.max' => 'Last name cannot be longer than 255 characters.',

        'email.required' => 'Email is required.',
        'email.email' => 'Please provide a valid email address.',
        'email.max' => 'Email cannot be longer than 255 characters.',

        'phone.required' => 'Phone number is required.',
        'phone.regex' => 'Please provide a valid phone number.',
        'address.required' => 'Address is required.',
        'address.string' => 'Address must be a string.',
        'address.max' => 'Address cannot be longer than 255 characters.',

        'national_code.required' => 'National Code is required.',
        'national_code.regex' => 'National Code must be a 10-digit number.',

        'passport_number.required' => 'Passport Number is required.',
        'passport_number.string' => 'Passport Number must be a string.',
        'passport_number.max' => 'Passport Number cannot be longer than 50 characters.',

        'passport_expiry_date.required' => 'Passport Expiry Date is required.',
        'passport_expiry_date.date' => 'Please provide a valid date for Passport Expiry.',
        'passport_expiry_date.after_or_equal' => 'Passport Expiry Date cannot be in the past.',
        'nationality.required' => 'Nationality is required.',
        'nationality.string' => 'Nationality must be a string.',
        'nationality.max' => 'Nationality cannot be longer than 100 characters.',

        'license_number.required' => 'License Number is required.',
        'license_number.string' => 'License Number must be a string.',
        'license_number.max' => 'License Number cannot be longer than 50 characters.',
    

    ];

    // Dynamic array for car models of the selected brand
    public $filteredCarModels = [];

    // Mount method to load initial data
    public function mount($contractId)
    {
        $this->carModels = CarModel::all();
        $this->contract = Contract::findOrFail($contractId);
        $this->start_date = $this->contract->start_date->format('Y-m-d');
        $this->end_date = $this->contract->end_date->format('Y-m-d');
        $this->total_price = $this->contract->total_price;
        $this->status = $this->contract->status;
        $this->note = $this->contract->note;
        $this->first_name = $this->contract->customer->first_name;
        $this->last_name = $this->contract->customer->last_name;
        $this->email = $this->contract->customer->email;
        $this->phone = $this->contract->customer->phone;
        $this->address = $this->contract->customer->address;
        $this->national_code = $this->contract->customer->national_code;
        $this->passport_number = $this->contract->customer->passport_number;
        $this->passport_expiry_date = $this->contract->customer->passport_expiry_date;
        $this->nationality = $this->contract->customer->nationality;
        $this->license_number = $this->contract->customer->license_number;
   


        // Set initial selected values based on the contract's car
        $this->selectedBrand = $this->contract->car->carModel->id;  // Using brand for filtering
        $this->selectedCarId = $this->contract->car->id;

        // Fetch cars based on initial brand selection
        $this->filterCarsByBrand($this->selectedBrand);
    }

    // Method to filter cars by the selected brand
    public function updatedSelectedBrand($value)
    {
        $this->filterCarsByBrand($value);
        $this->selectedCarId = '';
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


    public function submit()
    {
        $this->validate();

        // Update the contract or save new one
        // $this->contract->update([
        //     'start_date' => $this->start_date,
        //     'end_date' => $this->end_date,
        //     'total_price' => $this->total_price,
        //     'status' => $this->status,
        //     'notes' => $this->notes,
        //     // Update other fields for car and customer information
        // ]);

        session()->flash('message', 'Contract updated successfully!');
    }

    // Livewire render method
    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-edit');
    }
}
