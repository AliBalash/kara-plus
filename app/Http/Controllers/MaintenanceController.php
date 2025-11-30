<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    public function normalizeCustomerPhones(): JsonResponse
    {
        $normalized = 0;
        $unchanged = 0;
        $skipped = 0;

        Customer::chunk(200, function ($customers) use (&$normalized, &$unchanged, &$skipped) {
            foreach ($customers as $customer) {
                $normalizedPhone = PhoneNumber::normalize($customer->phone);
                $normalizedMessenger = PhoneNumber::normalize($customer->messenger_phone);

                if ($normalizedPhone === null && $normalizedMessenger === null) {
                    $skipped++;
                    continue;
                }

                $dirty = false;

                if ($normalizedPhone !== null && $normalizedPhone !== $customer->phone) {
                    $customer->phone = $normalizedPhone;
                    $dirty = true;
                }

                if ($normalizedMessenger !== null && $normalizedMessenger !== $customer->messenger_phone) {
                    $customer->messenger_phone = $normalizedMessenger;
                    $dirty = true;
                }

                if ($dirty) {
                    $customer->save();
                    $normalized++;
                } else {
                    $unchanged++;
                }
            }
        });

        return response()->json([
            'normalized' => $normalized,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
        ]);
    }
}
