<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PublicReservationCarsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_id' => ['nullable', 'integer', 'exists:car_models,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'pickup_date' => ['nullable', 'date', 'required_with:return_date'],
            'return_date' => ['nullable', 'date', 'required_with:pickup_date', 'after:pickup_date'],
        ];
    }
}
