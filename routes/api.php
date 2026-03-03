<?php

use App\Http\Controllers\FlightController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.apikey')->group(function () {
    Route::post('/flights', [FlightController::class, 'store']);
    Route::put('/flights/{flightId}', [FlightController::class, 'update']);
    Route::get('/flights/{flightId}', [FlightController::class, 'show']);
});
