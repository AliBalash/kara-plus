<?php
// app/Http/Controllers/CarReservationController.php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
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
         ]);
     
         if ($validator->fails()) {
             // ارسال پاسخ خطا
             return response()->json([
                 'success' => false,
                 'errors' => $validator->errors(),
             ], 422);
         }
     
         // اگر اعتبارسنجی موفق بود، داده‌ها ذخیره می‌شوند
         $customer = Customer::updateOrCreate(
             ['email' => $request->email],
             [
                 'first_name' => $request->first_name,
                 'last_name' => $request->last_name,
                 'phone' => $request->phone,
                 'registration_date' => now(),
                 'status' => 'active',
             ]
         );
     
         $contract = Contract::create([
             'customer_id' => $customer->id,
             'car_id' => $request->carId,
             'start_date' => $request->pickup_date,
             'end_date' => $request->return_date,
             'total_price' => $request->total_price,
             'status' => 'pending',
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
