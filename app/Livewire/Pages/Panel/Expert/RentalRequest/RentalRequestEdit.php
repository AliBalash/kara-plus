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
    public $notes;
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
    public $selected_insurance = 'basic_insurance';
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
    public $kardo_required;
    public $payment_on_delivery;
    public $apply_discount = false;
    public $custom_daily_rate = null;
    public $carsForModel = [];
    public $ldw_daily_rate = 0;
    public $scdw_daily_rate = 0;

    private $locationCosts = [
        'UAE/Dubai/Clock Tower/Main Branch' => ['under_3' => 0, 'over_3' => 0],
        'UAE/Dubai/Downtown' => ['under_3' => 45, 'over_3' => 45],
        'UAE/Dubai/Dubai Airport/Terminal 1' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Dubai Airport/Terminal 2' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Dubai Airport/Terminal 3' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Al Maktoum Airport' => ['under_3' => 190, 'over_3' => 190],
        'UAE/Dubai/Jumeirah 1, 2, 3' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JBR' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Marina' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JLT' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JVC' => ['under_3' => 60, 'over_3' => 60],
        'UAE/Dubai/Damac Hills' => ['under_3' => 60, 'over_3' => 60],
        'UAE/Dubai/Palm' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Hatta' => ['under_3' => 150, 'over_3' => 150],
        'UAE/Sharjah Airport' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Ajman' => ['under_3' => 100, 'over_3' => 100],
        'UAE/Abu Dhabi Airport' => ['under_3' => 200, 'over_3' => 200],
    ];

    public $services = [];

    public function mount($contractId)
    {
        $this->services = config('carservices');
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->contract = Contract::findOrFail($contractId);

        if ($this->contract->used_daily_rate) {
            $this->custom_daily_rate = $this->contract->used_daily_rate;
            // $this->apply_discount = true;
        }

        $this->initializeFromContract();
        $this->loadChargesFromDatabase($contractId);
        $this->calculateCosts();
    }

    private function loadChargesFromDatabase($contractId)
    {
        $charges = ContractCharges::where('contract_id', $contractId)->get();
        $this->selected_services = [];
        $this->selected_insurance = null; // Default to null for "No Additional Insurance"

        foreach ($charges as $charge) {
            if ($charge->type === 'addon' || $charge->type === 'insurance') {
                if (array_key_exists($charge->title, $this->services)) {
                    if (in_array($charge->title, ['ldw_insurance', 'scdw_insurance'])) {
                        $this->selected_insurance = $charge->title;
                    } elseif ($charge->title !== 'basic_insurance') {
                        $this->selected_services[] = $charge->title;
                    }
                }
            }
        }

        // If no insurance charge is found, set to null to reflect "No Additional Insurance"
        if (!$this->selected_insurance) {
            $this->selected_insurance = null;
        }

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
        $this->notes = $this->contract->notes;
        $this->kardo_required = $this->contract->kardo_required;
        $this->payment_on_delivery = $this->contract->payment_on_delivery;

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
    }

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
            if ($return->lte($pickup)) {
                $this->rental_days = 1;
                return;
            }
            $seconds = $return->getTimestamp() - $pickup->getTimestamp();
            $days = (int) ceil($seconds / 86400);
            $this->rental_days = max(1, $days);
        } else {
            $this->rental_days = 1;
        }
    }

    private function calculateBasePrice()
    {
        if ($this->selectedCarId && $this->rental_days) {
            $car = Car::find($this->selectedCarId);
            $standardRate = $this->getCarDailyRate($car, $this->rental_days);
            $this->dailyRate = $this->apply_discount && $this->custom_daily_rate
                ? $this->custom_daily_rate
                : ($this->contract->used_daily_rate ?? $standardRate);
            $this->base_price = round($this->dailyRate * $this->rental_days, 2);
            $this->ldw_daily_rate = $this->getInsuranceDailyRate($car, 'ldw', $this->rental_days);
            $this->scdw_daily_rate = $this->getInsuranceDailyRate($car, 'scdw', $this->rental_days);
        } else {
            $this->dailyRate = 0;
            $this->base_price = 0;
            $this->ldw_daily_rate = 0;
            $this->scdw_daily_rate = 0;
        }
    }

    private function getSelectedCar()
    {
        return Car::find($this->selectedCarId);
    }

    private function getCarDailyRate(Car $car, int $days): float
    {
        if ($days >= 28) return $car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short;
        if ($days >= 7) return $car->price_per_day_mid ?? $car->price_per_day_short;
        return $car->price_per_day_short;
    }

    private function getInsuranceDailyRate(Car $car, string $type, int $days): float
    {
        $prefix = $type . '_price_';
        if ($days >= 28) return $car->{$prefix . 'long'} ?? $car->{$prefix . 'mid'} ?? $car->{$prefix . 'short'} ?? 0;
        if ($days >= 7) return $car->{$prefix . 'mid'} ?? $car->{$prefix . 'short'} ?? 0;
        return $car->{$prefix . 'short'} ?? 0;
    }

    private function calculateTransferCosts()
    {
        $this->transfer_costs = [
            'pickup' => $this->calculateLocationFee($this->pickup_location, $this->rental_days),
            'return' => $this->calculateLocationFee($this->return_location, $this->rental_days),
            'total' => 0
        ];
        $this->transfer_costs['total'] = $this->transfer_costs['pickup'] + $this->transfer_costs['return'];
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

        foreach ($this->selected_services as $serviceId) {
            $service = $this->services[$serviceId] ?? null;
            if ($service) {
                $amount = $service['amount'] ?? 0;
                $servicesTotal += $service['per_day'] ? $amount * $days : $amount;
            }
        }

        if ($this->selected_insurance && in_array($this->selected_insurance, ['ldw_insurance', 'scdw_insurance']) && $this->selectedCarId) {
            $car = Car::find($this->selectedCarId);
            if ($car) {
                $insuranceDaily = $this->selected_insurance === 'ldw_insurance'
                    ? $this->getInsuranceDailyRate($car, 'ldw', $days)
                    : $this->getInsuranceDailyRate($car, 'scdw', $days);
                $insuranceTotal += $insuranceDaily * $days;
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
        $customerId = $this->contract ? $this->contract->customer->id : null;
        return [
            'selectedBrand' => ['required', 'string'],
            'selectedModelId' => ['required', 'exists:car_models,id'],
            'selectedCarId' => ['required', 'exists:cars,id'],
            'pickup_location' => ['required', Rule::in(array_keys($this->locationCosts))],
            'return_location' => ['required', Rule::in(array_keys($this->locationCosts))],
            'pickup_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customerId),
            ],
            'phone' => ['required', 'max:15'],
            'messenger_phone' => ['required', 'max:15'],
            'address' => ['nullable', 'string', 'max:255'],
            'national_code' => ['required'],
            'passport_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers')->ignore($customerId),
            ],
            'passport_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'selected_insurance' => ['nullable', Rule::in(['', 'basic_insurance', 'ldw_insurance', 'scdw_insurance'])],
            'kardo_required' => ['boolean'],
            'payment_on_delivery' => ['boolean'],
            'apply_discount' => ['boolean'],
            'custom_daily_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected $messages = [
        'selectedBrand.required' => 'The car brand field is required.',
        'selectedModelId.required' => 'The car model field is required.',
        'selectedModelId.exists' => 'The selected car model is invalid.',
        'selectedCarId.required' => 'The car field is required.',
        'selectedCarId.exists' => 'The selected car is invalid.',
        'pickup_location.required' => 'The pickup location is required.',
        'pickup_location.in' => 'The selected pickup location is invalid.',
        'return_location.required' => 'The return location is required.',
        'return_location.in' => 'The selected return location is invalid.',
        'pickup_date.required' => 'The pickup date and time is required.',
        'pickup_date.date' => 'Please provide a valid date for pickup.',
        'return_date.required' => 'The return date and time is required.',
        'return_date.date' => 'Please provide a valid date for return.',
        'return_date.after_or_equal' => 'The return date must be after or equal to the pickup date.',
        'first_name.required' => 'First name is required.',
        'first_name.string' => 'First name must be a string.',
        'first_name.max' => 'First name cannot be longer than 255 characters.',
        'last_name.required' => 'Last name is required.',
        'last_name.string' => 'Last name must be a string.',
        'last_name.max' => 'Last name cannot be longer than 255 characters.',
        'email.email' => 'Please provide a valid email address.',
        'email.max' => 'Email cannot be longer than 255 characters.',
        'email.unique' => 'This email is already registered.',
        'phone.required' => 'Phone number is required.',
        'phone.max' => 'Phone number cannot be longer than 15 characters.',
        'messenger_phone.required' => 'Messenger phone number is required.',
        'messenger_phone.max' => 'Messenger phone number cannot be longer than 15 characters.',
        'address.string' => 'Address must be a string.',
        'address.max' => 'Address cannot be longer than 255 characters.',
        'national_code.required' => 'National Code is required.',
        'passport_number.string' => 'Passport Number must be a string.',
        'passport_number.max' => 'Passport Number cannot be longer than 50 characters.',
        'passport_number.unique' => 'This passport number is already registered.',
        'passport_expiry_date.date' => 'Please provide a valid date for Passport Expiry.',
        'passport_expiry_date.after_or_equal' => 'Passport Expiry Date cannot be in the past.',
        'nationality.required' => 'Nationality is required.',
        'nationality.string' => 'Nationality must be a string.',
        'nationality.max' => 'Nationality cannot be longer than 100 characters.',
        'license_number.string' => 'License Number must be a string.',
        'license_number.max' => 'License Number cannot be longer than 50 characters.',
        'selected_insurance.in' => 'The selected insurance option is invalid.',
        'kardo_required.boolean' => 'The KARDO required field must be a boolean value.',
        'payment_on_delivery.boolean' => 'The payment on delivery field must be a boolean value.',
        'apply_discount.boolean' => 'The apply discount field must be a boolean value.',
        'custom_daily_rate.numeric' => 'The custom daily rate must be a number.',
        'custom_daily_rate.min' => 'The custom daily rate cannot be negative.',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        if ($this->isCostRelatedField($propertyName) || in_array($propertyName, ['apply_discount', 'custom_daily_rate'])) {
            $this->calculateCosts();
        }
        if ($propertyName === 'selectedModelId') {
            $this->loadCars();
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
            $this->storeContractCharges($this->contract);

            $oldTotal = $this->contract->total_price;
            $newTotal = $this->final_total;
            if ($newTotal > $oldTotal) {
                session()->flash('info', "Extension cost: " . ($newTotal - $oldTotal) . " AED");
            }
            DB::commit();
            session()->flash('info', 'Contract Updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    private function storeContractCharges(Contract $contract)
    {
        ContractCharges::where('contract_id', $contract->id)->delete();

        ContractCharges::create([
            'contract_id' => $contract->id,
            'title' => 'base_rental',
            'amount' => $this->base_price,
            'type' => 'base',
            'description' => ((int)$this->rental_days) . " روز × " . number_format($this->dailyRate, 2) . " درهم" . ($this->apply_discount ? ' (with discount)' : ''),
        ]);

        if ($this->transfer_costs['pickup'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'pickup_transfer',
                'amount' => $this->transfer_costs['pickup'],
                'type' => 'location_fee',
                'description' => $this->pickup_location
            ]);
        }

        if ($this->transfer_costs['return'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'return_transfer',
                'amount' => $this->transfer_costs['return'],
                'type' => 'location_fee',
                'description' => $this->return_location
            ]);
        }

        foreach ($this->selected_services as $serviceId) {
            if (!array_key_exists($serviceId, $this->services)) continue;
            $service = $this->services[$serviceId];
            $amount = $service['per_day'] ? $service['amount'] * $this->rental_days : $service['amount'];

            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $serviceId,
                'amount' => $amount,
                'type' => 'addon',
                'description' => $service['per_day'] ? "{$this->rental_days} روز × {$service['amount']} درهم" : 'یک‌بار هزینه'
            ]);
        }

        if ($this->selected_insurance && in_array($this->selected_insurance, ['ldw_insurance', 'scdw_insurance'])) {
            $insuranceAmount = 0;
            $car = Car::find($this->selectedCarId);
            if ($car) {
                $insuranceDaily = $this->getInsuranceDailyRate($car, str_replace('_insurance', '', $this->selected_insurance), $this->rental_days);
                $insuranceAmount = $insuranceDaily * $this->rental_days;
            }

            if ($insuranceAmount > 0) {
                ContractCharges::create([
                    'contract_id' => $contract->id,
                    'title' => $this->selected_insurance,
                    'amount' => $insuranceAmount,
                    'type' => 'insurance',
                    'description' => "{$this->rental_days} روز"
                ]);
            }
        }

        if ($this->tax_amount > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'tax',
                'amount' => $this->tax_amount,
                'type' => 'tax',
                'description' => '۵٪ مالیات بر ارزش افزوده'
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
            'notes' => $this->notes,
            'kardo_required' => $this->kardo_required,
            'used_daily_rate' => $this->dailyRate,
            'discount_note' => $this->apply_discount ? "Discount applied: {$this->custom_daily_rate} AED instead of standard rate" : null,
            'payment_on_delivery' => $this->payment_on_delivery ?? true,
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

    private function getCarReservations($carId)
    {
        if (!$carId) {
            return [];
        }
        $reservations = Contract::where('car_id', $carId)
            ->whereIn('current_status', ['pending', 'assigned', 'under_review', 'reserved', 'delivery', 'agreement_inspection', 'awaiting_return'])
            ->where('return_date', '>=', now())
            ->select('pickup_date', 'return_date')
            ->get()
            ->map(function ($contract) {
                return [
                    'pickup_date' => Carbon::parse($contract->pickup_date)->format('Y-m-d H:i'),
                    'return_date' => Carbon::parse($contract->return_date)->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
        return $reservations;
    }

    public function changeStatusToReserve($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        if ($contract->user_id === auth()->id()) {
            $contract->changeStatus('reserved', auth()->id());
            if ($contract->car) {
                $contract->car->update(['status' => 'reserved']);
            }
            session()->flash('success', 'Status changed to Reserved successfully.');
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
        // Reset custom rate when car changes
        $this->custom_daily_rate = null;
        $this->apply_discount = false;
    }

    public function render()
    {
        // مستقیماً $this->services رو آپدیت می‌کنیم
        if ($this->selectedCarId) {
            $car = Car::find($this->selectedCarId);
            if ($car) {
                $this->services['ldw_insurance']['amount'] = $car->ldw_price ?? 0;
                $this->services['scdw_insurance']['amount'] = $car->scdw_price ?? 0;
            }
        }

        $services = array_map(function ($service) {
            $service['label'] = $service['label_en'];
            return $service;
        }, $this->services);

        return view('livewire.pages.panel.expert.rental-request.rental-request-edit', [
            'brands' => $this->brands,
            'services' => $services,
            'ldw_daily_rate' => $this->ldw_daily_rate,
            'scdw_daily_rate' => $this->scdw_daily_rate,
        ]);
    }
}
