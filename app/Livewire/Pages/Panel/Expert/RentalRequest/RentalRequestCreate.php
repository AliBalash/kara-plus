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
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Pages\Panel\Expert\RentalRequest\Concerns\HandlesServicePricing;

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
    public $agent_sale = 'Website';
    public $submitted_by_name;
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
    public $selected_services = [];
    public $selected_insurance = 'basic_insurance';
    public $services_total = 0;
    public $insurance_total = 0;
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
    public $ldw_daily_rate = 0;
    public $scdw_daily_rate = 0;

    private $locationCosts = [
        'UAE/Dubai/Clock Tower/Main Branch' => ['under_3' => 0, 'over_3' => 0],
        'UAE/Dubai/Downtown' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Deira' => ['under_3' => 45, 'over_3' => 45], // اضافه‌شده
        'UAE/Dubai/Dubai Airport/Terminal 1' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Dubai Airport/Terminal 2' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Dubai Airport/Terminal 3' => ['under_3' => 50, 'over_3' => 0],
        'UAE/Dubai/Al Maktoum Airport' => ['under_3' => 190, 'over_3' => 190],
        'UAE/Dubai/Jumeirah 1, 2, 3' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JBR' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Marina' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JLT' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/JVC' => ['under_3' => 60, 'over_3' => 60],
        'UAE/Dubai/Business Bay' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Sheikh Zayed Road' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Mohammad Bin Zayed Road' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Damac Hills' => ['under_3' => 60, 'over_3' => 60],
        'UAE/Dubai/Damac Hills 2' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Arjan' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Al Warqa' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Creek Harbour' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Ras Al Khor' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Al Quoz' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Al Qusais' => ['under_3' => 50, 'over_3' => 50],
        'UAE/Dubai/Global Village' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Miracle Garden' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Palm' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Dubai/Hatta' => ['under_3' => 150, 'over_3' => 150],
        'UAE/Sharjah Airport' => ['under_3' => 70, 'over_3' => 70],
        'UAE/Ajman' => ['under_3' => 100, 'over_3' => 100],
        'UAE/Abu Dhabi Airport' => ['under_3' => 200, 'over_3' => 200],
    ];

    public function mount()
    {
        $this->services = config('carservices');
        $this->brands = CarModel::distinct()->pluck('brand')->filter()->sort()->values()->toArray();
        $this->submitted_by_name = $this->determineDefaultSubmitterName();
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
        ];
        return in_array($propertyName, $costRelatedFields) ||
            Str::startsWith($propertyName, 'selected_services.');
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
        $this->canonicalizeSelectedServices();
        $this->calculateRentalDays();
        $this->calculateBasePrice();
        $this->calculateTransferCosts();
        $this->calculateServicesTotal();
        $this->calculateDriverServiceCost();
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
            $this->dailyRate = ($this->apply_discount && $this->custom_daily_rate)
                ? $this->roundCurrency((float) $this->custom_daily_rate)
                : $standardRate;
            $this->base_price = $this->roundCurrency($this->dailyRate * $this->rental_days);
            $this->ldw_daily_rate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'ldw', $this->rental_days));
            $this->scdw_daily_rate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'scdw', $this->rental_days));
        } else {
            $this->dailyRate = $this->roundCurrency(0);
            $this->base_price = $this->roundCurrency(0);
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
        $days = $this->rental_days;

        foreach ($this->selected_services as $serviceId) {
            $service = $this->resolveServiceDefinition($serviceId);
            if (!$service) {
                continue;
            }

            $servicesTotal += $this->roundCurrency($this->calculateServiceAmount($service, $days));
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

    private function calculateTaxAndTotal()
    {
        $this->subtotal = $this->roundCurrency(
            $this->base_price
            + $this->services_total
            + $this->insurance_total
            + $this->transfer_costs['total']
            + $this->driver_cost
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
            'phone' => ['required', 'max:15'],
            'messenger_phone' => ['required', 'max:15'],
            'address' => ['nullable', 'string', 'max:255'],
            'national_code' => ['required'],
            'passport_number' => ['nullable', 'string', 'max:50', Rule::unique('customers')],
            'passport_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'selected_insurance' => ['nullable', Rule::in(['', 'basic_insurance', 'ldw_insurance', 'scdw_insurance'])],
            'kardo_required' => ['boolean'],
            'payment_on_delivery' => ['boolean'],
            'apply_discount' => ['boolean'],
            'custom_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'driver_hours' => ['nullable', 'numeric', 'min:0'],
            'driver_note' => ['nullable', 'string', 'max:1000'],
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
        'payment_on_delivery.boolean' => 'The payment on delivery field must be a boolean value.',
        'driver_hours.numeric' => 'Driver service hours must be a number.',
        'driver_hours.min' => 'Driver service hours cannot be negative.',
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
        'national_code' => 'national code',
        'passport_number' => 'passport number',
        'passport_expiry_date' => 'passport expiry date',
        'nationality' => 'nationality',
        'license_number' => 'license number',
        'selected_insurance' => 'insurance selection',
        'driver_hours' => 'driver service hours',
        'driver_note' => 'driver note',
        'custom_daily_rate' => 'custom daily rate',
    ];

    public function submit()
    {
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
                'agent_sale' => $this->agent_sale,
                'submitted_by_name' => $this->submitted_by_name ?: $this->determineDefaultSubmitterName(),
                'pickup_location' => $this->pickup_location,
                'return_location' => $this->return_location,
                'pickup_date' => $this->pickup_date,
                'return_date' => $this->return_date,
                'selected_services' => $this->selected_services,
                'selected_insurance' => $this->selected_insurance,
                'notes' => $this->notes,
                'kardo_required' => $this->kardo_required ?? true,
                'used_daily_rate' => $this->roundCurrency($this->dailyRate),
                'discount_note' => $this->apply_discount ? "Discount applied: {$this->custom_daily_rate} AED instead of standard rate" : null,
                'payment_on_delivery' => $this->payment_on_delivery ?? true,
                'meta' => $this->prepareContractMeta(),
            ]);

            $contract->changeStatus('pending', auth()->id());
            $this->contract = $contract;
            $this->storeContractCharges($contract);

            DB::commit();
            $this->toast('success', 'Contract created successfully!');
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

        return !empty($meta) ? $meta : null;
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
            'description' => ((int)$this->rental_days) . " روز × " . number_format($this->dailyRate, 2) . " درهم" . ($this->apply_discount ? ' (with discount)' : ''),
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

        foreach ($this->selected_services as $serviceId) {
            $resolvedId = $this->resolveServiceId($serviceId);
            if (!$resolvedId) {
                continue;
            }

            $service = $this->services[$resolvedId] ?? null;
            if (!$service) {
                continue;
            }

            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $resolvedId,
                'amount' => $this->roundCurrency($this->calculateServiceAmount($service, $this->rental_days)),
                'type' => 'addon',
                'description' => $this->buildServiceDescription($service, $this->rental_days),
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
                    'description' => ((int)$this->rental_days) . " روز",
                ]);
            }
        }

        if ($this->tax_amount > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'tax',
                'amount' => $this->roundCurrency($this->tax_amount),
                'type' => 'tax',
                'description' => '۵٪ مالیات بر ارزش افزوده',
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
