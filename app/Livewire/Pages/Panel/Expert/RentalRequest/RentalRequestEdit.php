<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

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

class RentalRequestEdit extends Component
{
    use InteractsWithToasts;
    use HandlesServicePricing;
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
    public $driver_note;
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
    public $filteredCarModels = [];
    public $customerDocumentsCompleted = false;
    public $paymentsExist = false;
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
    public $brands = [];
    public $models = [];
    public $selectedModelId;
    public $contract;
    public $contractId;
    public $kardo_required;
    public $payment_on_delivery;
    public $apply_discount = false;
    public $custom_daily_rate = null;
    public $standard_daily_rate = 0;
    public $carsForModel = [];
    public $ldw_daily_rate = 0;
    public $scdw_daily_rate = 0;
    public $originalCosts = [];
    public $originalSelections = [];
    public $carNameCache = [];
    public $deposit = null;
    public $deposit_category = null;
    public array $salesAgents = [];

    public array $locationCosts = [];
    public array $locationOptions = [];

    public $services = [];

    public function mount($contractId)
    {
        $this->services = config('carservices');
        $this->salesAgents = config('agents.sales_agents', []);
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->contract = Contract::with(['customer', 'car.carModel', 'payments'])->findOrFail($contractId);

        $this->apply_discount = (bool) ($this->contract->custom_daily_rate_enabled ?? false);

        if (!$this->apply_discount && $this->contract->discount_note) {
            $this->apply_discount = true;
        }

        if ($this->apply_discount && $this->contract->used_daily_rate) {
            $this->custom_daily_rate = $this->contract->used_daily_rate;
        }

        $this->initializeFromContract();
        $this->loadLocationCosts();
        $this->loadChargesFromDatabase($contractId);
        $this->calculateCosts();
        $this->originalSelections = $this->captureSelectionSnapshot();
        $this->originalCosts = $this->captureCurrentCostSnapshot();
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

    private function loadChargesFromDatabase($contractId)
    {
        $this->calculateRentalDays();

        $metaQuantities = $this->contract?->meta['service_quantities'] ?? [];

        $this->service_quantities = $this->normalizedServiceQuantities(
            is_array($metaQuantities) ? $metaQuantities : [],
            true
        );

        $charges = ContractCharges::where('contract_id', $contractId)->get();
        $this->selected_services = [];
        $this->selected_insurance = null; // Default to null for "No Additional Insurance"
        $driverCharge = null;

        foreach ($charges as $charge) {
            if (Str::startsWith($charge->title, 'driving_license_')) {
                $option = Str::after($charge->title, 'driving_license_');
                if (isset($this->driving_license_options[$option])) {
                    $this->driving_license_option = $option;
                }
            }

            if (!in_array($charge->type, ['addon', 'insurance'], true)) {
                if ($charge->title === 'driver_service') {
                    $driverCharge = $charge;
                }
                continue;
            }

            $resolvedId = $this->resolveServiceId((string) $charge->title);

            if (!$resolvedId) {
                continue;
            }

            $service = $this->services[$resolvedId] ?? null;

            if (in_array($resolvedId, ['ldw_insurance', 'scdw_insurance'], true)) {
                $this->selected_insurance = $resolvedId;
                continue;
            }

            if ($resolvedId !== 'basic_insurance') {
                $this->selected_services[] = $resolvedId;
            }

            if ($resolvedId === 'child_seat' && $service && $this->getServiceQuantity('child_seat') === 0) {
                $this->service_quantities['child_seat'] = $this->inferServiceQuantityFromCharge($charge, $service);
            }
        }

        if ($driverCharge && ($this->driver_hours ?? 0) <= 0) {
            $this->driver_hours = $this->inferDriverHoursFromCharge($driverCharge);
        }

        $this->canonicalizeSelectedServices();
        $this->service_quantities = $this->normalizedServiceQuantities(null, true);

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

        $meta = $this->contract->meta ?? [];
        $this->driver_note = $meta['driver_note'] ?? null;
        $this->deposit = $this->contract->deposit;
        $this->deposit_category = $this->contract->deposit_category;
        $this->driver_hours = isset($meta['driver_hours']) ? (float) $meta['driver_hours'] : 0;
        $this->service_quantities = $this->normalizedServiceQuantities($meta['service_quantities'] ?? [], true);
        $licenseOption = $meta['driving_license_option'] ?? null;
        if ($licenseOption && isset($this->driving_license_options[$licenseOption])) {
            $this->driving_license_option = $licenseOption;
        }

        // Customer data
        $customer = $this->contract->customer()->firstOrFail();
        $this->first_name = $customer->first_name;
        $this->last_name = $customer->last_name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->messenger_phone = $customer->messenger_phone;
        $this->address = $customer->address;
        $this->birth_date = $customer->birth_date ? $customer->birth_date->format('Y-m-d') : null;
        $this->national_code = $customer->national_code;
        $this->passport_number = $customer->passport_number;
        $this->passport_expiry_date = $customer->passport_expiry_date;
        $this->nationality = $customer->nationality;
        $this->license_number = $customer->license_number;
        $this->licensed_driver_name = $this->contract->licensed_driver_name;

        // Car selection
        $this->selectedBrand = $this->contract->car->carModel->brand;
        $this->loadModels();
        $this->selectedModelId = $this->contract->car->car_model_id;
        $this->loadCars();
        $this->selectedCarId = $this->contract->car->id;
        $this->carNameCache[$this->selectedCarId] = $this->contract->car->fullName();

        // Documents and payments
        $this->customerDocumentsCompleted = (bool)$this->contract->customerDocument;
        $this->paymentsExist = $this->contract->payments()->exists();
    }

    public function calculateCosts()
    {
        $this->syncServiceSelectionWithQuantities();
        $this->canonicalizeSelectedServices();
        $this->service_quantities = $this->normalizedServiceQuantities(null, true);
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
            $this->dailyRate = $this->apply_discount && $this->custom_daily_rate
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
        $pickup = $this->roundCurrency($this->calculateLocationFee($this->pickup_location, $this->rental_days));
        $return = $this->roundCurrency($this->calculateLocationFee($this->return_location, $this->rental_days));

        $this->transfer_costs = [
            'pickup' => $pickup,
            'return' => $return,
            'total' => $this->roundCurrency($pickup + $return)
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

    protected function rules()
    {
        $customerId = $this->contract ? $this->contract->customer->id : null;
        return [
            'selectedBrand' => ['required', 'string'],
            'selectedModelId' => ['required', 'exists:car_models,id'],
            'selectedCarId' => ['required', 'exists:cars,id'],
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
                'after_or_equal:pickup_date',
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
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customerId),
            ],
            'phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'messenger_phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
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
        'kardo_required.boolean' => 'The KARDO required field must be a boolean value.',
        'payment_on_delivery.boolean' => 'The payment on delivery field must be a boolean value.',
        'apply_discount.boolean' => 'The apply discount field must be a boolean value.',
        'custom_daily_rate.numeric' => 'The custom daily rate must be a number.',
        'custom_daily_rate.min' => 'The custom daily rate cannot be negative.',
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

    private function captureSelectionSnapshot(): array
    {
        return [
            'car_id' => $this->selectedCarId,
            'car_label' => $this->getCarLabel($this->selectedCarId),
            'pickup_location' => $this->pickup_location,
            'return_location' => $this->return_location,
            'pickup_date' => $this->pickup_date,
            'return_date' => $this->return_date,
            'selected_insurance' => $this->selected_insurance,
            'selected_services' => $this->selected_services,
            'service_quantities' => $this->service_quantities,
            'driver_hours' => $this->driver_hours,
            'driving_license_option' => $this->driving_license_option,
        ];
    }

    private function captureCurrentCostSnapshot(): array
    {
        return [
            'daily_rate' => $this->dailyRate,
            'rental_days' => $this->rental_days,
            'base_price' => $this->base_price,
            'pickup_transfer' => $this->transfer_costs['pickup'] ?? 0,
            'return_transfer' => $this->transfer_costs['return'] ?? 0,
            'services_total' => $this->services_total,
            'insurance_total' => $this->insurance_total,
            'driver_hours' => $this->driver_hours,
            'driver_cost' => $this->driver_cost,
            'driving_license_cost' => $this->driving_license_cost,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax_amount,
            'total' => $this->final_total,
        ];
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

    public function submit()
    {
        $this->normalizePhoneFields();
        $this->validateWithScroll();
        DB::beginTransaction();

        try {
            $this->calculateCosts();
            $this->updateCustomer();
            $this->updateContract();
            $this->storeContractCharges($this->contract);

            $oldTotal = $this->contract->total_price;
            $newTotal = $this->final_total;
            if ($newTotal > $oldTotal) {
                $this->toast('info', "Extension cost: " . ($newTotal - $oldTotal) . " AED", false);
            }
            DB::commit();
            $this->toast('success', 'Contract Updated successfully!');
        } catch (\Exception $e) {
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
                'description' => $this->pickup_location
            ]);
        }

        if ($this->transfer_costs['return'] > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'return_transfer',
                'amount' => $this->roundCurrency($this->transfer_costs['return']),
                'type' => 'location_fee',
                'description' => $this->return_location
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
                'description' => $this->buildServiceDescription($service, $this->rental_days, $quantity)
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
                    )
                ]);
            }
        }

        if ($this->tax_amount > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'tax',
                'amount' => $this->roundCurrency($this->tax_amount),
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
            'birth_date' => $this->birth_date,
            'passport_number' => $this->passport_number,
            'passport_expiry_date' => $this->passport_expiry_date,
            'nationality' => $this->nationality,
            'license_number' => $this->license_number,
        ];
        $this->contract->customer->update($customerData);
    }

    private function updateContract()
    {
        $meta = $this->contract->meta ?? [];
        $meta = is_array($meta) ? $meta : [];

        if (($this->driver_hours ?? 0) > 0) {
            $meta['driver_hours'] = (float) $this->driver_hours;
            $meta['driver_service_cost'] = $this->roundCurrency($this->driver_cost);
        } else {
            unset($meta['driver_hours'], $meta['driver_service_cost']);
        }

        $driverNote = $this->payment_on_delivery ? $this->driver_note : null;

        if (!is_null($driverNote) && trim((string) $driverNote) !== '') {
            $meta['driver_note'] = $driverNote;
        } else {
            unset($meta['driver_note']);
        }

        $serviceQuantities = $this->normalizedServiceQuantities();

        if (!empty($serviceQuantities)) {
            $meta['service_quantities'] = $serviceQuantities;
        } else {
            unset($meta['service_quantities']);
        }

        if ($this->driving_license_option && isset($this->driving_license_options[$this->driving_license_option])) {
            $meta['driving_license_option'] = $this->driving_license_option;
            $meta['driving_license_cost'] = $this->roundCurrency($this->driving_license_cost);
        } else {
            unset($meta['driving_license_option'], $meta['driving_license_cost']);
        }

        $contractData = [
            'car_id' => $this->selectedCarId,
            'total_price' => $this->roundCurrency($this->final_total),
            'agent_sale' => $this->agent_sale,
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
            'kardo_required' => $this->kardo_required,
            'used_daily_rate' => $this->roundCurrency($this->dailyRate),
            'custom_daily_rate_enabled' => $this->apply_discount,
            'discount_note' => $this->apply_discount ? "Discount applied: {$this->custom_daily_rate} AED instead of standard rate" : null,
            'payment_on_delivery' => $this->payment_on_delivery ?? true,
            'meta' => !empty($meta) ? $meta : null,
        ];
        $this->contract->update($contractData);
        $this->contract->meta = $contractData['meta'];
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

        if (! $this->selectedModelId) {
            return;
        }

        $currentCarId = $this->contract?->car_id;

        $this->carsForModel = Car::where('car_model_id', $this->selectedModelId)
            ->with(['carModel', 'currentContract.customer'])
            ->orderBy('plate_number')
            ->get();
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
            $this->toast('success', 'Contract assigned to you successfully.');
            $this->dispatch('refreshContracts');
        } else {
            $this->toast('error', 'This contract is already assigned to someone.', false);
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
            ->when($this->contract, function ($query) {
                return $query->where('id', '!=', $this->contract->id);
            })
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
        return $reservations;
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

    public function changeStatusToReserve($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        $contract->changeStatus('reserved', auth()->id());

        $this->toast('success', 'Status changed to Reserved successfully.');
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
        $this->getCarLabel($this->selectedCarId);
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
            'comparisonRows' => $this->costComparisonData,
            'deliveryInformation' => $this->deliveryInformationText,
            'returnInformation' => $this->returnInformationText,
        ]);
    }

    public function getDeliveryInformationTextProperty(): string
    {
        $this->contract->loadMissing([
            'payments',
            'customer',
            'car.carModel',
            'user',
            'incomingBalanceTransfers',
            'outgoingBalanceTransfers',
        ]);

        $payments = $this->contract->payments ?? collect();

        $sumPaymentAmount = fn(string $type): float => (float) $payments
            ->where('payment_type', $type)
            ->sum('amount_in_aed');
        $sumTrips = fn(string $type): int => $payments
            ->where('payment_type', $type)
            ->sum(fn($payment) => (int) $payment->salikTripCount());

        $salik4Amount = $sumPaymentAmount('salik_4_aed');
        $salik4Trips = $sumTrips('salik_4_aed');
        $salik6Amount = $sumPaymentAmount('salik_6_aed');
        $salik6Trips = $sumTrips('salik_6_aed');
        $otherRevenueAmount = $sumPaymentAmount('salik_other_revenue');
        $otherRevenueTrips = $payments
            ->where('payment_type', 'salik_other_revenue')
            ->sum(fn($payment) => $payment->salikTripCount() ?: (int) round((float) $payment->amount_in_aed));
        $insuranceTotal = (float) ($this->insurance_total ?? 0);
        $childSeatQuantity = $this->getServiceQuantity('child_seat');
        $childSeatUnit = (float) ($this->services['child_seat']['amount'] ?? 0);
        $childSeatAmount = $childSeatQuantity * $childSeatUnit;
        $pickupCharge = (float) ($this->transfer_costs['pickup'] ?? 0);
        $returnCharge = (float) ($this->transfer_costs['return'] ?? 0);
        $fineAmount = $sumPaymentAmount('fine');
        $noSecurityHoldFee = $sumPaymentAmount('no_deposit_fee');
        $parkingAmount = $sumPaymentAmount('parking');
        $fuelAmount = $sumPaymentAmount('fuel');
        $carwashAmount = $sumPaymentAmount('carwash');
        $scratchAmount = $sumPaymentAmount('damage');
        $legacySalikAmount = $sumPaymentAmount('salik');
        $driverServiceCost = (float) ($this->driver_cost ?? 0);
        $drivingLicenseCost = (float) ($this->driving_license_cost ?? 0);
        $servicesTotal = (float) ($this->services_total ?? 0);

        $discountsTotal = $sumPaymentAmount('discount');
        $securityDeposit = $sumPaymentAmount('security_deposit');
        $paymentBack = $sumPaymentAmount('payment_back');
        $rentalFeeCollected = $sumPaymentAmount('rental_fee');
        $effectivePaid = $rentalFeeCollected - $paymentBack;

        $incomingTransfers = (float) ($this->contract->incomingBalanceTransfers?->sum('amount') ?? 0);
        $outgoingTransfers = (float) ($this->contract->outgoingBalanceTransfers?->sum('amount') ?? 0);
        $debtAmount = max((float) $this->contract->calculateRemainingBalance($payments), 0);

        $basePrice = (float) ($this->base_price ?? 0);
        $rentalCostLines = [];
        $otherChargeLines = [];
        $appendRentalLine = function (string $label, float $amount, ?string $extra = null) use (&$rentalCostLines) {
            if (abs((float) $amount) < 0.01) {
                return;
            }

            $value = $this->formatCurrency($amount) . ' AED';

            if ($extra) {
                $value = $extra . ', ' . $value;
            }

            $rentalCostLines[] = $label . ': ' . $value;
        };
        $appendOtherChargeLine = function (string $label, float $amount, ?string $extra = null) use (&$otherChargeLines) {
            if (abs((float) $amount) < 0.01) {
                return;
            }

            $value = $this->formatCurrency($amount) . ' AED';

            if ($extra) {
                $value = $extra . ', ' . $value;
            }

            $otherChargeLines[] = $label . ': ' . $value;
        };
        $appendTripsOtherCharge = function (string $label, float $amount, int $trips) use (&$otherChargeLines) {
            if (abs((float) $amount) < 0.01) {
                return;
            }

            $tripText = $trips > 0 ? $trips . ' Trips, ' : '';
            $otherChargeLines[] = $label . ': ' . $tripText . $this->formatCurrency($amount) . ' AED';
        };
        if ($basePrice > 0) {
            $rentalCostLines[] = 'Rate: ' . $this->rental_days . ' day(s) x ' . $this->formatDailyRate() . ' AED = ' . $this->formatCurrency($basePrice) . ' AED';
        }

        $appendRentalLine('Add-on total', $servicesTotal, 'Selected add-ons');
        $appendRentalLine('Driver service', $driverServiceCost, ($this->driver_hours ?? 0) > 0 ? number_format(max(0, (float) $this->driver_hours), 1) . ' hrs' : null);
        $appendRentalLine('Driving license', $drivingLicenseCost, $this->driving_license_option ? $this->formatDrivingLicenseLabel($this->driving_license_option) : null);

        if ($insuranceTotal > 0) {
            $dailyInsurance = $insuranceTotal / max(1, (int) $this->rental_days);
            $appendRentalLine('Supplementary Insurance Package (Daily)', $insuranceTotal, 'Daily ' . $this->formatCurrency($dailyInsurance) . ' AED');
        }

        $appendRentalLine('Pickup travel charge', $pickupCharge);
        $appendRentalLine('Return travel charge', $returnCharge);

        $appendTripsOtherCharge('Salik (4 AED)', $salik4Amount, $salik4Trips);
        $appendTripsOtherCharge('Salik (6 AED)', $salik6Amount, $salik6Trips);
        $appendTripsOtherCharge('Other revenue', $otherRevenueAmount, $otherRevenueTrips);
        $appendOtherChargeLine('Legacy salik', $legacySalikAmount);

        if ($childSeatAmount > 0) {
            $appendOtherChargeLine('Baby seat', $childSeatAmount, $childSeatQuantity . ' seat(s)');
        }

        $appendOtherChargeLine('Fine', $fineAmount);
        $appendOtherChargeLine('No Security Hold Fee', $noSecurityHoldFee);
        $appendOtherChargeLine('Parking', $parkingAmount);
        $appendOtherChargeLine('Petrol', $fuelAmount);
        $appendOtherChargeLine('Car wash', $carwashAmount);
        $appendOtherChargeLine('Scratch', $scratchAmount);
        $appendOtherChargeLine('Debt', $debtAmount);

        $paymentsReceivedLines = [];
        $paidByType = $payments
            ->groupBy('payment_type')
            ->map(fn($group) => (float) $group->sum('amount_in_aed'));

        foreach ($paidByType as $type => $amount) {
            if (abs($amount) < 0.01) {
                continue;
            }

            $label = match ($type) {
                'rental_fee' => 'Rental Fee',
                'security_deposit' => 'Security Deposit',
                'fine' => 'Fine',
                'salik_4_aed' => 'Salik (4 AED)',
                'salik_6_aed' => 'Salik (6 AED)',
                'salik_other_revenue' => 'Other revenue',
                'parking' => 'Parking',
                'fuel' => 'Petrol',
                'carwash' => 'Car wash',
                'damage' => 'Scratch',
                'no_deposit_fee' => 'No Security Hold Fee',
                'driver_service' => 'Driver service',
                'driving_license' => 'Driving license',
                'addon' => 'Add-ons',
                default => Str::headline(str_replace('_', ' ', (string) $type)),
            };

            $paymentsReceivedLines[] = $label . ': ' . $this->formatCurrency($amount) . ' AED';
        }

        $customerName = trim($this->first_name . ' ' . $this->last_name) ?: ($this->contract->customer?->fullName() ?? '---');
        $phone = $this->phone ?: ($this->messenger_phone ?? '---');
        $seller = $this->contract->agent_sale
            ?: optional($this->contract->user)->fullName()
            ?? '---';
        $deliveryDate = $this->pickup_date ? Carbon::parse($this->pickup_date)->format('Y-m-d \A\T H:i') : '---';
        $pickupLocation = $this->pickup_location ?: '---';
        $returnLocation = $this->return_location ?: '---';
        $carDescriptor = $this->formatCarDescriptor(
            $this->stripPlateFromLabel(
                $this->contract->car?->fullName() ?? $this->getCarLabel($this->selectedCarId)
            ),
            $this->contract->car?->plate_number
        );
        $insurance = $this->formatInsuranceLabel($this->selected_insurance);
        $guaranteeFee = $this->formattedDepositLabel();
        $addOnsLabel = $this->formatServiceList($this->selected_services ?? []);
        $addOnsLabel = $addOnsLabel === '—' ? 'None' : $addOnsLabel;
        $securityHold = $securityDeposit;
        $remainingBalanceRaw = (float) $this->contract->calculateRemainingBalance($payments);
        $paymentStatus = $remainingBalanceRaw < -0.01
            ? 'Overpaid'
            : ($remainingBalanceRaw <= 0.01
                ? 'Paid in full'
                : ($this->payment_on_delivery ? 'Payment due on delivery' : 'Balance pending'));
        $paidTotal = max(0, $effectivePaid);
        $cardooForm = $this->kardo_required ? 'YES' : 'NO';

        $contractTotal = (float) ($this->contract->total_price ?? $this->final_total);
        $totalRent = $this->formatCurrency($contractTotal);
        $vatAmount = $this->formatCurrency($this->tax_amount);
        $grandTotal = $this->formatCurrency($contractTotal);
        $remainingBalanceFormatted = $this->formatCurrency($remainingBalanceRaw);
        $dailyRate = $this->formatDailyRate() . ' AED plus vat';
        $securityHoldSummary = '';

        if ($securityHold > 0) {
            $securityHoldSummary = "\nSecurity Hold (paid): " . $this->formatCurrency($securityHold) . ' AED';
        }

        $indent = '';
        $formatListSection = function (string $title, array $items) use ($indent): string {
            $items = array_values(array_filter($items, fn($item) => $item !== null && $item !== ''));

            if (empty($items)) {
                return '';
            }

            $lines = array_map(fn($item) => $indent . '- ' . $item, $items);

            return $indent . $title . ":\n" . implode("\n", $lines);
        };

        $deliveryScheduleBlock = $formatListSection('Delivery schedule', [
            "Date and time: {$deliveryDate}",
            "Pickup location: {$pickupLocation}",
            "Return location: {$returnLocation}",
        ]);

        $vehiclePlanBlock = $formatListSection('Vehicle plan', [
            "Car: *{$carDescriptor}*",
            "Days: {$this->rental_days}",
            "Daily rate: {$dailyRate}",
        ]);

        $rentalCostsBlock = $formatListSection('Rental cost calculation', $rentalCostLines);
        $otherChargesBlock = $formatListSection('Additional recorded charges', $otherChargeLines);
        $paymentsReceivedBlock = $formatListSection('Payments recorded by type', $paymentsReceivedLines);

        $summaryBlock = $formatListSection('Financial summary', [
            "Contract total reference: {$totalRent} AED",
            "VAT (current calc): {$vatAmount} AED",
            "Grand total reference: {$grandTotal} AED",
            "Paid so far (after refunds): {$this->formatCurrency($paidTotal)} AED",
            "Remaining balance: {$remainingBalanceFormatted} AED",
            "Payment status: {$paymentStatus}",
        ]);

        $prependBreak = function (string $block): string {
            return $block !== '' ? "\n{$block}" : '';
        };

        $deliveryScheduleBlock = $prependBreak($deliveryScheduleBlock);
        $vehiclePlanBlock = $prependBreak($vehiclePlanBlock);
        $rentalCostsBlock = $prependBreak($rentalCostsBlock);
        $otherChargesBlock = $prependBreak($otherChargesBlock);
        $paymentsReceivedBlock = $prependBreak($paymentsReceivedBlock);
        $summaryBlock = $prependBreak($summaryBlock);

        $subtractItems = array_filter([
            [
                'label' => 'Paid (after refunds)',
                'amount' => $effectivePaid,
                'detail' => 'Collected ' . $this->formatCurrency($rentalFeeCollected) . ' AED − Payment back ' . $this->formatCurrency($paymentBack) . ' AED',
            ],
            [
                'label' => 'Discounts',
                'amount' => $discountsTotal,
            ],
            [
                'label' => 'Security Deposit',
                'amount' => $securityDeposit,
            ],
            $outgoingTransfers > 0 ? [
                'label' => 'Transfer Out',
                'amount' => $outgoingTransfers,
            ] : null,
        ], fn($item) => $item && abs((float) $item['amount']) >= 0.01);

        $additionItems = array_filter([
            [
                'label' => 'Fines',
                'amount' => $fineAmount,
            ],
            [
                'label' => 'Salik (4 & 6 AED)',
                'amount' => $salik4Amount + $salik6Amount,
            ],
            [
                'label' => 'Salik other revenue',
                'amount' => $otherRevenueAmount,
            ],
            [
                'label' => 'Legacy salik',
                'amount' => $legacySalikAmount,
            ],
            [
                'label' => 'Parking',
                'amount' => $parkingAmount,
            ],
            [
                'label' => 'Petrol',
                'amount' => $fuelAmount,
            ],
            [
                'label' => 'Car wash',
                'amount' => $carwashAmount,
            ],
            [
                'label' => 'Scratch',
                'amount' => $scratchAmount,
            ],
            [
                'label' => 'No Security Hold Fee',
                'amount' => $noSecurityHoldFee,
            ],
            $incomingTransfers > 0 ? [
                'label' => 'Transfer In',
                'amount' => $incomingTransfers,
            ] : null,
        ], fn($item) => $item && abs((float) $item['amount']) >= 0.01);

        $financialFormulaBlock = '';

        if (!empty($subtractItems) || !empty($additionItems)) {
            $lines = [];
            $lines[] = $indent . '- Contract total price: ' . $totalRent . ' AED';

            if (!empty($subtractItems)) {
                $lines[] = $indent . '- Less:';
                foreach ($subtractItems as $item) {
                    $detail = isset($item['detail']) ? ' (' . $item['detail'] . ')' : '';
                    $lines[] = $indent . '    * ' . $item['label'] . ': ' . $this->formatCurrency($item['amount']) . ' AED' . $detail;
                }
            }

            if (!empty($additionItems)) {
                $lines[] = $indent . '- Plus:';
                foreach ($additionItems as $item) {
                    $lines[] = $indent . '    * ' . $item['label'] . ': ' . $this->formatCurrency($item['amount']) . ' AED';
                }
            }

            $lines[] = $indent . '- Resulting balance: ' . $remainingBalanceFormatted . ' AED';

            $financialFormulaBlock = "\n" . $indent . 'Payment reconciliation (Remaining Balance Formula):' . "\n" . implode("\n", $lines);
        }

        return trim(<<<TEXT
Seller: {$seller}
Name of Customer: {$customerName}
Mobile number: {$phone}
----------------------------------------------------{$deliveryScheduleBlock}{$vehiclePlanBlock}
Supplementary Insurance Package : {$insurance}
Add-ons: {$addOnsLabel}
Security hold method: {$guaranteeFee}{$rentalCostsBlock}{$otherChargesBlock}{$financialFormulaBlock}{$paymentsReceivedBlock}
----------------------------------------------------{$summaryBlock}{$securityHoldSummary}
----------------------------------------------------
Must get receive: {$remainingBalanceFormatted} AED
----------------------------------------------------
Cardoo form: {$cardooForm}
TEXT);
    }

    public function getReturnInformationTextProperty(): string
    {
        $this->contract->loadMissing(['payments', 'customer', 'car.carModel', 'pickupDocument']);

        $payments = $this->contract->payments ?? collect();

        $sumAmount = fn(string $type): float => (float) $payments->where('payment_type', $type)->sum('amount_in_aed');
        $sumTrips = fn(string $type): int => $payments->where('payment_type', $type)->sum(fn($payment) => $payment->salikTripCount());

        $salik4Trips = $sumTrips('salik_4_aed');
        $salik6Trips = $sumTrips('salik_6_aed');
        $otherRevenueTrips = $payments
            ->where('payment_type', 'salik_other_revenue')
            ->sum(fn($payment) => $payment->salikTripCount() ?: (int) round((float) $payment->amount_in_aed));

        $insuranceLabel = 'Supplementary Insurance Package (Daily)';
        $insuranceDaily = 0.0;

        if ($this->selected_insurance === 'ldw_insurance' && $this->ldw_daily_rate > 0) {
            $insuranceDaily = (float) $this->ldw_daily_rate;
        } elseif ($this->selected_insurance === 'scdw_insurance' && $this->scdw_daily_rate > 0) {
            $insuranceDaily = (float) $this->scdw_daily_rate;
        }

        $childSeatQuantity = $this->getServiceQuantity('child_seat');
        $childSeatAmount = $childSeatQuantity * ($this->services['child_seat']['amount'] ?? 0);

        $securityHold = $sumAmount('security_deposit');
        $customerPayments = (float) $payments->sum('amount_in_aed');
        $balance = $this->contract->calculateRemainingBalance($payments);

        $agreementNumber = $this->contract->pickupDocument?->agreement_number ?? '---';
        $depositLabel = $this->formattedDepositLabel();
        $customerName = trim($this->first_name . ' ' . $this->last_name) ?: ($this->contract->customer?->fullName() ?? '---');
        $phone = $this->phone ?: ($this->messenger_phone ?? '---');
        $carDescriptor = $this->formatCarDescriptor(
            $this->stripPlateFromLabel(
                $this->contract->car?->fullName() ?? $this->getCarLabel($this->selectedCarId)
            ),
            $this->contract->car?->plate_number
        );

        return trim(<<<TEXT
            *This report is for the information of the customer and the settlement is not complete*
            AG number: {$agreementNumber}
            Customer Name: {$customerName}
            Mobile number: {$phone}
            Car: *{$carDescriptor}*
            --------------------------------------------------
            Days: {$this->rental_days}
            Rate: {$this->formatDailyRate()} AED
            Salik (4 AED): {$salik4Trips} Trips, {$this->formatCurrency($sumAmount('salik_4_aed'))} AED
            Salik (6 AED): {$salik6Trips} Trips, {$this->formatCurrency($sumAmount('salik_6_aed'))} AED
            Other revenue: {$otherRevenueTrips} Trips, {$this->formatCurrency($sumAmount('salik_other_revenue'))} AED
            {$insuranceLabel}: {$this->formatCurrency($insuranceDaily)} AED
            Baby seat: {$this->formatCurrency($childSeatAmount)} AED
            Fine: {$this->formatCurrency($sumAmount('fine'))} AED
            Pickup travel charge: {$this->formatCurrency($this->transfer_costs['pickup'] ?? 0)} AED
            Return travel charge: {$this->formatCurrency($this->transfer_costs['return'] ?? 0)} AED
            No Security Hold Fee: {$this->formatCurrency($sumAmount('no_deposit_fee'))} AED
            Parking: {$this->formatCurrency($sumAmount('parking'))} AED
            Petrol: {$this->formatCurrency($sumAmount('fuel'))} AED
            Car wash: {$this->formatCurrency($sumAmount('carwash'))} AED
            Scratch: {$this->formatCurrency($sumAmount('damage'))} AED
            Debt: {$this->formatCurrency(max($balance, 0))} AED
            ----------------------------------------------------
            Total Costs: {$this->formatCurrency($this->final_total)} AED
            vat: {$this->formatCurrency($this->tax_amount)} AED
            Security hold method: {$depositLabel}
            Security Hold: {$this->formatCurrency($securityHold)} AED
            Sub Total: {$this->formatCurrency($this->final_total +$securityHold)} AED
            ----------------------------------------------------
            Customer Payments: {$this->formatCurrency($customerPayments)} AED
            ----------------------------------------------------
            *Must get receive {$this->formatCurrency($balance)} AED*

            Other charges will be deducted from the security hold. The rest will be returned to the customer after 10 days.
            
            *Please check fine before receive the car*
            TEXT);
    }

    private function formatDailyRate(): string
    {
        $rate = $this->apply_discount && $this->custom_daily_rate
            ? (float) $this->custom_daily_rate
            : (float) ($this->dailyRate ?? 0);

        return number_format($rate, 2);
    }

    private function formatCurrency($value): string
    {
        if (! is_numeric($value)) {
            return '0.00';
        }

        return number_format((float) $value, 2);
    }

    private function depositCategoryLabel(?string $category): string
    {
        return match ($category) {
            'cash_aed' => 'Cash (based on AED)',
            'cheque' => 'Cheque',
            'transfer_cash_irr' => 'Transfer or Cash (based on IRR)',
            default => 'Security Hold',
        };
    }

    private function formattedDepositLabel(): string
    {
        $depositValue = $this->normalizedDeposit();

        if (!$this->deposit_category && !$depositValue) {
            return 'No Security Hold';
        }

        $label = $this->depositCategoryLabel($this->deposit_category);

        if ($this->deposit_category === 'cash_aed' && is_numeric($depositValue)) {
            return $label . ': ' . $this->formatCurrency($depositValue) . ' AED';
        }

        if ($depositValue) {
            return $label . ': ' . $depositValue;
        }

        return $label;
    }

    public function getCostComparisonDataProperty(): array
    {
        if (empty($this->originalCosts)) {
            return [];
        }

        $rows = [];

        $rows[] = $this->buildTextComparisonRow(
            'Vehicle',
            $this->originalSelections['car_label'] ?? '—',
            $this->getCarLabel($this->selectedCarId)
        );

        $rows[] = $this->buildTextComparisonRow(
            'Pickup Date',
            $this->formatDateTime($this->originalSelections['pickup_date'] ?? null),
            $this->formatDateTime($this->pickup_date)
        );

        $rows[] = $this->buildTextComparisonRow(
            'Return Date',
            $this->formatDateTime($this->originalSelections['return_date'] ?? null),
            $this->formatDateTime($this->return_date)
        );

        $rows[] = $this->buildNumericComparisonRow(
            'Rental Days',
            (float) ($this->originalCosts['rental_days'] ?? $this->rental_days),
            (float) $this->rental_days,
            ' days',
            0
        );

        $rows[] = $this->buildTextComparisonRow(
            'Pickup Location',
            $this->originalSelections['pickup_location'] ?? '—',
            $this->pickup_location
        );

        $rows[] = $this->buildTextComparisonRow(
            'Return Location',
            $this->originalSelections['return_location'] ?? '—',
            $this->return_location
        );

        $rows[] = $this->buildTextComparisonRow(
            'Insurance',
            $this->formatInsuranceLabel($this->originalSelections['selected_insurance'] ?? null),
            $this->formatInsuranceLabel($this->selected_insurance)
        );

        $rows[] = $this->buildTextComparisonRow(
            'Add-ons Selected',
            $this->formatServiceList($this->originalSelections['selected_services'] ?? []),
            $this->formatServiceList($this->selected_services ?? []),
            $this->describeServiceChanges($this->originalSelections['selected_services'] ?? [], $this->selected_services ?? [])
        );

        $rows[] = $this->buildNumericComparisonRow(
            'Child Seats',
            (float) ($this->originalSelections['service_quantities']['child_seat'] ?? 0),
            (float) ($this->service_quantities['child_seat'] ?? 0),
            ' seat(s)',
            0
        );

        $rows[] = $this->buildTextComparisonRow(
            'Driving License',
            $this->formatDrivingLicenseLabel($this->originalSelections['driving_license_option'] ?? null),
            $this->formatDrivingLicenseLabel($this->driving_license_option)
        );

        $rows[] = $this->buildNumericComparisonRow(
            'Driving License Cost',
            (float) ($this->originalCosts['driving_license_cost'] ?? 0),
            (float) ($this->driving_license_cost ?? 0),
            ' AED'
        );

        $rows[] = $this->buildNumericComparisonRow(
            'Driver Service Hours',
            (float) ($this->originalSelections['driver_hours'] ?? 0),
            (float) ($this->driver_hours ?? 0),
            ' h',
            1
        );

        $rows[] = $this->buildNumericComparisonRow(
            'Driver Service Cost',
            (float) ($this->originalCosts['driver_cost'] ?? 0),
            (float) ($this->driver_cost ?? 0),
            ' AED'
        );

        $rows[] = $this->buildNumericComparisonRow('Daily Rate', (float) ($this->originalCosts['daily_rate'] ?? 0), (float) $this->dailyRate, ' AED/day');
        $rows[] = $this->buildNumericComparisonRow('Base Rental Cost', (float) ($this->originalCosts['base_price'] ?? 0), (float) $this->base_price, ' AED');
        $rows[] = $this->buildNumericComparisonRow('Pickup Transfer Cost', (float) ($this->originalCosts['pickup_transfer'] ?? 0), (float) ($this->transfer_costs['pickup'] ?? 0), ' AED');
        $rows[] = $this->buildNumericComparisonRow('Return Transfer Cost', (float) ($this->originalCosts['return_transfer'] ?? 0), (float) ($this->transfer_costs['return'] ?? 0), ' AED');
        $rows[] = $this->buildNumericComparisonRow('Add-on Total', (float) ($this->originalCosts['services_total'] ?? 0), (float) $this->services_total, ' AED');
        $rows[] = $this->buildNumericComparisonRow('Insurance Total', (float) ($this->originalCosts['insurance_total'] ?? 0), (float) $this->insurance_total, ' AED');
        $rows[] = $this->buildNumericComparisonRow('Subtotal', (float) ($this->originalCosts['subtotal'] ?? 0), (float) $this->subtotal, ' AED');
        $rows[] = $this->buildNumericComparisonRow('Tax (5%)', (float) ($this->originalCosts['tax'] ?? 0), (float) $this->tax_amount, ' AED');
        $rows[] = $this->buildNumericComparisonRow('Total Amount', (float) ($this->originalCosts['total'] ?? 0), (float) $this->final_total, ' AED');

        return $rows;
    }

    private function buildNumericComparisonRow(string $label, float $original, float $current, string $suffix = '', int $precision = 2): array
    {
        $delta = $current - $original;
        $threshold = $precision === 0 ? 1 : 0.01;
        $changed = abs($delta) >= $threshold;

        return [
            'label' => $label,
            'original' => $this->formatNumber($original, $precision) . $suffix,
            'current' => $this->formatNumber($current, $precision) . $suffix,
            'change' => $changed ? [
                'type' => $delta > 0 ? 'increase' : 'decrease',
                'text' => ($delta > 0 ? '+' : '-') . $this->formatNumber(abs($delta), $precision) . $suffix,
            ] : null,
            'changed' => $changed,
        ];
    }

    private function buildTextComparisonRow(string $label, string $original, string $current, ?string $note = null): array
    {
        $normalizedOriginal = trim((string) $original) !== '' ? $original : '—';
        $normalizedCurrent = trim((string) $current) !== '' ? $current : '—';
        $changed = $normalizedOriginal !== $normalizedCurrent || !empty($note);
        $change = null;

        if (!empty($note)) {
            $change = ['type' => 'note', 'text' => $note];
        } elseif ($changed) {
            $change = ['type' => 'changed', 'text' => 'Changed'];
        }

        return [
            'label' => $label,
            'original' => $normalizedOriginal,
            'current' => $normalizedCurrent,
            'change' => $change,
            'changed' => $changed,
        ];
    }

    private function formatNumber(float $value, int $precision = 2): string
    {
        return number_format($value, $precision, '.', ',');
    }

    private function formatServiceList(array $services): string
    {
        $labels = [];

        foreach ($services as $serviceId) {
            $resolved = $this->resolveServiceId((string) $serviceId);

            if ($resolved && isset($this->services[$resolved])) {
                $labels[$resolved] = $this->services[$resolved]['label_en'] ?? Str::headline(str_replace('_', ' ', $resolved));
            }
        }

        if (empty($labels)) {
            return '—';
        }

        ksort($labels);

        return implode(', ', array_values($labels));
    }

    private function normalizedServiceQuantities($source = null, bool $includeZeros = false): array
    {
        if ($source === true && $includeZeros === false) {
            $includeZeros = true;
            $source = null;
        }

        $data = $source ?? $this->service_quantities;

        if (!is_array($data)) {
            return [];
        }

        $normalized = [];

        foreach ($data as $serviceId => $quantity) {
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

    private function inferServiceQuantityFromCharge(ContractCharges $charge, array $service): int
    {
        $unitAmount = $this->calculateServiceAmount($service, max(1, (int) $this->rental_days), 1);

        if ($unitAmount <= 0) {
            return 1;
        }

        $estimatedQuantity = (int) round($charge->amount / $unitAmount);

        return max(1, $estimatedQuantity);
    }

    private function describeServiceChanges(array $original, array $current): ?string
    {
        $originalNormalized = $this->normalizeServiceLabels($original);
        $currentNormalized = $this->normalizeServiceLabels($current);

        $added = array_diff_key($currentNormalized, $originalNormalized);
        $removed = array_diff_key($originalNormalized, $currentNormalized);

        if (empty($added) && empty($removed)) {
            return null;
        }

        $changes = [];

        if (!empty($added)) {
            $changes[] = 'Added: ' . implode(', ', array_values($added));
        }

        if (!empty($removed)) {
            $changes[] = 'Removed: ' . implode(', ', array_values($removed));
        }

        return implode(' • ', $changes);
    }

    private function normalizeServiceLabels(array $services): array
    {
        $normalized = [];

        foreach ($services as $serviceId) {
            $resolved = $this->resolveServiceId((string) $serviceId);

            if ($resolved && isset($this->services[$resolved])) {
                $normalized[$resolved] = $this->services[$resolved]['label_en'] ?? Str::headline(str_replace('_', ' ', $resolved));
            }
        }

        ksort($normalized);

        return $normalized;
    }

    private function formatInsuranceLabel($insuranceId): string
    {
        if (!$insuranceId || $insuranceId === 'basic_insurance') {
            return 'Basic Insurance (Included)';
        }

        $resolved = $this->resolveServiceId((string) $insuranceId);

        if ($resolved && isset($this->services[$resolved])) {
            return $this->services[$resolved]['label_en'] ?? Str::headline(str_replace('_', ' ', $resolved));
        }

        return Str::headline(str_replace('_', ' ', (string) $insuranceId));
    }

    private function formatDrivingLicenseLabel($option): string
    {
        if (!$option) {
            return 'None';
        }

        return $this->driving_license_options[$option]['label'] ?? Str::headline(str_replace('_', ' ', (string) $option));
    }

    private function getCarLabel($carId): string
    {
        if (!$carId) {
            return '—';
        }

        if (isset($this->carNameCache[$carId])) {
            return $this->carNameCache[$carId];
        }

        if ($this->contract && (int) $this->contract->car_id === (int) $carId) {
            $this->carNameCache[$carId] = $this->contract->car?->fullName() ?? '—';

            return $this->carNameCache[$carId];
        }

        if (is_iterable($this->carsForModel)) {
            foreach ($this->carsForModel as $car) {
                if ((int) $car->id === (int) $carId) {
                    $this->carNameCache[$carId] = $car->fullName();

                    return $this->carNameCache[$carId];
                }
            }
        }

        $car = Car::with('carModel')->find($carId);
        $this->carNameCache[$carId] = $car?->fullName() ?? '—';

        return $this->carNameCache[$carId];
    }

    private function stripPlateFromLabel(string $label): string
    {
        return trim((string) preg_replace('/\s*\([^)]*\)$/', '', $label));
    }

    private function formatCarDescriptor(string $label, ?string $plate): string
    {
        $cleanLabel = trim($label) !== '' ? $label : '---';

        if (!$plate) {
            return $cleanLabel;
        }

        return trim("{$cleanLabel} ({$plate})");
    }

    private function formatDateTime(?string $value): string
    {
        if (!$value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d M Y H:i');
        } catch (\Exception $exception) {
            return $value;
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

    private function inferDriverHoursFromCharge(ContractCharges $charge): float
    {
        if (isset($this->contract->meta['driver_hours'])) {
            return (float) $this->contract->meta['driver_hours'];
        }

        $amount = (float) $charge->amount;

        if ($amount <= 0) {
            return 0;
        }

        if ($amount <= 250) {
            return 8.0;
        }

        $extraCost = $amount - 250;
        $extraHours = max(0, (int) round($extraCost / 40));

        return 8 + $extraHours;
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

    private function roundCurrency($value): float
    {
        return round((float) $value, 2);
    }
}
