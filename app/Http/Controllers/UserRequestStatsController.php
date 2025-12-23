<?php

namespace App\Http\Controllers;

use App\Models\PickupDocument;
use App\Models\ReturnDocument;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRequestStatsController extends Controller
{
    public function show(Request $request, int $userId): JsonResponse
    {
        $startDate = $this->startOfDecember();

        $pickupCount = PickupDocument::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $returnCount = ReturnDocument::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        return response()->json([
            'user_id' => $userId,
            'from' => $startDate->toDateString(),
            'pickup_count' => $pickupCount,
            'return_count' => $returnCount,
        ]);
    }

    private function startOfDecember(): Carbon
    {
        $now = now();
        $start = Carbon::create($now->year, 12, 1)->startOfDay();

        if ($now->lessThan($start)) {
            $start = $start->subYear();
        }

        return $start;
    }
}
