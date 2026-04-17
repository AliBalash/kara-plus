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

    public function messages(): array
    {
        return [
            'integer' => ':attribute باید عدد صحیح باشد.',
            'exists' => ':attribute انتخاب‌شده معتبر نیست.',
            'string' => ':attribute باید متن باشد.',
            'max' => ':attribute از حد مجاز بیشتر است.',
            'date' => 'فرمت :attribute معتبر نیست.',
            'required_with' => 'تکمیل :attribute الزامی است.',
            'after' => ':attribute باید بعد از تاریخ تحویل باشد.',
        ];
    }

    public function attributes(): array
    {
        return [
            'model_id' => 'مدل خودرو',
            'brand' => 'برند',
            'pickup_date' => 'تاریخ تحویل',
            'return_date' => 'تاریخ بازگشت',
        ];
    }
}
