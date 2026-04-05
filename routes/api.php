<?php

use App\Http\Controllers\Api\PublicReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('public/reservations')
    ->middleware('throttle:reservation-public')
    ->group(function () {
        Route::get('/bootstrap', [PublicReservationController::class, 'bootstrap']);
        Route::get('/brands', [PublicReservationController::class, 'brands']);
        Route::get('/models', [PublicReservationController::class, 'models']);
        Route::get('/cars', [PublicReservationController::class, 'cars']);
        Route::post('/quote', [PublicReservationController::class, 'quote']);
        Route::post('/submit', [PublicReservationController::class, 'store']);
        Route::post('/', [PublicReservationController::class, 'store']);
    });
