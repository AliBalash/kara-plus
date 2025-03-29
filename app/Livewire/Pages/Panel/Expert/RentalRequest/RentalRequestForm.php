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
    public $selectedCar; // Store the selected car 

    public $total_price;
    public $agent_sale;
    public $pickup_location;
    public $return_location;
    public $return_date;
    public $pickup_date;
    public $note;

    public $first_name, $last_name, $email, $phone, $messenger_phone;
    public $address;
    public $national_code;
    public $passport_number;
    public $passport_expiry_date;
    public $nationality;
    public $license_number;
    // Dynamic array for car models of the selected brand
    public $filteredCarModels = [];

    // Mount method to load initial data
    public $customerDocumentsCompleted = false;
    public $paymentsExist = false;

    public function mount($contractId = null)
    {
        $this->carModels = CarModel::all();
        if ($contractId) {
            $this->contract = Contract::findOrFail($contractId);
            $this->total_price = $this->contract->total_price;
            $this->agent_sale = $this->contract->agent_sale;
            $this->pickup_location = $this->contract->pickup_location;
            $this->return_location = $this->contract->return_location;
            $this->pickup_date = \Carbon\Carbon::parse($this->contract->pickup_date)->format('Y-m-d\TH:i');
            $this->return_date = \Carbon\Carbon::parse($this->contract->return_date)->format('Y-m-d\TH:i');
            $this->note = $this->contract->note;
            $this->first_name = $this->contract->customer->first_name;
            $this->last_name = $this->contract->customer->last_name;
            $this->email = $this->contract->customer->email;
            $this->phone = $this->contract->customer->phone;
            $this->messenger_phone = $this->contract->customer->messenger_phone;
            $this->address = $this->contract->customer->address;
            $this->national_code = $this->contract->customer->national_code;
            $this->passport_number = $this->contract->customer->passport_number;
            $this->passport_expiry_date = $this->contract->customer->passport_expiry_date;
            $this->nationality = $this->contract->customer->nationality;
            $this->license_number = $this->contract->customer->license_number;

            // بررسی مدارک مشتری
            if ($this->contract->customer && $this->contract->customerDocument) {
                $this->customerDocumentsCompleted = true;
            }

            // بررسی پرداخت‌ها
            if ($this->contract->payments()->exists()) {
                $this->paymentsExist = true;
            }

            // Set initial selected values based on the contract's car
            $this->selectedBrand = $this->contract->car->carModel->id;
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
            'total_price' => 'numeric|min:0',
            'pickup_location' => 'required|',
            'return_location' => 'required|',
            'pickup_date' => 'required|',
            'return_date' => 'required|',
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
            'messenger_phone' => 'required|regex:/^[0-9]{10,15}$/',
            'address' => 'required|string|max:255',
            'national_code' => [
                'required',
                // 'regex:/^[0-9]{10}$/',
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
        'total_price.required' => 'The total price field is required.',
        'total_price.numeric' => 'The total price must be a valid number.',
        'total_price.min' => 'The total price cannot be negative.',
        'pickup_location.required' => 'The pickup location field is required.',
        'return_location.required' => 'The return location field is required.',
        'pickup_date.required' => 'The return pickup date is required.',
        'return_date.required' => 'The return date field is required.',
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
        'messenger_phone.required' => 'messenger phone number is required.',
        'phone.regex' => 'Please provide a valid phone number.',
        'messenger_phone.regex' => 'Please provide a valid messenger phone number.',
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



    // Calculate the total price dynamically based on user inputs
    public function updated($propertyName)
    {
        if ($propertyName === 'pickup_date' || $propertyName === 'return_date' || $propertyName === 'selectedCarId') {
            $this->calculateTotalPrice();
        }
    }

    public function calculateTotalPrice()
    {
        if ($this->pickup_date && $this->return_date && $this->selectedCarId) {
            $pickupDate = \Carbon\Carbon::parse($this->pickup_date);
            $returnDate = \Carbon\Carbon::parse($this->return_date);

            // Calculate the difference in days between pickup and return date
            $days = $pickupDate->diffInDays($returnDate);

            // Get the selected car's price per day
            $car = Car::find($this->selectedCarId);
            $pricePerDay = $car->price_per_day;

            // Calculate total price
            $totalPrice = $days * $pricePerDay;

            // Update the total price
            $this->total_price = $totalPrice;
        }
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
                'messenger_phone' => $this->messenger_phone,
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
                'total_price' => $this->total_price,
                'agent_sale' => $this->agent_sale,
                'pickup_location' => $this->pickup_location,
                'return_location' => $this->return_location,
                'pickup_date' => $this->pickup_date,
                'return_date' => $this->return_date,
                'notes' => $this->notes ?? null,
            ];


            if ($this->contract) {
                // Update existing contract
                $this->contract->update($contractData);
                session()->flash('info', 'Contract Updated successfully!');
            } else {
                // Create a new contract
                $this->contract = Contract::create($contractData);

                // ثبت وضعیت اولیه به عنوان pending
                $this->contract->changeStatus('pending', auth()->id());

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
