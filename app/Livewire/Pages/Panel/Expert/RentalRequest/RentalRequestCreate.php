<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Agent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\LocationCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Pages\Panel\Expert\RentalRequest\Concerns\HandlesServicePricing;
use App\Support\PhoneNumber;

class RentalRequestCreate extends Component
{
    use InteractsWithToasts;
    use HandlesServicePricing;
    public $selectedBrand;
    public $selectedModelId;
    public $selectedCarId;
    public $pickup_location;
    public $return_location;
    public $pickup_date;
    public $return_date;
    public $notes;
    public $driver_note;
    public $agent_id;
    public $submitted_by_name;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $messenger_phone;
    public $address;
    public $birth_date;
    public $national_code;
    public $passport_number;
    public $passport_expiry_date;
    public $nationality;
    public $license_number;
    public $licensed_driver_name;
    public $selected_services = [];
    public array $service_quantities = [
        'child_seat' => 0,
    ];
    public $selected_insurance = 'basic_insurance';
    public $services_total = 0;
    public $insurance_total = 0;
    public ?string $driving_license_option = null;
    public float $driving_license_cost = 0;
    public array $driving_license_options = [
        'one_year' => [
            'label' => 'Driving License (1 Year)',
            'amount' => 32,
        ],
        'three_year' => [
            'label' => 'Driving License (3 Years)',
            'amount' => 220,
        ],
    ];
    public $driver_hours = 0;
    public $driver_cost = 0;
    public $transfer_costs = ['pickup' => 0, 'return' => 0, 'total' => 0];
    public $tax_rate = 0.05;
    public $tax_amount = 0;
    public $subtotal = 0;
    public $final_total = 0;
    public $rental_days = 1;
    public $dailyRate;
    public $base_price;
    public $brands;
    public $models = [];
    public $carsForModel = [];
    public $services = [];
    public $contract;
    public $kardo_required = true;
    public $payment_on_delivery = true;
    public $apply_discount = false;
    public $custom_daily_rate = null;
    public $standard_daily_rate = 0;
    public $ldw_daily_rate = 0;
    public $scdw_daily_rate = 0;
    public $deposit = null;
    public $deposit_category = null;
    public $salesAgents = [];

    public array $locationCosts = [];
    public array $locationOptions = [];

    public function mount()
    {
        $this->services = config('carservices');
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->submitted_by_name = $this->determineDefaultSubmitterName();
        $this->salesAgents = Agent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $this->agent_id = Agent::query()->where('name', 'Website')->value('id');
        $this->loadLocationCosts();
    }

    private function loadLocationCosts(): void
    {
        $locations = LocationCost::orderBy('location')->get();

        $this->locationCosts = $locations->mapWithKeys(function ($cost) {
            return [
                $cost->location => [
                    'under_3' => (float) $cost->under_3_fee,
                    'over_3' => (float) $cost->over_3_fee,
                    'is_active' => (bool) $cost->is_active,
                ],
            ];
        })->toArray();

        $activeLocations = $locations->where('is_active', true)->pluck('location')->values()->all();

        foreach ([$this->pickup_location, $this->return_location] as $selectedLocation) {
            if ($selectedLocation && !isset($this->locationCosts[$selectedLocation])) {
                $this->locationCosts[$selectedLocation] = [
                    'under_3' => 0.0,
                    'over_3' => 0.0,
                    'is_active' => false,
                ];
            }

            if ($selectedLocation && !in_array($selectedLocation, $activeLocations, true)) {
                $activeLocations[] = $selectedLocation;
            }
        }

        $this->locationOptions = $activeLocations;
    }

    public function assignToMe($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        if (is_null($contract->user_id)) {
            $contract->update([
                'user_id' => auth()->id(),
            ]);
            $contract->changeStatus('assigned', auth()->id());
            $this->toast('success', 'Contract assigned to you successfully.');
            $this->dispatch('refreshContracts');
        } else {
            $this->toast('error', 'This contract is already assigned to someone.', false);
        }
    }

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

