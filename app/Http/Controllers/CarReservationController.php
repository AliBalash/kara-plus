<?php
// app/Http/Controllers/CarReservationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        return response()->json($request->all());

        
        // اعتبارسنجی فرم
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:15',
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'required|string|max:255',
            'pickup_date' => 'required|date',
            'return_date' => 'required|date',
            'car_id' => 'required|integer|exists:cars,id', // اعتبارسنجی ID ماشین
        ]);

        // ارسال درخواست به API
        $response = Http::post('YOUR_API_URL', [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'pickup_location' => $request->input('pickup_location'),
            'return_location' => $request->input('return_location'),
            'pickup_date' => $request->input('pickup_date'),
            'return_date' => $request->input('return_date'),
            'car_id' => $request->input('car_id'),
        ]);

        // بررسی نتیجه درخواست به API
        if ($response->successful()) {
            // در صورت موفقیت، کاربر را به صفحه تایید هدایت می‌کنیم
            return redirect()->route('thank.you')->with('success', 'Your car reservation has been successfully processed!');
        } else {
            // در صورت بروز خطا، پیغام خطا نمایش داده می‌شود
            return back()->withErrors(['error' => 'There was an error processing your reservation. Please try again.']);
        }
    }
}

