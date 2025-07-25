<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use Livewire\Component;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class RentalRequestCreate extends Component
{
    // تمام پراپرتی‌های مربوط به ایجاد قرارداد
    public $selectedBrand;
    public $selectedCarId;
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
    public function mount()
    {
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->pickup_date = now()->format('Y-m-d\TH:i');
        $this->return_date = now()->addDay()->format('Y-m-d\TH:i');
    }

    // تمام متدهای محاسباتی (calculateCosts, calculateRentalDays, ...) همانند کامپوننت اصلی
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

        // محاسبه بیمه
        if ($this->selected_insurance) {
            if ($this->selected_insurance === 'basic_insurance') {
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

    protected function rules()
    {
        return [
            'selectedBrand' => ['required', 'string'],
            'selectedModelId' => ['required', 'exists:car_models,id'],
            'selectedCarId' => ['required', 'exists:cars,id'],
            'pickup_location' => 'required',
            'return_location' => 'required',
            'pickup_date' => 'required',
            'return_date' => 'required',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
            'messenger_phone' => 'required|regex:/^[0-9]{10,15}$/',
            'address' => 'nullable|string|max:255',
            'national_code' => 'required',
            'passport_number' => 'nullable|string|max:50',
            'passport_expiry_date' => 'nullable|date|after_or_equal:today',
            'nationality' => 'required|string|max:100',
            'license_number' => 'nullable|string|max:50',
        ];
    }

    public function submit()
    {
        $this->validate();
        DB::beginTransaction();
        try {
            $this->calculateCosts();

            $customerData = [/* ... */];
            $customer = Customer::updateOrCreate([/* ... */], $customerData);

            $contractData = [/* ... */];
            $contract = Contract::create($contractData);
            $contract->changeStatus('pending', auth()->id());

            $this->storeContractCharges($contract);

            DB::commit();
            session()->flash('message', 'Contract created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
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

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-create');
    }
}