    public function updatedDepositCategory(): void
    {
        $this->deposit = null;
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
            'selected_insurance',
            'driver_hours',
            'driving_license_option',
        ];
        return in_array($propertyName, $costRelatedFields) ||
            Str::startsWith($propertyName, 'selected_services.') ||
            Str::startsWith($propertyName, 'service_quantities.');
    }

    public function updatedSelectedBrand()
    {
        $this->selectedModelId = null;
        $this->selectedCarId = null;
        $this->loadModels();
        $this->calculateCosts();
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
        $this->custom_daily_rate = null;
        $this->apply_discount = false;
    }

    private function loadModels()
    {
        $this->models = $this->selectedBrand
            ? CarModel::where('brand', $this->selectedBrand)->orderBy('model')->get()
            : [];
    }

    private function loadCars()
    {
        if (! $this->selectedModelId) {
            $this->carsForModel = [];
            return;
        }

        $this->carsForModel = Car::where('car_model_id', $this->selectedModelId)
            ->with(['carModel', 'currentContract.customer'])
            ->orderBy('plate_number')
            ->get();
    }

    public function calculateCosts()
    {
        $this->syncServiceSelectionWithQuantities();
        $this->canonicalizeSelectedServices();
        $this->service_quantities = $this->normalizedServiceQuantities(true);
        $this->calculateRentalDays();
        $this->calculateBasePrice();
        $this->calculateTransferCosts();
        $this->calculateServicesTotal();
        $this->calculateDriverServiceCost();
        $this->calculateDrivingLicenseCost();
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
            $standardRate = $this->roundCurrency($this->getCarDailyRate($car, $this->rental_days));
            $this->standard_daily_rate = $standardRate;
            $this->dailyRate = ($this->apply_discount && $this->custom_daily_rate)
                ? $this->roundCurrency((float) $this->custom_daily_rate)
                : $standardRate;
            $this->base_price = $this->roundCurrency($this->dailyRate * $this->rental_days);
            $this->ldw_daily_rate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'ldw', $this->rental_days));
            $this->scdw_daily_rate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'scdw', $this->rental_days));
        } else {
            $this->dailyRate = $this->roundCurrency(0);
            $this->base_price = $this->roundCurrency(0);
            $this->standard_daily_rate = $this->roundCurrency(0);
            $this->ldw_daily_rate = $this->roundCurrency(0);
            $this->scdw_daily_rate = $this->roundCurrency(0);
        }
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
        $pickup = $this->roundCurrency($this->calculateLocationFee($this->pickup_location, $this->rental_days));
        $return = $this->roundCurrency($this->calculateLocationFee($this->return_location, $this->rental_days));

        $this->transfer_costs = [
            'pickup' => $pickup,
            'return' => $return,
            'total' => $this->roundCurrency($pickup + $return),
        ];
    }

    private function calculateLocationFee($location, $days)
    {
        $feeType = ($days < 3) ? 'under_3' : 'over_3';
        return (float) ($this->locationCosts[$location][$feeType] ?? 0);
    }

    private function calculateServicesTotal()
    {
        $servicesTotal = 0;
        $insuranceTotal = 0;
        $days = max(1, (int) $this->rental_days);

        foreach ($this->selected_services as $serviceId) {
            $service = $this->resolveServiceDefinition($serviceId);
            if (!$service) {
                continue;
            }

            $quantity = $this->getServiceQuantity($serviceId);

            if ($quantity <= 0) {
                continue;
            }

            $servicesTotal += $this->roundCurrency($this->calculateServiceAmount($service, $days, $quantity));
        }

        if ($this->selected_insurance && in_array($this->selected_insurance, ['ldw_insurance', 'scdw_insurance']) && $this->selectedCarId) {
            $car = Car::find($this->selectedCarId);
            if ($car) {
                $insuranceDaily = $this->selected_insurance === 'ldw_insurance'
                    ? $this->getInsuranceDailyRate($car, 'ldw', $days)
                    : $this->getInsuranceDailyRate($car, 'scdw', $days);
                $insuranceTotal += $this->roundCurrency($insuranceDaily * $days);
            }
        }

        $this->services_total = $this->roundCurrency($servicesTotal);
        $this->insurance_total = $this->roundCurrency($insuranceTotal);
    }

    private function calculateDriverServiceCost(): void
    {
        $hours = (float) ($this->driver_hours ?? 0);

        if ($hours <= 0) {
            $this->driver_cost = $this->roundCurrency(0);
            return;
        }

        $totalMinutes = (int) ceil($hours * 60);

        if ($totalMinutes <= 0) {
            $this->driver_cost = $this->roundCurrency(0);
            return;
        }

        $baseCost = 250;
        $includedMinutes = 8 * 60;

        if ($totalMinutes <= $includedMinutes) {
            $this->driver_cost = $this->roundCurrency($baseCost);
            return;
        }

        $extraMinutes = $totalMinutes - $includedMinutes;
        $extraHours = (int) ceil($extraMinutes / 60);
        $additionalCost = $extraHours * 40;

        $this->driver_cost = $this->roundCurrency($baseCost + $additionalCost);
    }

    private function calculateDrivingLicenseCost(): void
    {
        $selectedKey = $this->driving_license_option ?: null;

        if (!$selectedKey || !isset($this->driving_license_options[$selectedKey])) {
            $this->driving_license_cost = $this->roundCurrency(0);
            $this->driving_license_option = $selectedKey ?: null;
            return;
        }

        $amount = (float) ($this->driving_license_options[$selectedKey]['amount'] ?? 0);
        $this->driving_license_cost = $this->roundCurrency($amount);
    }

    private function calculateTaxAndTotal()
    {
        $this->subtotal = $this->roundCurrency(
            $this->base_price
                + $this->services_total
                + $this->insurance_total
                + $this->transfer_costs['total']
                + $this->driver_cost
                + $this->driving_license_cost
        );
        $this->tax_amount = $this->roundCurrency($this->subtotal * $this->tax_rate);
        $this->final_total = $this->roundCurrency($this->subtotal + $this->tax_amount);
    }

    private function roundCurrency($value): float
    {
        return round((float) $value, 2);
    }

    private function getCarReservations($carId)
    {
        if (!$carId) return [];

        return Contract::where('car_id', $carId)
            ->whereIn('current_status', ['pending', 'assigned', 'under_review', 'reserved', 'delivery', 'agreement_inspection', 'awaiting_return'])
            ->where('return_date', '>=', now())
            ->select('id', 'pickup_date', 'return_date', 'current_status')
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'pickup_date' => Carbon::parse($contract->pickup_date)->format('Y-m-d H:i'),
                    'return_date' => Carbon::parse($contract->return_date)->format('Y-m-d H:i'),
                    'status' => $contract->current_status,
                ];
            })
            ->toArray();
    }

    private function getAvailabilityConflictMessage(Carbon $pickup, Carbon $return): ?string
    {
        $reservations = $this->getCarReservations($this->selectedCarId);

        foreach ($reservations as $reservation) {
            $existingPickup = Carbon::parse($reservation['pickup_date']);
            $existingReturn = Carbon::parse($reservation['return_date']);

            if ($pickup->lessThan($existingReturn) && $return->greaterThan($existingPickup)) {
                return "The selected car is already reserved from {$reservation['pickup_date']} to {$reservation['return_date']}.";
            }
        }

        return null;
    }

    protected function rules()
    {
        return [
            'selectedBrand' => ['required', 'string'],
            'selectedModelId' => ['required', 'exists:car_models,id'],
            'selectedCarId' => ['required', 'exists:cars,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'pickup_location' => ['required', Rule::in(array_keys($this->locationCosts))],
            'return_location' => ['required', Rule::in(array_keys($this->locationCosts))],
            'pickup_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (!$this->selectedCarId || !$this->return_date) {
                        return;
                    }

                    $pickup = Carbon::parse($value);
                    $return = Carbon::parse($this->return_date);
                    $conflictMessage = $this->getAvailabilityConflictMessage($pickup, $return);

                    if ($conflictMessage) {
                        $fail($conflictMessage);
                    }
                },
            ],
            'return_date' => [
                'required',
                'date',
                'after:pickup_date',
                function ($attribute, $value, $fail) {
                    if (!$this->selectedCarId || !$this->pickup_date) {
                        return;
                    }

                    $pickup = Carbon::parse($this->pickup_date);
                    $return = Carbon::parse($value);
                    $conflictMessage = $this->getAvailabilityConflictMessage($pickup, $return);

                    if ($conflictMessage) {
                        $fail($conflictMessage);
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')],
            'phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'messenger_phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'national_code' => ['required'],
            'passport_number' => ['nullable', 'string', 'max:50', Rule::unique('customers')],
            'passport_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'licensed_driver_name' => ['nullable', 'string', 'max:255'],
            'selected_insurance' => ['nullable', Rule::in(['', 'basic_insurance', 'ldw_insurance', 'scdw_insurance'])],
            'driving_license_option' => ['nullable', Rule::in(array_keys($this->driving_license_options))],
            'kardo_required' => ['boolean'],
            'payment_on_delivery' => ['boolean'],
            'apply_discount' => ['boolean'],
            'custom_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'driver_hours' => ['nullable', 'numeric', 'min:0'],
            'driver_note' => ['nullable', 'string', 'max:1000'],
            'deposit_category' => ['nullable', 'in:cash_aed,cheque,transfer_cash_irr', 'required_with:deposit'],
            'deposit' => $this->depositRules(),
            'service_quantities.child_seat' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function depositRules(): array
    {
        $rules = ['nullable'];

        $rules[] = function ($attribute, $value, $fail) {
            if (!$this->deposit_category && ($value !== null && $value !== '')) {
                $fail('Please select a security hold category before entering details.');
            }
        };

        if ($this->deposit_category === 'cash_aed') {
            $rules[] = 'required_with:deposit_category';
            $rules[] = 'numeric';
            $rules[] = 'min:0';
        } elseif ($this->deposit_category) {
            $rules[] = 'required_with:deposit_category';
            $rules[] = 'string';
            $rules[] = 'max:1000';
        }

        return $rules;
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
        'return_date.after' => 'The return date must be after the pickup date.',
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
        'phone.regex' => 'Phone number must start with + and include 8 to 15 digits.',
        'messenger_phone.required' => 'Messenger phone number is required.',
        'messenger_phone.regex' => 'Messenger phone must start with + and include 8 to 15 digits.',
        'address.string' => 'Address must be a string.',
        'address.max' => 'Address cannot be longer than 255 characters.',
        'birth_date.date' => 'Please provide a valid birth date.',
        'birth_date.before_or_equal' => 'Birth date cannot be in the future.',
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
        'licensed_driver_name.string' => 'Licensed driver name must be a string.',
        'licensed_driver_name.max' => 'Licensed driver name cannot be longer than 255 characters.',
        'selected_insurance.in' => 'The selected insurance option is invalid.',
        'driving_license_option.in' => 'The selected driving license option is invalid.',
        'payment_on_delivery.boolean' => 'The payment on delivery field must be a boolean value.',
        'driver_hours.numeric' => 'Driver service hours must be a number.',
        'driver_hours.min' => 'Driver service hours cannot be negative.',
        'service_quantities.child_seat.integer' => 'Child seat quantity must be a whole number.',
        'service_quantities.child_seat.min' => 'Child seat quantity cannot be negative.',
        'deposit_category.in' => 'Please select a valid security hold category.',
        'deposit_category.required_with' => 'Please select a security hold category.',
        'deposit.required_with' => 'Please provide security hold details for the selected category.',
        'deposit.numeric' => 'Cash security hold amount must be a valid number.',
        'deposit.min' => 'Cash security hold amount cannot be negative.',
        'deposit.string' => 'Security hold note must be text.',
        'deposit.max' => 'Security hold note may not be greater than 1000 characters.',
    ];

    protected array $validationAttributes = [
        'selectedBrand' => 'car brand',
        'selectedModelId' => 'car model',
        'selectedCarId' => 'car',
        'agent_id' => 'sales agent',
        'pickup_location' => 'pickup location',
        'return_location' => 'return location',
        'pickup_date' => 'pickup date',
        'return_date' => 'return date',
        'first_name' => 'first name',
        'last_name' => 'last name',
        'email' => 'email address',
        'phone' => 'phone number',
        'messenger_phone' => 'messenger phone number',
        'address' => 'address',
        'birth_date' => 'birth date',
        'national_code' => 'national code',
        'passport_number' => 'passport number',
        'passport_expiry_date' => 'passport expiry date',
        'nationality' => 'nationality',
        'license_number' => 'license number',
        'licensed_driver_name' => 'licensed driver name',
        'selected_insurance' => 'insurance selection',
        'driving_license_option' => 'driving license option',
        'driver_hours' => 'driver service hours',
        'driver_note' => 'driver note',
        'deposit' => 'security hold detail',
        'deposit_category' => 'security hold category',
        'custom_daily_rate' => 'custom daily rate',
        'service_quantities.child_seat' => 'child seat quantity',
    ];

    private function normalizePhoneFields(): void
    {
        $this->phone = PhoneNumber::normalize($this->phone) ?? trim((string) $this->phone);
        $this->messenger_phone = PhoneNumber::normalize($this->messenger_phone) ?? trim((string) $this->messenger_phone);
    }

    public function submit()
    {
        $this->normalizePhoneFields();
        $this->validateWithScroll();
        DB::beginTransaction();

        try {
            $this->calculateCosts();

            $customer = Customer::updateOrCreate(
                ['phone' => $this->phone, 'email' => $this->email],
                [
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'national_code' => $this->national_code,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'messenger_phone' => $this->messenger_phone,
                    'address' => $this->address,
                    'birth_date' => $this->birth_date,
                    'passport_number' => $this->passport_number,
                    'passport_expiry_date' => $this->passport_expiry_date,
                    'nationality' => $this->nationality,
                    'license_number' => $this->license_number,
                ]
            );

            $contract = Contract::create([
                'user_id' => null,
                'customer_id' => $customer->id,
                'car_id' => $this->selectedCarId,
                'total_price' => $this->roundCurrency($this->final_total),
                'agent_id' => $this->agent_id,
                'submitted_by_name' => $this->submitted_by_name ?: $this->determineDefaultSubmitterName(),
                'pickup_location' => $this->pickup_location,
                'return_location' => $this->return_location,
                'pickup_date' => $this->pickup_date,
                'return_date' => $this->return_date,
                'selected_services' => $this->selected_services,
                'selected_insurance' => $this->selected_insurance,
                'licensed_driver_name' => $this->licensed_driver_name,
                'notes' => $this->notes,
                'deposit' => $this->normalizedDeposit(),
                'deposit_category' => $this->deposit_category,
                'kardo_required' => $this->kardo_required ?? true,
                'used_daily_rate' => $this->roundCurrency($this->dailyRate),
                'custom_daily_rate_enabled' => $this->apply_discount,
                'discount_note' => $this->apply_discount ? "Discount applied: {$this->custom_daily_rate} AED instead of standard rate" : null,
                'payment_on_delivery' => $this->payment_on_delivery ?? true,
                'meta' => $this->prepareContractMeta(),
            ]);

            $contract->changeStatus('pending', auth()->id());
            $this->contract = $contract;
            $this->storeContractCharges($contract);

            DB::commit();
            $this->toast('success', 'Contract created successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->toast('error', 'An error occurred: ' . $e->getMessage(), false);
        }
    }

    private function validateWithScroll(?array $rules = null): array
    {
        try {
            return $this->validate($rules ?? $this->rules(), $this->messages, $this->validationAttributes);
        } catch (ValidationException $exception) {
            $this->dispatch('kara-scroll-to-error', field: $this->firstErrorField($exception));
            throw $exception;
        }
    }

    private function firstErrorField(ValidationException $exception): string
    {
        $errors = $exception->errors();
        $firstKey = array_key_first($errors);

        if (!is_string($firstKey) || $firstKey === '') {
            return '';
        }

        return Str::before($firstKey, '.');
    }

    private function prepareContractMeta(): ?array
    {
        $meta = [];

        if (($this->driver_hours ?? 0) > 0) {
            $meta['driver_hours'] = (float) $this->driver_hours;
            $meta['driver_service_cost'] = $this->roundCurrency($this->driver_cost);
        }

        if ($this->payment_on_delivery && !is_null($this->driver_note) && trim((string) $this->driver_note) !== '') {
            $meta['driver_note'] = $this->driver_note;
        }

        $serviceQuantities = $this->normalizedServiceQuantities();

        if (!empty($serviceQuantities)) {
            $meta['service_quantities'] = $serviceQuantities;
        }

        if ($this->driving_license_option && isset($this->driving_license_options[$this->driving_license_option])) {
            $meta['driving_license_option'] = $this->driving_license_option;
            $meta['driving_license_cost'] = $this->roundCurrency($this->driving_license_cost);
        }

        return !empty($meta) ? $meta : null;
    }

    private function normalizedDeposit(): ?string
    {
        if (!$this->deposit_category) {
            return null;
        }

        if ($this->deposit_category === 'cash_aed' && is_numeric($this->deposit)) {
            return number_format(max(0, (float) $this->deposit), 2, '.', '');
        }

        $deposit = is_string($this->deposit) ? trim($this->deposit) : null;

        return $deposit !== '' ? $deposit : null;
    }

    private function normalizedServiceQuantities(bool $includeZeros = false): array
    {
        if (!is_array($this->service_quantities)) {
            return [];
        }

        $normalized = [];

        foreach ($this->service_quantities as $serviceId => $quantity) {
            $resolvedId = $this->resolveServiceId((string) $serviceId);

            if ($resolvedId === null) {
                continue;
            }

            $count = max(0, (int) $quantity);

            if ($count > 0 || $includeZeros) {
                $normalized[$resolvedId] = $count;
            }
        }

        return $normalized;
    }

    private function determineDefaultSubmitterName(): string
    {
        $user = auth()->user();
        if ($user) {
            $fullName = method_exists($user, 'fullName') ? trim($user->fullName()) : trim($user->name ?? '');
            if ($fullName !== '') {
                return $fullName;
            }
        }

        return 'Website';
    }

    private function storeContractCharges(Contract $contract)
    {
        ContractCharges::where('contract_id', $contract->id)->delete();

        ContractCharges::create([
            'contract_id' => $contract->id,
            'title' => 'base_rental',
            'amount' => $this->roundCurrency($this->base_price),
            'type' => 'base',
            'description' => sprintf(
                '%d %s × %s AED%s',
                (int) $this->rental_days,
                (int) $this->rental_days === 1 ? 'day' : 'days',
                number_format($this->dailyRate, 2),
                $this->apply_discount ? ' (with discount)' : ''
            ),
        ]);

        if ($this->transfer_costs['pickup'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'pickup_transfer',
                'amount' => $this->roundCurrency($this->transfer_costs['pickup']),
                'type' => 'location_fee',
                'description' => $this->pickup_location,
            ]);
        }

        if ($this->transfer_costs['return'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'return_transfer',
                'amount' => $this->roundCurrency($this->transfer_costs['return']),
                'type' => 'location_fee',
                'description' => $this->return_location,
            ]);
        }

        if ($this->driver_cost > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'driver_service',
                'amount' => $this->roundCurrency($this->driver_cost),
                'type' => 'service',
                'description' => $this->buildDriverChargeDescription(),
            ]);
        }

        if ($this->driving_license_cost > 0 && $this->driving_license_option) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'driving_license_' . $this->driving_license_option,
                'amount' => $this->roundCurrency($this->driving_license_cost),
                'type' => 'service',
                'description' => $this->buildDrivingLicenseDescription(),
            ]);
        }

        foreach ($this->selected_services as $serviceId) {
            $resolvedId = $this->resolveServiceId($serviceId);
            if (!$resolvedId) {
                continue;
            }

            $service = $this->services[$resolvedId] ?? null;
            if (!$service) {
                continue;
            }

            $quantity = $this->getServiceQuantity($resolvedId);

            if ($quantity <= 0) {
                continue;
            }

            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $resolvedId,
                'amount' => $this->roundCurrency($this->calculateServiceAmount($service, $this->rental_days, $quantity)),
                'type' => 'addon',
                'description' => $this->buildServiceDescription($service, $this->rental_days, $quantity),
            ]);
        }

        if ($this->selected_insurance && in_array($this->selected_insurance, ['ldw_insurance', 'scdw_insurance'])) {
            $insuranceAmount = 0;
            $car = Car::find($this->selectedCarId);
            if ($car) {
                $insuranceDaily = $this->getInsuranceDailyRate($car, str_replace('_insurance', '', $this->selected_insurance), $this->rental_days);
                $insuranceAmount = $this->roundCurrency($insuranceDaily * $this->rental_days);
            }

            if ($insuranceAmount > 0) {
                ContractCharges::create([
                    'contract_id' => $contract->id,
                    'title' => $this->selected_insurance,
                    'amount' => $this->roundCurrency($insuranceAmount),
                    'type' => 'insurance',
                    'description' => sprintf(
                        '%d %s',
                        (int) $this->rental_days,
                        (int) $this->rental_days === 1 ? 'day' : 'days'
                    ),
                ]);
            }
        }

        if ($this->tax_amount > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'tax',
                'amount' => $this->roundCurrency($this->tax_amount),
                'type' => 'tax',
                'description' => '5% VAT',
            ]);
        }
    }

    private function buildDriverChargeDescription(): string
    {
        $hours = max(0, (float) ($this->driver_hours ?? 0));
        $minutes = (int) ceil($hours * 60);

        if ($minutes <= 0) {
            return 'Driver service not requested';
        }

        $formattedHours = $hours == floor($hours)
            ? (string) (int) $hours
            : number_format($hours, 2);

        $includedMinutes = 8 * 60;
        $extraDescription = 'Includes first 8 hours at 250 AED';

        if ($minutes > $includedMinutes) {
            $extraMinutes = $minutes - $includedMinutes;
            $extraHours = (int) ceil($extraMinutes / 60);
            $extraDescription .= " + {$extraHours} extra hour(s) at 40 AED each";
        }

        return "Driver service for {$formattedHours} hour(s) — {$extraDescription}";
    }

    private function buildDrivingLicenseDescription(): string
    {
        if (!$this->driving_license_option || !isset($this->driving_license_options[$this->driving_license_option])) {
            return 'Driving license fee';
        }

        $label = $this->driving_license_options[$this->driving_license_option]['label'] ?? 'Driving License';

        return sprintf('%s - %s AED', $label, number_format($this->driving_license_cost, 2));
    }

    public function changeStatusToReserve($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        if ($contract->current_status !== 'reserved') {
            $contract->changeStatus('reserved', auth()->id());
            $this->toast('success', 'Contract status changed to Reserved successfully.');
            $this->dispatch('refreshContracts');
        } else {
            $this->toast('info', 'This contract is already Reserved.', false);
        }
    }

    public function render()
    {
        $services = array_map(function ($service) {
            $service['label'] = $service['label_en'];
            return $service;
        }, $this->services);

        return view('livewire.pages.panel.expert.rental-request.rental-request-create', [
            'brands' => $this->brands,
            'services' => $services,
            'ldw_daily_rate' => $this->ldw_daily_rate,
            'scdw_daily_rate' => $this->scdw_daily_rate,
        ]);
    }
}
