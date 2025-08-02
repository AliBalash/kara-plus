<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

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

    public $selected_services = [];
    public $selected_insurance = null;
    public $services_total = 0;
    public $insurance_total = 0;

    public $transfer_costs = ['pickup' => 0, 'return' => 0, 'total' => 0];
    public $tax_rate = 0.05;
    public $tax_amount = 0;
    public $subtotal = 0;
    public $final_total = 0;

    public $rental_days = 1; // مقدار پیش‌فرض 1 روز
    public $dailyRate;
    public $base_price;

    public $brands = []; // لیست برندها (رشته)
    public $models = []; // لیست مدل‌های مربوط به برند انتخاب شده
    public $selectedModelId; // ID مدل انتخاب شده
    public $carsForModel = []; // لیست خودروهای مربوط به مدل انتخاب شده

    public $services = [
        'basic_insurance' => [
            'label'   => 'بیمه پایه',
            'amount'  => 0,
            'per_day' => false
        ],
        'child_seat' => [
            'label'   => 'صندلی کودک',
            'amount'  => 20,
            'per_day' => true
        ],
        'additional_driver' => [
            'label'   => 'راننده اضافه',
            'amount'  => 20,
            'per_day' => false
        ]
    ];

    private $locationCosts = [
        'UAE/Dubai/Clock Tower/Main Branch' => [
            'under_3' => 0,
            'over_3' => 0
        ],
        'UAE/Dubai/Downtown' => [
            'under_3' => 50,
            'over_3' => 50
        ],
        'UAE/Dubai/Dubai Airport/Terminal 1' => [
            'under_3' => 50,
            'over_3' => 0
        ],
        'UAE/Dubai/Dubai Airport/Terminal 2' => [
            'under_3' => 50,
            'over_3' => 0
        ],
        'UAE/Dubai/Dubai Airport/Terminal 3' => [
            'under_3' => 50,
            'over_3' => 0
        ],
        'UAE/Dubai/Jumeirah 1, 2, 3' => [
            'under_3' => 45,
            'over_3' => 45
        ],
        'UAE/Dubai/JBR' => [
            'under_3' => 45,
            'over_3' => 45
        ],
        'UAE/Dubai/Marina' => [
            'under_3' => 45,
            'over_3' => 45
        ],
        'UAE/Dubai/JLT' => [
            'under_3' => 45,
            'over_3' => 45
        ],
        'UAE/Dubai/JVC' => [
            'under_3' => 60,
            'over_3' => 60
        ],
        'UAE/Dubai/Damac Hills' => [
            'under_3' => 60,
            'over_3' => 60
        ],
        'UAE/Dubai/Palm' => [
            'under_3' => 70,
            'over_3' => 70
        ],
        'UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more' => [
            'under_3' => 70,
            'over_3' => 70
        ],
        'UAE/Sharjah Airport' => [
            'under_3' => 100,
            'over_3' => 100
        ],
        'UAE/Abu Dhabi Airport' => [
            'under_3' => 200,
            'over_3' => 200
        ]
    ];


    public function calculateCosts()
    {
        $this->calculateRentalDays();
        $this->calculateBasePrice();
        $this->calculateTransferCosts();
        $this->calculateServicesTotal();
        $this->calculateTaxAndTotal();
    }

    private function calculateRentalDays()
    {
        if ($this->pickup_date && $this->return_date) {
            $pickup = Carbon::parse($this->pickup_date);
            $return = Carbon::parse($this->return_date);
            $this->rental_days = max(1, $pickup->diffInDays($return));
        } else {
            $this->rental_days = 1; // مقدار پیش‌فرض اگر تاریخ‌ها تنظیم نشده باشند
        }
    }

    private function calculateBasePrice()
    {
        if ($this->selectedCarId && $this->rental_days) {
            $car = Car::find($this->selectedCarId);
            $this->dailyRate = $this->getCarDailyRate($car, $this->rental_days);
            $this->base_price = $this->dailyRate * $this->rental_days;
        } else {
            $this->dailyRate = 0;
            $this->base_price = 0;
        }
    }

    private function getCarDailyRate(Car $car, int $days): float
    {
        if ($days >= 21) return $car->price_per_day_long;
        if ($days >= 7) return $car->price_per_day_mid;
        return $car->price_per_day_short;
    }

    private function calculateTransferCosts()
    {
        $this->transfer_costs = [
            'pickup' => $this->calculateLocationFee($this->pickup_location, $this->rental_days),
            'return' => $this->calculateLocationFee($this->return_location, $this->rental_days),
            'total' => 0
        ];

        $this->transfer_costs['total'] =
            $this->transfer_costs['pickup'] + $this->transfer_costs['return'];
    }

    private function calculateLocationFee($location, $days)
    {
        $feeType = ($days < 3) ? 'under_3' : 'over_3';
        return $this->locationCosts[$location][$feeType] ?? 0;
    }

    private function calculateServicesTotal()
    {
        $servicesTotal = 0;
        $insuranceTotal = 0;
        $days = $this->rental_days;

        // محاسبه خدمات
        foreach ($this->selected_services as $serviceId) {
            $service = $this->services[$serviceId] ?? null;
            if ($service) {
                $servicesTotal += $service['per_day']
                    ? $service['amount'] * $days
                    : $service['amount'];
            }
        }

        // محاسبه بیمه (اضافه کردن شرط برای بیمه پایه)
        if ($this->selected_insurance) {
            if ($this->selected_insurance === 'basic_insurance') {
                // محاسبه بیمه پایه از سرویس‌ها
                $insuranceTotal += $this->services['basic_insurance']['amount'] * $days;
            } elseif ($this->selectedCarId) {
                $car = Car::find($this->selectedCarId);
                if ($car) {
                    $insuranceAmount = ($this->selected_insurance === 'ldw_insurance')
                        ? $car->ldw_price
                        : $car->scdw_price;
                    $insuranceTotal += $insuranceAmount * $days;
                }
            }
        }

        $this->services_total = $servicesTotal;
        $this->insurance_total = $insuranceTotal;
    }


    private function calculateTaxAndTotal()
    {
        $this->subtotal = $this->base_price + $this->services_total + $this->insurance_total + $this->transfer_costs['total'];
        $this->tax_amount = round($this->subtotal * $this->tax_rate);
        $this->final_total = $this->subtotal + $this->tax_amount;
    }



    protected $listeners = [
        'refreshContracts' => '$refresh',
    ];

    public function mount($contractId = null)
    {
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();

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

            $this->selectedBrand = $this->contract->car->carModel->brand;
            $this->loadModels();
            $this->selectedModelId = $this->contract->car->car_model_id;
            $this->loadCars();
            $this->selectedCarId = $this->contract->car->id;

            // بررسی مدارک مشتری
            if ($this->contract->customer && $this->contract->customerDocument) {
                $this->customerDocumentsCompleted = true;
            }

            $this->selected_services = $this->contract->selected_services ?? [];
            $this->selected_insurance = $this->contract->selected_insurance;


            // بررسی پرداخت‌ها
            if ($this->contract->payments()->exists()) {
                $this->paymentsExist = true;
            }

            // Set initial selected values based on the contract's car
            $this->selectedBrand = $this->contract->car->carModel->id;
            $this->selectedCarId = $this->contract->car->id;

            $this->calculateCosts();

            $this->loadChargesFromDatabase($this->contract);



            // Fetch cars based on initial brand selection
            $this->filterCarsByBrand($this->selectedBrand);
        }
    }


    private function loadChargesFromDatabase(Contract $contract)
    {
        $charges = ContractCharges::where('contract_id', $contract->id)->get();

        $this->selected_services = [];
        $this->selected_insurance = null;

        foreach ($charges as $charge) {
            // بازیابی خدمات
            if ($charge->type === 'addon') {
                if ($charge->title === 'Basic Insurance') {
                    $this->selected_insurance = 'basic_insurance';
                } elseif ($charge->title === 'LDW Insurance') {
                    $this->selected_insurance = 'ldw_insurance';
                } elseif ($charge->title === 'Full Coverage (SCDW)') {
                    $this->selected_insurance = 'scdw_insurance';
                } else {
                    // پیدا کردن کلید سرویس بر اساس عنوان
                    foreach ($this->services as $key => $service) {
                        if ($service['label'] === $charge->title) {
                            $this->selected_services[] = $key;
                            break;
                        }
                    }
                }
            }
        }

        // محاسبات را پس از بارگذاری انجام دهید
        $this->calculateCosts();
    }


    // Add other fields for car and customer information as necessary
    // Add this to the validation rules
    protected function rules()
    {
        // If editing, get the customer ID (if the contract exists, fetch the customer's ID)
        $customerId = $this->contract ? $this->contract->customer->id : null;
        return [
            'selectedBrand' => ['required', 'string'], // یا اگر قراره exists باشه، باید اصلاح بشه
            'selectedModelId' => ['required', 'exists:car_models,id'],
            'selectedCarId' => ['required', 'exists:cars,id'],
            'pickup_location' => 'required|',
            'return_location' => 'required|',
            'pickup_date' => 'required|',
            'return_date' => 'required|',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                // Rule::unique('customers')->ignore($customerId), // Ignore the current customer when updating
            ],
            'phone' => 'required|max:15',
            'messenger_phone' => 'required|max:15',
            'address' => 'nullable|string|max:255',
            'national_code' => [
                'required',
            ],
            'passport_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers')->ignore($customerId), // Ignore the current customer when updating
            ],
            'passport_expiry_date' => 'nullable|date|after_or_equal:today',
            'nationality' => 'required|string|max:100',
            'license_number' => 'nullable|string|max:50',
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

        $this->validateOnly($propertyName);

        // فیلدهای مؤثر بر محاسبات قیمت
        $costRelatedFields = [
            'pickup_date',
            'return_date',
            'selectedCarId',
            'pickup_location',
            'return_location',
            'selected_services',
            'selected_insurance'
        ];

        if (
            in_array($propertyName, $costRelatedFields) ||
            Str::startsWith($propertyName, 'selected_services.') ||
            $propertyName === 'selected_insurance'
        ) {
            $this->calculateCosts();
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

    public function updatedSelectedBrand()
    {
        $this->selectedModelId = null;
        $this->selectedCarId = null;
        $this->loadModels();
        $this->calculateCosts();
    }

    private function loadModels()
    {
        $this->models = [];
        if ($this->selectedBrand) {
            $this->models = CarModel::where('brand', $this->selectedBrand)
                ->orderBy('model')
                ->get();
        }
    }

    public function updatedSelectedModelId()
    {
        $this->selectedCarId = null;
        $this->loadCars();
        $this->calculateCosts();
    }

    private function loadCars()
    {
        $this->carsForModel = [];
        if ($this->selectedModelId) {
            $this->carsForModel = Car::where('car_model_id', $this->selectedModelId)
                ->with('carModel')
                ->get();
        }
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

            $this->calculateCosts();

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
                    'phone' => $this->phone,
                    'email' => $this->email
                ],
                $customerData
            );

            // افزودن ذخیره‌سازی خدمات و بیمه
            $contractData['selected_services'] = $this->selected_services;
            $contractData['selected_insurance'] = $this->selected_insurance;
            $contractData['total_price'] = $this->final_total; // ذخیره مبلغ نهایی
            // Update or create the contract
            $contractData = [
                'user_id' => null,
                'customer_id' => $customer->id,
                'car_id' => $this->selectedCarId,
                'total_price' => $this->final_total,
                'agent_sale' => $this->agent_sale,
                'pickup_location' => $this->pickup_location,
                'return_location' => $this->return_location,
                'pickup_date' => $this->pickup_date,
                'return_date' => $this->return_date,
                'selected_services' => $this->selected_services,
                'selected_insurance' => $this->selected_insurance,
                'notes' => $this->notes ?? null,
            ];


            if ($this->contract) {
                $this->contract->update($contractData);
                session()->flash('info', 'Contract Updated successfully!');
            } else {
                $this->contract = Contract::create($contractData);
                $this->contract->changeStatus('pending', auth()->id());
                session()->flash('message', 'Contract saved successfully!');
            }

            $this->storeContractCharges($this->contract);


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


    private function storeContractCharges(Contract $contract)
    {
        // حذف رکوردهای قبلی
        ContractCharges::where('contract_id', $contract->id)->delete();

        // هزینه پایه اجاره
        ContractCharges::create([
            'contract_id' => $contract->id,
            'title' => 'Base Rental Cost',
            'amount' => $this->base_price,
            'type' => 'base',
            'description' => "{$this->rental_days} days × {$this->dailyRate} AED"
        ]);

        // هزینه‌های انتقال
        if ($this->transfer_costs['pickup'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'Pickup Transfer Cost',
                'amount' => $this->transfer_costs['pickup'],
                'type' => 'location_fee',
                'description' => $this->pickup_location
            ]);
        }

        if ($this->transfer_costs['return'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'Return Transfer Cost',
                'amount' => $this->transfer_costs['return'],
                'type' => 'location_fee',
                'description' => $this->return_location
            ]);
        }

        // خدمات اضافی
        foreach ($this->selected_services as $serviceId) {
            $service = $this->services[$serviceId] ?? null;
            if (!$service) continue;

            $amount = $service['per_day']
                ? $service['amount'] * $this->rental_days
                : $service['amount'];

            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $service['label'],
                'amount' => $amount,
                'type' => 'addon',
                'description' => $service['per_day']
                    ? "{$this->rental_days} days × {$service['amount']} AED"
                    : 'One-time fee'
            ]);
        }

        // بیمه‌ها
        if ($this->selected_insurance) {
            $insuranceLabel = '';
            $insuranceAmount = 0;

            if ($this->selected_insurance === 'basic_insurance') {
                $insuranceLabel = 'Basic Insurance';
                $insuranceAmount = $this->services['basic_insurance']['amount'] * $this->rental_days;
            } elseif ($this->selectedCarId) {
                $car = Car::find($this->selectedCarId);
                if ($car) {
                    if ($this->selected_insurance === 'ldw_insurance') {
                        $insuranceLabel = 'LDW Insurance';
                        $insuranceAmount = $car->ldw_price * $this->rental_days;
                    } elseif ($this->selected_insurance === 'scdw_insurance') {
                        $insuranceLabel = 'Full Coverage (SCDW)';
                        $insuranceAmount = $car->scdw_price * $this->rental_days;
                    }
                }
            }

            if ($insuranceAmount > 0) {
                ContractCharges::create([
                    'contract_id' => $contract->id,
                    'title' => $insuranceLabel,
                    'amount' => $insuranceAmount,
                    'type' => 'addon',
                    'description' => "{$this->rental_days} days"
                ]);
            }
        }

        // مالیات
        if ($this->tax_amount > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'Tax (5%)',
                'amount' => $this->tax_amount,
                'type' => 'tax',
                'description' => '5% VAT'
            ]);
        }
    }

    public function changeStatusToReserve($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if ($contract->user_id === auth()->id()) {
            // تغییر وضعیت قرارداد به 'reserved'
            $contract->changeStatus('reserved', auth()->id());

            // تغییر وضعیت خودرو به 'reserved'
            if ($contract->car) {
                $contract->car->update(['status' => 'reserved']);
            }

            session()->flash('message', 'Status changed to Reserved successfully.');
        } else {
            session()->flash('error', 'You are not authorized to perform this action.');
        }
    }


    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        if (is_null($contract->user_id)) {
            $contract->update([
                'user_id' => auth()->id(),
            ]);

            $contract->changeStatus('assigned', auth()->id());

            session()->flash('success', 'Contract assigned to you successfully.');


            $this->dispatch('refreshContracts');
        } else {
            session()->flash('error', 'This contract is already assigned to someone.');
        }
    }
}
