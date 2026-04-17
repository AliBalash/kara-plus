<?php

namespace App\Services\Reservations;

use App\Models\Agent;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Contract;
use App\Models\ContractCharges;
use App\Models\Customer;
use App\Models\LocationCost;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicReservationService
{
    private const TAX_RATE = 0.05;

    public function bootstrapData(): array
    {
        $services = $this->serviceDefinitions();
        $locationMap = $this->locationCostMap();
        $locationOptions = array_keys(array_filter($locationMap, static fn (array $item): bool => (bool) ($item['is_active'] ?? false)));
        $agents = $this->agents();
        $defaultAgent = Arr::first($agents, static fn (array $agent): bool => strtolower((string) $agent['name']) === 'website');

        return [
            'currency' => 'AED',
            'tax_rate' => self::TAX_RATE,
            'min_pickup_at' => now()->toIso8601String(),
            'services' => array_values(array_map(function (string $id, array $service): array {
                return [
                    'id' => $id,
                    'key' => $service['key'] ?? $id,
                    'label_en' => $service['label_en'] ?? $id,
                    'label_fa' => $service['label_fa'] ?? $service['label_en'] ?? $id,
                    'icon' => $service['icon'] ?? null,
                    'amount' => isset($service['amount']) ? (float) $service['amount'] : null,
                    'per_day' => (bool) ($service['per_day'] ?? false),
                    'is_insurance' => in_array($id, ['ldw_insurance', 'scdw_insurance'], true),
                ];
            }, array_keys($services), $services)),
            'driving_license_options' => $this->drivingLicenseOptions(),
            'location_options' => array_values($locationOptions),
            'location_costs' => array_map(static function (array $location): array {
                return [
                    'under_3' => (float) ($location['under_3'] ?? 0),
                    'over_3' => (float) ($location['over_3'] ?? 0),
                    'is_active' => (bool) ($location['is_active'] ?? false),
                ];
            }, $locationMap),
            'agents' => $agents,
            'default_agent_id' => $defaultAgent['id'] ?? null,
        ];
    }

    public function brands(): array
    {
        return CarModel::query()
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->filter()
            ->values()
            ->all();
    }

    public function models(?string $brand = null): array
    {
        $query = CarModel::query()
            ->select(['id', 'brand', 'model', 'is_featured'])
            ->orderBy('brand')
            ->orderBy('model');

        if ($brand !== null && trim($brand) !== '') {
            $query->where('brand', trim($brand));
        }

        return $query->get()
            ->map(static function (CarModel $model): array {
                return [
                    'id' => $model->id,
                    'brand' => $model->brand,
                    'model' => $model->model,
                    'is_featured' => (bool) $model->is_featured,
                ];
            })
            ->all();
    }

    public function cars(?int $modelId = null, ?string $brand = null, ?string $pickupDate = null, ?string $returnDate = null): array
    {
        $pickup = $pickupDate ? Carbon::parse($pickupDate) : null;
        $return = $returnDate ? Carbon::parse($returnDate) : null;

        $cars = Car::query()
            ->with([
                'carModel.image',
                'options',
                'currentContract.customer',
            ])
            ->when($modelId !== null, static fn ($query) => $query->where('car_model_id', $modelId))
            ->when($brand !== null && trim($brand) !== '', static function ($query) use ($brand) {
                $query->whereHas('carModel', static fn ($modelQuery) => $modelQuery->where('brand', trim($brand)));
            })
            ->orderByDesc('availability')
            ->orderByRaw('(select brand from car_models where car_models.id = cars.car_model_id limit 1)')
            ->orderByRaw('(select model from car_models where car_models.id = cars.car_model_id limit 1)')
            ->orderBy('plate_number')
            ->get();

        $conflictsByCarId = [];
        if ($pickup && $return) {
            $conflictsByCarId = $this->overlappingReservationsForCars($cars->pluck('id')->all(), $pickup, $return);
        }

        return $cars->map(function (Car $car) use ($conflictsByCarId): array {
            $conflicts = $conflictsByCarId[$car->id] ?? [];
            $hasConflict = count($conflicts) > 0;
            $isAvailable = !$hasConflict && $car->status !== 'under_maintenance';

            $activeContract = $car->currentContract;

            return [
                'id' => $car->id,
                'plate_number' => $car->plate_number,
                'status' => $car->status,
                'availability' => (bool) $car->availability,
                'is_available_for_selection' => $isAvailable,
                'manufacturing_year' => $car->manufacturing_year,
                'color' => $car->color,
                'primary_image_url' => $car->primaryImageUrl(),
                'car_model' => [
                    'id' => $car->carModel?->id,
                    'brand' => $car->carModel?->brand,
                    'model' => $car->carModel?->model,
                    'is_featured' => (bool) ($car->carModel?->is_featured ?? false),
                ],
                'pricing' => [
                    'short' => (float) $car->price_per_day_short,
                    'mid' => (float) ($car->price_per_day_mid ?? $car->price_per_day_short),
                    'long' => (float) ($car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short),
                ],
                'insurance_pricing' => [
                    'ldw' => [
                        'short' => (float) ($car->ldw_price_short ?? 0),
                        'mid' => (float) ($car->ldw_price_mid ?? $car->ldw_price_short ?? 0),
                        'long' => (float) ($car->ldw_price_long ?? $car->ldw_price_mid ?? $car->ldw_price_short ?? 0),
                    ],
                    'scdw' => [
                        'short' => (float) ($car->scdw_price_short ?? 0),
                        'mid' => (float) ($car->scdw_price_mid ?? $car->scdw_price_short ?? 0),
                        'long' => (float) ($car->scdw_price_long ?? $car->scdw_price_mid ?? $car->scdw_price_short ?? 0),
                    ],
                ],
                'options' => $car->options
                    ->mapWithKeys(static fn ($option): array => [(string) $option->option_key => (string) $option->option_value])
                    ->all(),
                'active_contract' => $activeContract ? [
                    'id' => $activeContract->id,
                    'status' => $activeContract->current_status,
                    'pickup_date' => optional($activeContract->pickup_date)->toIso8601String(),
                    'return_date' => optional($activeContract->return_date)->toIso8601String(),
                    'customer_name' => $activeContract->customer?->fullName(),
                ] : null,
                'conflicts' => $conflicts,
            ];
        })->all();
    }

    public function quote(array $payload): array
    {
        $normalized = $this->normalizeQuotePayload($payload);
        $car = Car::query()->with('carModel')->findOrFail($normalized['selected_car_id']);

        return $this->buildQuote($normalized, $car);
    }

    public function createReservation(array $payload): array
    {
        $normalized = $this->normalizeQuotePayload($payload);

        return DB::transaction(function () use ($normalized, $payload): array {
            /** @var Car $car */
            $car = Car::query()->lockForUpdate()->findOrFail($normalized['selected_car_id']);

            $quote = $this->buildQuote($normalized, $car);
            if (($quote['availability']['has_conflict'] ?? false) === true) {
                throw ValidationException::withMessages([
                    'selected_car_id' => [$quote['availability']['message'] ?? 'خودروی انتخاب‌شده در بازه زمانی انتخابی در دسترس نیست.'],
                ]);
            }

            $phone = PhoneNumber::normalize($payload['phone'] ?? null) ?? trim((string) ($payload['phone'] ?? ''));
            $messengerPhone = PhoneNumber::normalize($payload['messenger_phone'] ?? null) ?? trim((string) ($payload['messenger_phone'] ?? ''));
            $email = isset($payload['email']) ? trim((string) $payload['email']) : null;
            $passportNumber = isset($payload['passport_number']) ? trim((string) $payload['passport_number']) : null;
            $nationalCode = trim((string) ($payload['national_code'] ?? ''));
            $licenseNumber = isset($payload['license_number']) ? trim((string) $payload['license_number']) : null;

            $customer = $this->resolveCustomer(
                $phone,
                $email,
                $passportNumber,
                $nationalCode,
                $licenseNumber
            );

            $customer->fill([
                'first_name' => trim((string) ($payload['first_name'] ?? '')),
                'last_name' => trim((string) ($payload['last_name'] ?? '')),
                'national_code' => $nationalCode,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone,
                'messenger_phone' => $messengerPhone,
                'address' => $this->nullableString($payload['address'] ?? null),
                'birth_date' => $payload['birth_date'] ?? null,
                'passport_number' => $passportNumber !== '' ? $passportNumber : null,
                'passport_expiry_date' => $payload['passport_expiry_date'] ?? null,
                'nationality' => trim((string) ($payload['nationality'] ?? '')),
                'license_number' => $licenseNumber !== '' ? $licenseNumber : null,
            ]);
            $customer->save();

            $agentId = isset($payload['agent_id'])
                ? (int) $payload['agent_id']
                : Agent::query()->where('name', 'Website')->value('id');

            $applyDiscount = (bool) ($normalized['apply_discount'] ?? false);
            $customRate = $normalized['custom_daily_rate'];

            $meta = [
                'source' => 'public_website_api',
                'selected_services' => $quote['selected_services'],
                'selected_insurance' => $quote['selected_insurance'],
                'service_quantities' => $quote['service_quantities'],
                'quote_snapshot' => Arr::except($quote, ['availability']),
            ];

            if (($quote['driver_hours'] ?? 0) > 0) {
                $meta['driver_hours'] = (float) $quote['driver_hours'];
                $meta['driver_service_cost'] = (float) $quote['driver_cost'];
            }

            if (($payload['payment_on_delivery'] ?? true) && $this->nullableString($payload['driver_note'] ?? null) !== null) {
                $meta['driver_note'] = $this->nullableString($payload['driver_note']);
            }

            if ($quote['driving_license_option'] !== null) {
                $meta['driving_license_option'] = $quote['driving_license_option'];
                $meta['driving_license_cost'] = (float) $quote['driving_license_cost'];
            }

            $contract = Contract::create([
                'user_id' => null,
                'customer_id' => $customer->id,
                'car_id' => $car->id,
                'agent_id' => $agentId,
                'submitted_by_name' => $this->nullableString($payload['submitted_by_name'] ?? null) ?? 'Website',
                'pickup_date' => $quote['pickup_date'],
                'return_date' => $quote['return_date'],
                'pickup_location' => $quote['pickup_location'],
                'return_location' => $quote['return_location'],
                'total_price' => $quote['final_total'],
                'kardo_required' => (bool) ($payload['kardo_required'] ?? true),
                'payment_on_delivery' => (bool) ($payload['payment_on_delivery'] ?? true),
                'notes' => $this->nullableString($payload['notes'] ?? null),
                'licensed_driver_name' => $this->nullableString($payload['licensed_driver_name'] ?? null),
                'deposit' => $this->normalizedDeposit($payload['deposit_category'] ?? null, $payload['deposit'] ?? null),
                'deposit_category' => $this->nullableString($payload['deposit_category'] ?? null),
                'used_daily_rate' => $quote['daily_rate'],
                'custom_daily_rate_enabled' => $applyDiscount,
                'discount_note' => ($applyDiscount && $customRate !== null)
                    ? sprintf('Discount applied: %.2f AED instead of standard rate', (float) $customRate)
                    : null,
                'meta' => $meta,
            ]);

            $contract->changeStatus('pending', null);
            $this->storeContractCharges($contract, $quote, $car);

            return [
                'contract_id' => $contract->id,
                'status' => $contract->current_status,
                'customer_id' => $customer->id,
                'total_price' => (float) $contract->total_price,
                'quote' => $quote,
            ];
        });
    }

    private function buildQuote(array $normalized, Car $car): array
    {
        $pickup = $normalized['pickup'];
        $return = $normalized['return'];
        $rentalDays = max(1, (int) ceil(($return->getTimestamp() - $pickup->getTimestamp()) / 86400));

        $standardDailyRate = $this->roundCurrency($this->getCarDailyRate($car, $rentalDays));
        $dailyRate = ($normalized['apply_discount'] && $normalized['custom_daily_rate'])
            ? $this->roundCurrency((float) $normalized['custom_daily_rate'])
            : $standardDailyRate;
        $basePrice = $this->roundCurrency($dailyRate * $rentalDays);

        $pickupTransfer = $this->roundCurrency($this->calculateLocationFee($normalized['pickup_location'], $rentalDays));
        $returnTransfer = $this->roundCurrency($this->calculateLocationFee($normalized['return_location'], $rentalDays));
        $transferCosts = [
            'pickup' => $pickupTransfer,
            'return' => $returnTransfer,
            'total' => $this->roundCurrency($pickupTransfer + $returnTransfer),
        ];

        $selectedServices = $this->canonicalizeSelectedServices($normalized['selected_services']);
        $serviceQuantities = $this->normalizedServiceQuantities($normalized['service_quantities']);
        [$selectedServices, $serviceQuantities] = $this->syncServiceSelectionWithQuantities($selectedServices, $serviceQuantities);

        $serviceDefinitions = $this->serviceDefinitions();
        $serviceRows = [];
        $servicesTotal = 0.0;

        foreach ($selectedServices as $serviceId) {
            $service = $serviceDefinitions[$serviceId] ?? null;
            if (!$service) {
                continue;
            }

            $quantity = $this->serviceQuantity($serviceId, $serviceQuantities);
            if ($quantity <= 0) {
                continue;
            }

            $amount = $this->roundCurrency($this->calculateServiceAmount($service, $rentalDays, $quantity));
            $servicesTotal += $amount;

            $serviceRows[] = [
                'id' => $serviceId,
                'label_en' => $service['label_en'] ?? $serviceId,
                'label_fa' => $service['label_fa'] ?? $service['label_en'] ?? $serviceId,
                'quantity' => $quantity,
                'per_day' => (bool) ($service['per_day'] ?? false),
                'unit_amount' => (float) ($service['amount'] ?? 0),
                'amount' => $amount,
                'description' => $this->buildServiceDescription($service, $rentalDays, $quantity),
            ];
        }

        $servicesTotal = $this->roundCurrency($servicesTotal);

        $selectedInsurance = $normalized['selected_insurance'];
        $insuranceTotal = 0.0;
        $ldwDailyRate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'ldw', $rentalDays));
        $scdwDailyRate = $this->roundCurrency($this->getInsuranceDailyRate($car, 'scdw', $rentalDays));

        if ($selectedInsurance === 'ldw_insurance') {
            $insuranceTotal = $this->roundCurrency($ldwDailyRate * $rentalDays);
        } elseif ($selectedInsurance === 'scdw_insurance') {
            $insuranceTotal = $this->roundCurrency($scdwDailyRate * $rentalDays);
        }

        $driverHours = max(0, (float) $normalized['driver_hours']);
        $driverCost = $this->calculateDriverServiceCost($driverHours);

        $drivingLicenseOption = $normalized['driving_license_option'];
        $drivingLicenseOptions = $this->drivingLicenseOptions();
        $drivingLicenseCost = 0.0;
        if ($drivingLicenseOption !== null && isset($drivingLicenseOptions[$drivingLicenseOption])) {
            $drivingLicenseCost = $this->roundCurrency((float) ($drivingLicenseOptions[$drivingLicenseOption]['amount'] ?? 0));
        } else {
            $drivingLicenseOption = null;
        }

        $subtotal = $this->roundCurrency(
            $basePrice
                + $servicesTotal
                + $insuranceTotal
                + $transferCosts['total']
                + $driverCost
                + $drivingLicenseCost
        );
        $taxAmount = $this->roundCurrency($subtotal * self::TAX_RATE);
        $finalTotal = $this->roundCurrency($subtotal + $taxAmount);

        $conflicts = $this->overlappingReservationsForCar($car->id, $pickup, $return);
        $hasConflict = count($conflicts) > 0;

        $lineItems = $this->lineItemsFromQuote(
            $rentalDays,
            $dailyRate,
            $basePrice,
            $transferCosts,
            $serviceRows,
            $selectedInsurance,
            $insuranceTotal,
            $driverHours,
            $driverCost,
            $drivingLicenseOption,
            $drivingLicenseCost,
            $taxAmount
        );

        return [
            'currency' => 'AED',
            'pickup_date' => $pickup->toIso8601String(),
            'return_date' => $return->toIso8601String(),
            'pickup_location' => $normalized['pickup_location'],
            'return_location' => $normalized['return_location'],
            'rental_days' => $rentalDays,
            'daily_rate' => $dailyRate,
            'standard_daily_rate' => $standardDailyRate,
            'base_price' => $basePrice,
            'transfer_costs' => $transferCosts,
            'services_total' => $servicesTotal,
            'service_rows' => $serviceRows,
            'selected_services' => $selectedServices,
            'service_quantities' => $serviceQuantities,
            'selected_insurance' => $selectedInsurance,
            'insurance_total' => $insuranceTotal,
            'ldw_daily_rate' => $ldwDailyRate,
            'scdw_daily_rate' => $scdwDailyRate,
            'driver_hours' => $driverHours,
            'driver_cost' => $driverCost,
            'driving_license_option' => $drivingLicenseOption,
            'driving_license_cost' => $drivingLicenseCost,
            'subtotal' => $subtotal,
            'tax_rate' => self::TAX_RATE,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal,
            'availability' => [
                'has_conflict' => $hasConflict,
                'message' => $hasConflict
                    ? sprintf(
                        'این خودرو از %s تا %s قبلا رزرو شده است.',
                        $conflicts[0]['pickup_date'],
                        $conflicts[0]['return_date']
                    )
                    : null,
                'conflicts' => $conflicts,
            ],
            'line_items' => $lineItems,
        ];
    }

    private function storeContractCharges(Contract $contract, array $quote, Car $car): void
    {
        ContractCharges::query()->where('contract_id', $contract->id)->delete();

        ContractCharges::create([
            'contract_id' => $contract->id,
            'title' => 'base_rental',
            'amount' => $quote['base_price'],
            'type' => 'base',
            'description' => sprintf(
                '%d %s × %s AED',
                (int) $quote['rental_days'],
                (int) $quote['rental_days'] === 1 ? 'day' : 'days',
                number_format((float) $quote['daily_rate'], 2, '.', '')
            ),
        ]);

        if (($quote['transfer_costs']['pickup'] ?? 0) > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'pickup_transfer',
                'amount' => $quote['transfer_costs']['pickup'],
                'type' => 'location_fee',
                'description' => $quote['pickup_location'],
            ]);
        }

        if (($quote['transfer_costs']['return'] ?? 0) > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'return_transfer',
                'amount' => $quote['transfer_costs']['return'],
                'type' => 'location_fee',
                'description' => $quote['return_location'],
            ]);
        }

        if (($quote['driver_cost'] ?? 0) > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'driver_service',
                'amount' => $quote['driver_cost'],
                'type' => 'service',
                'description' => $this->buildDriverChargeDescription((float) ($quote['driver_hours'] ?? 0)),
            ]);
        }

        if (($quote['driving_license_cost'] ?? 0) > 0 && !empty($quote['driving_license_option'])) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'driving_license_' . $quote['driving_license_option'],
                'amount' => $quote['driving_license_cost'],
                'type' => 'service',
                'description' => $this->buildDrivingLicenseDescription((string) $quote['driving_license_option'], (float) $quote['driving_license_cost']),
            ]);
        }

        foreach ($quote['service_rows'] as $serviceRow) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $serviceRow['id'],
                'amount' => $serviceRow['amount'],
                'type' => 'addon',
                'description' => $serviceRow['description'],
            ]);
        }

        if (in_array($quote['selected_insurance'], ['ldw_insurance', 'scdw_insurance'], true) && ($quote['insurance_total'] ?? 0) > 0) {
            $insuranceDaily = $quote['selected_insurance'] === 'ldw_insurance'
                ? $this->getInsuranceDailyRate($car, 'ldw', (int) $quote['rental_days'])
                : $this->getInsuranceDailyRate($car, 'scdw', (int) $quote['rental_days']);

            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => $quote['selected_insurance'],
                'amount' => $quote['insurance_total'],
                'type' => 'insurance',
                'description' => sprintf(
                    '%d %s × %s AED',
                    (int) $quote['rental_days'],
                    (int) $quote['rental_days'] === 1 ? 'day' : 'days',
                    number_format($insuranceDaily, 2, '.', '')
                ),
            ]);
        }

        if (($quote['tax_amount'] ?? 0) > 0) {
            ContractCharges::create([
                'contract_id' => $contract->id,
                'title' => 'tax',
                'amount' => $quote['tax_amount'],
                'type' => 'tax',
                'description' => '5% VAT',
            ]);
        }
    }

    private function lineItemsFromQuote(
        int $rentalDays,
        float $dailyRate,
        float $basePrice,
        array $transferCosts,
        array $serviceRows,
        ?string $selectedInsurance,
        float $insuranceTotal,
        float $driverHours,
        float $driverCost,
        ?string $drivingLicenseOption,
        float $drivingLicenseCost,
        float $taxAmount
    ): array {
        $rows = [[
            'key' => 'base_rental',
            'type' => 'base',
            'title' => 'Base Rental',
            'amount' => $basePrice,
            'description' => sprintf('%d day(s) × %.2f AED', $rentalDays, $dailyRate),
        ]];

        if (($transferCosts['pickup'] ?? 0) > 0) {
            $rows[] = [
                'key' => 'pickup_transfer',
                'type' => 'location_fee',
                'title' => 'Pickup Transfer',
                'amount' => (float) $transferCosts['pickup'],
                'description' => 'Pickup location transfer fee',
            ];
        }

        if (($transferCosts['return'] ?? 0) > 0) {
            $rows[] = [
                'key' => 'return_transfer',
                'type' => 'location_fee',
                'title' => 'Return Transfer',
                'amount' => (float) $transferCosts['return'],
                'description' => 'Return location transfer fee',
            ];
        }

        foreach ($serviceRows as $serviceRow) {
            $rows[] = [
                'key' => $serviceRow['id'],
                'type' => 'addon',
                'title' => $serviceRow['label_en'],
                'amount' => (float) $serviceRow['amount'],
                'description' => $serviceRow['description'],
            ];
        }

        if ($selectedInsurance !== null && $insuranceTotal > 0) {
            $rows[] = [
                'key' => $selectedInsurance,
                'type' => 'insurance',
                'title' => strtoupper(str_replace('_insurance', '', $selectedInsurance)) . ' Insurance',
                'amount' => $insuranceTotal,
                'description' => sprintf('%d day(s)', $rentalDays),
            ];
        }

        if ($drivingLicenseOption !== null && $drivingLicenseCost > 0) {
            $rows[] = [
                'key' => 'driving_license_' . $drivingLicenseOption,
                'type' => 'service',
                'title' => 'Driving License',
                'amount' => $drivingLicenseCost,
                'description' => $drivingLicenseOption,
            ];
        }

        if ($driverCost > 0) {
            $rows[] = [
                'key' => 'driver_service',
                'type' => 'service',
                'title' => 'Driver Service',
                'amount' => $driverCost,
                'description' => $this->buildDriverChargeDescription($driverHours),
            ];
        }

        if ($taxAmount > 0) {
            $rows[] = [
                'key' => 'tax',
                'type' => 'tax',
                'title' => 'VAT',
                'amount' => $taxAmount,
                'description' => '5% VAT',
            ];
        }

        return $rows;
    }

    private function normalizedDeposit(?string $category, mixed $deposit): ?string
    {
        $category = $this->nullableString($category);
        if ($category === null) {
            return null;
        }

        if ($category === 'cash_aed' && is_numeric($deposit)) {
            return number_format(max(0, (float) $deposit), 2, '.', '');
        }

        $value = $this->nullableString(is_scalar($deposit) ? (string) $deposit : null);

        return $value;
    }

    private function resolveCustomer(
        string $phone,
        ?string $email,
        ?string $passportNumber,
        ?string $nationalCode,
        ?string $licenseNumber
    ): Customer {
        $customer = Customer::query()
            ->where('phone', $phone)
            ->when($email, static fn ($query) => $query->orWhere('email', $email))
            ->when($passportNumber, static fn ($query) => $query->orWhere('passport_number', $passportNumber))
            ->when($nationalCode, static fn ($query) => $query->orWhere('national_code', $nationalCode))
            ->when($licenseNumber, static fn ($query) => $query->orWhere('license_number', $licenseNumber))
            ->first();

        return $customer ?? new Customer();
    }

    private function normalizeQuotePayload(array $payload): array
    {
        $pickup = Carbon::parse((string) $payload['pickup_date']);
        $return = Carbon::parse((string) $payload['return_date']);

        $selectedServices = is_array($payload['selected_services'] ?? null)
            ? array_values(array_map(static fn ($item): string => (string) $item, $payload['selected_services']))
            : [];

        $serviceQuantities = is_array($payload['service_quantities'] ?? null)
            ? $payload['service_quantities']
            : [];

        $selectedInsurance = isset($payload['selected_insurance']) ? trim((string) $payload['selected_insurance']) : null;
        if ($selectedInsurance === '') {
            $selectedInsurance = null;
        }

        $drivingLicenseOption = isset($payload['driving_license_option']) ? trim((string) $payload['driving_license_option']) : null;
        if ($drivingLicenseOption === '') {
            $drivingLicenseOption = null;
        }

        $customDailyRate = isset($payload['custom_daily_rate']) && $payload['custom_daily_rate'] !== ''
            ? (float) $payload['custom_daily_rate']
            : null;

        return [
            'selected_car_id' => (int) $payload['selected_car_id'],
            'pickup' => $pickup,
            'return' => $return,
            'pickup_location' => trim((string) $payload['pickup_location']),
            'return_location' => trim((string) $payload['return_location']),
            'selected_services' => $selectedServices,
            'service_quantities' => $serviceQuantities,
            'selected_insurance' => $selectedInsurance,
            'apply_discount' => (bool) ($payload['apply_discount'] ?? false),
            'custom_daily_rate' => $customDailyRate,
            'driver_hours' => (float) ($payload['driver_hours'] ?? 0),
            'driving_license_option' => $drivingLicenseOption,
        ];
    }

    private function calculateLocationFee(string $location, int $days): float
    {
        $feeType = $days < 3 ? 'under_3' : 'over_3';
        $costMap = $this->locationCostMap();

        return (float) ($costMap[$location][$feeType] ?? 0);
    }

    private function locationCostMap(): array
    {
        return LocationCost::query()
            ->orderBy('location')
            ->get()
            ->mapWithKeys(static function (LocationCost $cost): array {
                return [
                    $cost->location => [
                        'under_3' => (float) $cost->under_3_fee,
                        'over_3' => (float) $cost->over_3_fee,
                        'is_active' => (bool) $cost->is_active,
                    ],
                ];
            })
            ->toArray();
    }

    private function serviceDefinitions(): array
    {
        /** @var array<string, array<string, mixed>> $services */
        $services = config('carservices', []);

        return $services;
    }

    private function getCarDailyRate(Car $car, int $days): float
    {
        if ($days >= 28) {
            return (float) ($car->price_per_day_long ?? $car->price_per_day_mid ?? $car->price_per_day_short);
        }

        if ($days >= 7) {
            return (float) ($car->price_per_day_mid ?? $car->price_per_day_short);
        }

        return (float) $car->price_per_day_short;
    }

    private function getInsuranceDailyRate(Car $car, string $type, int $days): float
    {
        $prefix = $type . '_price_';

        if ($days >= 28) {
            return (float) ($car->{$prefix . 'long'} ?? $car->{$prefix . 'mid'} ?? $car->{$prefix . 'short'} ?? 0);
        }

        if ($days >= 7) {
            return (float) ($car->{$prefix . 'mid'} ?? $car->{$prefix . 'short'} ?? 0);
        }

        return (float) ($car->{$prefix . 'short'} ?? 0);
    }

    private function calculateDriverServiceCost(float $hours): float
    {
        if ($hours <= 0) {
            return $this->roundCurrency(0);
        }

        $totalMinutes = (int) ceil($hours * 60);
        if ($totalMinutes <= 0) {
            return $this->roundCurrency(0);
        }

        $baseCost = 250;
        $includedMinutes = 8 * 60;

        if ($totalMinutes <= $includedMinutes) {
            return $this->roundCurrency($baseCost);
        }

        $extraMinutes = $totalMinutes - $includedMinutes;
        $extraHours = (int) ceil($extraMinutes / 60);
        $additionalCost = $extraHours * 40;

        return $this->roundCurrency($baseCost + $additionalCost);
    }

    private function buildDriverChargeDescription(float $hours): string
    {
        $hours = max(0, $hours);
        $minutes = (int) ceil($hours * 60);

        if ($minutes <= 0) {
            return 'Driver service not requested';
        }

        $formattedHours = $hours === floor($hours)
            ? (string) (int) $hours
            : number_format($hours, 2, '.', '');

        $includedMinutes = 8 * 60;
        $extraDescription = 'Includes first 8 hours at 250 AED';

        if ($minutes > $includedMinutes) {
            $extraMinutes = $minutes - $includedMinutes;
            $extraHours = (int) ceil($extraMinutes / 60);
            $extraDescription .= " + {$extraHours} extra hour(s) at 40 AED each";
        }

        return "Driver service for {$formattedHours} hour(s) — {$extraDescription}";
    }

    private function buildDrivingLicenseDescription(string $option, float $amount): string
    {
        $options = $this->drivingLicenseOptions();
        $label = $options[$option]['label'] ?? 'Driving License';

        return sprintf('%s - %.2f AED', $label, $amount);
    }

    private function calculateServiceAmount(array $service, int $days, int $quantity): float
    {
        $amount = (float) ($service['amount'] ?? 0);
        $quantity = max(0, $quantity);

        if ($quantity === 0) {
            return 0.0;
        }

        $value = !empty($service['per_day'])
            ? $amount * max($days, 1)
            : $amount;

        return $this->roundCurrency($value * $quantity);
    }

    private function buildServiceDescription(array $service, int $days, int $quantity): string
    {
        $quantity = max(1, $quantity);

        if (!empty($service['per_day'])) {
            $dailyAmount = number_format((float) ($service['amount'] ?? 0), 2, '.', '');
            $quantityPrefix = $quantity > 1 ? ($quantity . ' × ') : '';
            $dayCount = max($days, 1);
            $dayLabel = $dayCount === 1 ? 'day' : 'days';

            return sprintf('%s%d %s × %s AED', $quantityPrefix, $dayCount, $dayLabel, $dailyAmount);
        }

        $oneTime = 'One-time fee';

        return $quantity > 1 ? sprintf('%d × %s', $quantity, $oneTime) : $oneTime;
    }

    private function normalizeServiceId(string $serviceId): string
    {
        $prepared = str_replace([' ', '-'], '_', trim($serviceId));

        return Str::snake($prepared);
    }

    private function resolveServiceId(string $serviceId): ?string
    {
        $services = $this->serviceDefinitions();

        if (isset($services[$serviceId])) {
            return $serviceId;
        }

        $normalized = $this->normalizeServiceId($serviceId);

        return isset($services[$normalized]) ? $normalized : null;
    }

    private function canonicalizeSelectedServices(array $selectedServices): array
    {
        $canonical = [];

        foreach ($selectedServices as $serviceId) {
            $resolved = $this->resolveServiceId((string) $serviceId);

            if ($resolved !== null && !in_array($resolved, $canonical, true) && !in_array($resolved, ['ldw_insurance', 'scdw_insurance'], true)) {
                $canonical[] = $resolved;
            }
        }

        return $canonical;
    }

    private function normalizedServiceQuantities(array $input, bool $includeZeros = false): array
    {
        $normalized = [];

        foreach ($input as $serviceId => $quantity) {
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

    /**
     * @param  array<int, string>  $selectedServices
     * @param  array<string, int>  $serviceQuantities
     * @return array{0: array<int, string>, 1: array<string, int>}
     */
    private function syncServiceSelectionWithQuantities(array $selectedServices, array $serviceQuantities): array
    {
        $childSeatQuantity = max(0, (int) ($serviceQuantities['child_seat'] ?? 0));
        if ($childSeatQuantity > 0) {
            if (!in_array('child_seat', $selectedServices, true)) {
                $selectedServices[] = 'child_seat';
            }
        } else {
            $selectedServices = array_values(array_filter($selectedServices, static fn (string $id): bool => $id !== 'child_seat'));
            unset($serviceQuantities['child_seat']);
        }

        if ($childSeatQuantity > 0) {
            $serviceQuantities['child_seat'] = $childSeatQuantity;
        }

        return [array_values(array_unique($selectedServices)), $serviceQuantities];
    }

    /**
     * @param  array<string, int>  $serviceQuantities
     */
    private function serviceQuantity(string $serviceId, array $serviceQuantities): int
    {
        if ($serviceId === 'child_seat') {
            return max(0, (int) ($serviceQuantities['child_seat'] ?? 0));
        }

        return max(1, (int) ($serviceQuantities[$serviceId] ?? 1));
    }

    private function roundCurrency(float|int $value): float
    {
        return round((float) $value, 2);
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function agents(): array
    {
        return Agent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Agent $agent): array => [
                'id' => $agent->id,
                'name' => $agent->name,
            ])
            ->all();
    }

    /**
     * @return array<string, array{label:string,amount:float}>
     */
    private function drivingLicenseOptions(): array
    {
        return [
            'one_year' => [
                'label' => 'Driving License (1 Year)',
                'amount' => 32.0,
            ],
            'three_year' => [
                'label' => 'Driving License (3 Years)',
                'amount' => 220.0,
            ],
        ];
    }

    /**
     * @param  array<int, int>  $carIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function overlappingReservationsForCars(array $carIds, Carbon $pickup, Carbon $return): array
    {
        if ($carIds === []) {
            return [];
        }

        return Contract::query()
            ->whereIn('car_id', $carIds)
            ->whereIn('current_status', Car::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<', $return)
            ->where(function ($query) use ($pickup) {
                $query->whereNull('return_date')
                    ->orWhere('return_date', '>', $pickup);
            })
            ->orderBy('pickup_date')
            ->get(['id', 'car_id', 'pickup_date', 'return_date', 'current_status'])
            ->groupBy('car_id')
            ->map(function ($contracts): array {
                return $contracts->map(function (Contract $contract): array {
                    return [
                        'id' => $contract->id,
                        'pickup_date' => optional($contract->pickup_date)->format('Y-m-d H:i'),
                        'return_date' => optional($contract->return_date)->format('Y-m-d H:i'),
                        'status' => $contract->current_status,
                    ];
                })->all();
            })
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function overlappingReservationsForCar(int $carId, Carbon $pickup, Carbon $return): array
    {
        return $this->overlappingReservationsForCars([$carId], $pickup, $return)[$carId] ?? [];
    }
}
