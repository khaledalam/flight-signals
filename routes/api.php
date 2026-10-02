<?php

use App\Http\Controllers\FlightController;
use Illuminate\Support\Facades\Route;

$flightNotFound = fn () => response()->json(['message' => 'Flight not found.'], 404);

Route::middleware('auth.apikey')->group(function () use ($flightNotFound) {
    Route::post('/flights', [FlightController::class, 'store']);
    Route::put('/flights/{flight}', [FlightController::class, 'update'])
        ->whereUuid('flight')
        ->missing($flightNotFound);
    Route::get('/flights/{flight}', [FlightController::class, 'show'])
        ->whereUuid('flight')
        ->missing($flightNotFound);
});
