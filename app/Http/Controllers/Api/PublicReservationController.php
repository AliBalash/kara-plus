<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PublicReservationCarsRequest;
use App\Http\Requests\Api\ReservationQuoteRequest;
use App\Http\Requests\Api\StorePublicReservationRequest;
use App\Services\Reservations\PublicReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicReservationController extends Controller
{
    public function __construct(private readonly PublicReservationService $reservationService)
    {
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'data' => $this->reservationService->bootstrapData(),
        ]);
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'data' => $this->reservationService->brands(),
        ]);
    }

    public function models(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->reservationService->models($validated['brand'] ?? null),
        ]);
    }

    public function cars(PublicReservationCarsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $this->reservationService->cars(
                isset($validated['model_id']) ? (int) $validated['model_id'] : null,
                $validated['brand'] ?? null,
                $validated['pickup_date'] ?? null,
                $validated['return_date'] ?? null
            ),
        ]);
    }

    public function quote(ReservationQuoteRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reservationService->quote($request->validated()),
        ]);
    }

    public function store(StorePublicReservationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reservationService->createReservation($request->validated()),
        ], 201);
    }
}
