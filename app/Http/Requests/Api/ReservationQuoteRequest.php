<?php

namespace App\Http\Requests\Api;

use App\Models\LocationCost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReservationQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $selectedInsurance = $this->input('selected_insurance');
        $drivingLicenseOption = $this->input('driving_license_option');

        $this->merge([
            'selected_insurance' => $selectedInsurance === '' ? null : $selectedInsurance,
            'driving_license_option' => $drivingLicenseOption === '' ? null : $drivingLicenseOption,
        ]);
    }

    public function rules(): array
    {
        $locationRules = ['required', 'string', 'max:255'];
        $locations = $this->activeLocations();
        if ($locations !== []) {
            $locationRules[] = Rule::in($locations);
        }

        $serviceRules = ['string', 'max:100'];
        $serviceKeys = $this->serviceKeys();
        if ($serviceKeys !== []) {
            $serviceRules[] = Rule::in($serviceKeys);
        }

        return [
            'selected_car_id' => ['required', 'integer', 'exists:cars,id'],
            'pickup_location' => $locationRules,
            'return_location' => $locationRules,
            'pickup_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after:pickup_date'],
            'selected_services' => ['nullable', 'array'],
            'selected_services.*' => $serviceRules,
            'service_quantities' => ['nullable', 'array'],
            'service_quantities.*' => ['nullable', 'integer', 'min:0'],
            'selected_insurance' => ['nullable', Rule::in(['basic_insurance', 'ldw_insurance', 'scdw_insurance'])],
            'driving_license_option' => ['nullable', Rule::in(['one_year', 'three_year'])],
            'apply_discount' => ['nullable', 'boolean'],
            'custom_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'driver_hours' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $serviceKeys = $this->serviceKeys();
        if ($serviceKeys === []) {
            return;
        }

        $validator->after(function (Validator $validator) use ($serviceKeys): void {
            $quantities = $this->input('service_quantities');
            if (!is_array($quantities)) {
                return;
            }

            foreach (array_keys($quantities) as $serviceId) {
                if (!in_array((string) $serviceId, $serviceKeys, true)) {
                    $validator->errors()->add(
                        "service_quantities.{$serviceId}",
                        'The selected service quantity key is invalid.'
                    );
                }
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function activeLocations(): array
    {
        return LocationCost::query()
            ->where('is_active', true)
            ->pluck('location')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function serviceKeys(): array
    {
        return array_values(array_keys((array) config('carservices', [])));
    }
}
