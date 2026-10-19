<?php

namespace App\Services\Reservations;

use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationPriceChanged extends HttpResponseException
{
    public function __construct(array $quote)
    {
        parent::__construct(response()->json([
            'code' => 'price_changed',
            'message' => 'The reservation price changed. Review and accept the updated quote before submitting.',
            'data' => ['quote' => $quote],
        ], 409));
    }
}
