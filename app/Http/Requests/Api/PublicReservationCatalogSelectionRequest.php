<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PublicReservationCatalogSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_code' => ['required', 'string', 'max:40'],
            'pickup_date' => ['nullable', 'date', 'required_with:return_date'],
            'return_date' => ['nullable', 'date', 'required_with:pickup_date', 'after:pickup_date'],
        ];
    }
}
