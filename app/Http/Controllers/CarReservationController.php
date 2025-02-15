<?php
// app/Http/Controllers/CarReservationController.php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CarReservationController extends Controller
{
    /**
     * Handle car reservation request and send it to the API.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function reserveCar(Request $request)
    {
        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:15',
            'messenger_phone' => 'required|string|max:15',
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'required|string|max:255',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:today|after_or_equal:pickup_date',
            'carId' => 'required|integer|exists:cars,id',
            'total_price' => 'required|numeric|min:0', // اضافه کردن اعتبارسنجی برای total_price
        ]);

        if ($validator->fails()) {
            // ارسال پاسخ خطا
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // ایجاد یک نمونه از Guzzle Client
        // $client = new Client();
        // فراخوانی API خارجی (genderize.io)
        // $response = $client->get("https://api.genderize.io", [
        //     'query' => [
        //         'name' => $request->first_name
        //     ]
        // ]);
        // دریافت داده‌های پاسخ
        // $data = json_decode($response->getBody()->getContents(), true);
        // بررسی وجود جنسیت در پاسخ
        // $gender = isset($data['gender']) ? $data['gender'] : null;

        // اگر اعتبارسنجی موفق بود، داده‌ها ذخیره می‌شوند
        $customer = Customer::updateOrCreate(
            ['email' => $request->email],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'messenger_phone' => $request->messenger_phone,
                'registration_date' => now(),
                'status' => 'active',
            ]
        );

        // ایجاد قرارداد
        $contract = Contract::create([
            'customer_id' => $customer->id,
            'car_id' => $request->carId,
            'pickup_date' => $request->pickup_date, // Field should be 'pickup_date'
            'return_date' => $request->return_date,
            'pickup_location' => $request->pickup_location, // Field should be 'pickup_location'
            'return_location' => $request->return_location, // Field should be 'return_location'
            'total_price' => $request->total_price,
            'current_status' => 'pending', // It should be 'current_status', not 'status'
        ]);

        // برای دیباگ، داده‌ها را در کنسول نمایش بدهید (بدون ارسال به کلاینت)
        Log::info('Reservation Created', [
            'customer' => $customer,
            'contract' => $contract
        ]);

        // اگر نمی‌خواهید هیچ چیزی برگشت داده شود، می‌توانید از کد زیر استفاده کنید:
        return response()->noContent();  // هیچ محتوایی به کلاینت برگشت داده نمی‌شود
    }
}
