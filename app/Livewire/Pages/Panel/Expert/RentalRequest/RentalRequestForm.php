<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RentalRequestForm extends Component
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
    // Dynamic array for car models of the selected brand
    public $filteredCarModels = [];

    // Mount method to load initial data
    public function mount($contractId = null)
    {
        $this->carModels = CarModel::all();
        if ($contractId) {

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
    }


    // Add other fields for car and customer information as necessary
    // Add this to the validation rules
    protected function rules()
    {
        // If editing, get the customer ID (if the contract exists, fetch the customer's ID)
        $customerId = $this->contract ? $this->contract->customer->id : null;

        return [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,completed,cancelled,pending',
            'selectedBrand' => 'required|exists:car_models,id',
            'selectedCarId' => 'required|exists:cars,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customerId), // Ignore the current customer when updating
            ],
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'address' => 'required|string|max:255',
            'national_code' => [
                'required',
                'regex:/^[0-9]{10}$/',
                Rule::unique('customers')->ignore($customerId), // Ignore the current customer when updating
            ],
            'passport_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers')->ignore($customerId), // Ignore the current customer when updating
            ],
            'passport_expiry_date' => 'required|date|after_or_equal:today',
            'nationality' => 'required|string|max:100',
            'license_number' => 'required|string|max:50',
        ];
    }


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
        'email.unique' => 'This email is already registered in the system.',

        'phone.required' => 'Phone number is required.',
        'phone.regex' => 'Please provide a valid phone number.',
        'address.required' => 'Address is required.',
        'address.string' => 'Address must be a string.',
        'address.max' => 'Address cannot be longer than 255 characters.',

        'national_code.required' => 'National Code is required.',
        'national_code.regex' => 'National Code must be a 10-digit number.',
        'national_code.unique' => 'This National Code is already registered in the system.',

        'passport_number.required' => 'Passport Number is required.',
        'passport_number.string' => 'Passport Number must be a string.',
        'passport_number.max' => 'Passport Number cannot be longer than 50 characters.',
        'passport_number.unique' => 'This Passport Number is already registered in the system.',

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

        // Start a database transaction
        DB::beginTransaction();

        try {



            // Update or create the customer
            $customerData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_code' => $this->national_code,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'passport_number' => $this->passport_number,
                'passport_expiry_date' => $this->passport_expiry_date,
                'nationality' => $this->nationality,
                'license_number' => $this->license_number,
            ];

            $customer = Customer::updateOrCreate(
                [
                    'email' => $this->email, // جستجو برای ایمیل مشابه
                    'national_code' => $this->national_code // Match existing customer by national code
                ],
                $customerData
            );

            // Update or create the contract
            $contractData = [
                'user_id' => null,
                'customer_id' => $customer->id,
                'car_id' => $this->selectedCarId,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'total_price' => $this->total_price,
                'status' => $this->status,
                'notes' => $this->notes ?? null,
            ];

            if ($this->contract) {
                // Update existing contract
                $this->contract->update($contractData);
                session()->flash('info', 'Contract Updated successfully!');
            } else {
                // Create a new contract
                $this->contract = Contract::create($contractData);
                session()->flash('message', 'Contract saved successfully!');
            }

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            session()->flash('error', 'An error occurred while saving the contract.');
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-form');
    }
}
