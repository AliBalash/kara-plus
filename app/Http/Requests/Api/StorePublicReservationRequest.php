<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StorePublicReservationRequest extends ReservationQuoteRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'messenger_phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'national_code' => ['required', 'string', 'max:50'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'licensed_driver_name' => ['nullable', 'string', 'max:255'],
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'submitted_by_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'driver_note' => ['nullable', 'string', 'max:1000'],
            'kardo_required' => ['nullable', 'boolean'],
            'payment_on_delivery' => ['nullable', 'boolean'],
            'deposit_category' => ['nullable', Rule::in(['cash_aed', 'cheque', 'transfer_cash_irr']), 'required_with:deposit'],
            'deposit' => $this->depositRules(),
        ]);
    }

    private function depositRules(): array
    {
        $rules = ['nullable'];
        $category = $this->input('deposit_category');

        if ($category === 'cash_aed') {
            $rules[] = 'required_with:deposit_category';
            $rules[] = 'numeric';
            $rules[] = 'min:0';
        } elseif ($category) {
            $rules[] = 'required_with:deposit_category';
            $rules[] = 'string';
            $rules[] = 'max:1000';
        }

        return $rules;
    }
}
