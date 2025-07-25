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

class RentalRequestEdit extends Component
{
    public $cars;
    public $carModels;
    public $selectedBrand;
    public $selectedCarId;
    public $selectedCar;
    public $total_price;
    public $agent_sale;
    public $pickup_location;
    public $return_location;
    public $return_date;
    public $pickup_date;
    public $note;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $messenger_phone;
    public $address;
    public $national_code;
    public $passport_number;
    public $passport_expiry_date;
    public $nationality;
    public $license_number;
    public $filteredCarModels = [];
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
    public $rental_days = 1;
    public $dailyRate;
    public $base_price;
    public $brands = [];
    public $models = [];
    public $selectedModelId;
    public $contract;
    public $contractId;


    public $carsForModel = [];
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


    public function mount($contractId)
    {
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->contract = Contract::findOrFail($contractId);

        // Initialize properties from contract
        $this->initializeFromContract();
        $this->calculateCosts();
        $this->loadChargesFromDatabase($contractId);
    }
    private function loadChargesFromDatabase($contractid)
    {
        $charges = ContractCharges::where('contract_id', $contractid)->get();
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

    private function initializeFromContract()
    {
        $this->total_price = $this->contract->total_price;
        $this->agent_sale = $this->contract->agent_sale;
        $this->pickup_location = $this->contract->pickup_location;
        $this->return_location = $this->contract->return_location;
        $this->pickup_date = \Carbon\Carbon::parse($this->contract->pickup_date)->format('Y-m-d\TH:i');
        $this->return_date = \Carbon\Carbon::parse($this->contract->return_date)->format('Y-m-d\TH:i');
        $this->note = $this->contract->note;

        // Customer data
        $customer = $this->contract->customer()->firstOrFail();

        $this->first_name = $customer->first_name;
        $this->last_name = $customer->last_name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->messenger_phone = $customer->messenger_phone;
        $this->address = $customer->address;
        $this->national_code = $customer->national_code;
        $this->passport_number = $customer->passport_number;
        $this->passport_expiry_date = $customer->passport_expiry_date;
        $this->nationality = $customer->nationality;
        $this->license_number = $customer->license_number;

        // Car selection
        $this->selectedBrand = $this->contract->car->carModel->brand;
        $this->loadModels();
        $this->selectedModelId = $this->contract->car->car_model_id;
        $this->loadCars();
        $this->selectedCarId = $this->contract->car->id;

        // Documents and payments
        $this->customerDocumentsCompleted = (bool)$this->contract->customerDocument;
        $this->paymentsExist = $this->contract->payments()->exists();

        // Services and insurance
        $this->selected_services = $this->contract->selected_services ?? [];
        $this->selected_insurance = $this->contract->selected_insurance;
    }


    // ... Keep all calculation methods unchanged (calculateCosts, calculateRentalDays, etc.) ...
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
            $car = $this->getSelectedCar();
            $this->dailyRate = $this->getCarDailyRate($car, $this->rental_days);
            $this->base_price = $this->dailyRate * $this->rental_days;
        } else {
            $this->dailyRate = 0;
            $this->base_price = 0;
        }
    }
    private function getSelectedCar()
    {
        return Car::find($this->selectedCarId);
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
                $car = $this->getSelectedCar();
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
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'messenger_phone' => 'required|regex:/^[0-9]{10,15}$/',
            'address' => 'nullable|string|max:255',
            'national_code' => [
                'required',
                // 'regex:/^[0-9]{10}$/',
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




    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        if ($this->isCostRelatedField($propertyName)) {
            $this->calculateCosts();
        }
    }

    private function isCostRelatedField($propertyName)
    {
        $costRelatedFields = [
            'pickup_date',
            'return_date',
            'selectedCarId',
            'pickup_location',
            'return_location',
            'selected_services',
            'selected_insurance'
        ];

        return in_array($propertyName, $costRelatedFields) ||
            Str::startsWith($propertyName, 'selected_services.');
    }

    public function submit()
    {
        $this->validate();
        DB::beginTransaction();

        try {
            $this->calculateCosts();
            $this->updateCustomer();
            $this->updateContract();
            $this->storeContractCharges($this->contract); // Pass the contract instance here
            DB::commit();
            session()->flash('info', 'Contract Updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
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

    private function updateCustomer()
    {
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

        $this->contract->customer->update($customerData);
    }

    private function updateContract()
    {
        $contractData = [
            'car_id' => $this->selectedCarId,
            'total_price' => $this->final_total,
            'agent_sale' => $this->agent_sale,
            'pickup_location' => $this->pickup_location,
            'return_location' => $this->return_location,
            'pickup_date' => $this->pickup_date,
            'return_date' => $this->return_date,
            'selected_services' => $this->selected_services,
            'selected_insurance' => $this->selected_insurance,
            'notes' => $this->note,
        ];

        $this->contract->update($contractData);
    }

    public function updatedSelectedBrand()
    {
        $this->selectedModelId = null;
        $this->selectedCarId = null;
        $this->loadModels();
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

    private function loadModels()
    {
        $this->models = [];
        if ($this->selectedBrand) {
            $this->models = CarModel::where('brand', $this->selectedBrand)
                ->orderBy('model')
                ->get();
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

    public function updatedSelectedModelId()
    {
        $this->selectedCarId = null;
        $this->loadCars();
        $this->calculateCosts();
    }

    public function updatedSelectedCarId()
    {
        $this->calculateCosts();
    }
}
