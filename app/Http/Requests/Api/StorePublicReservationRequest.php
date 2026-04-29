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
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'messenger_phone' => ['required', 'regex:/^\+\d{8,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'national_code' => ['nullable', 'string'],
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

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'شماره تماس باید با + شروع شود و بین ۸ تا ۱۵ رقم باشد.',
            'messenger_phone.regex' => 'شماره پیام‌رسان باید با + شروع شود و بین ۸ تا ۱۵ رقم باشد.',
            'birth_date.before_or_equal' => 'تاریخ تولد نمی‌تواند بعد از امروز باشد.',
            'passport_expiry_date.after_or_equal' => 'تاریخ انقضای پاسپورت باید امروز یا بعد از آن باشد.',
            'deposit_category.in' => 'نوع ودیعه انتخاب‌شده معتبر نیست.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'phone' => 'شماره تماس',
            'messenger_phone' => 'شماره پیام‌رسان',
            'address' => 'آدرس',
            'birth_date' => 'تاریخ تولد',
            'national_code' => 'کد ملی/شناسه',
            'passport_number' => 'شماره پاسپورت',
            'passport_expiry_date' => 'تاریخ انقضای پاسپورت',
            'nationality' => 'ملیت',
            'license_number' => 'شماره گواهینامه',
            'licensed_driver_name' => 'نام راننده دارای گواهینامه',
            'agent_id' => 'کارشناس',
            'submitted_by_name' => 'ثبت‌کننده',
            'notes' => 'توضیحات',
            'driver_note' => 'یادداشت راننده',
            'kardo_required' => 'نیاز به کاردو',
            'payment_on_delivery' => 'پرداخت در محل',
            'deposit_category' => 'نوع ودیعه',
            'deposit' => 'مبلغ/جزئیات ودیعه',
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
