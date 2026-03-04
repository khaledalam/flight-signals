<?php

use App\Services\AdminService;
use App\Services\FlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns correct stats', function () {
    $flightService = app(FlightService::class);
    $flightService->createFlight([
        ['segments' => [
            ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
            ['origin' => 'LON', 'destination' => 'JFK', 'departure' => '2026-06-09T11:55:00', 'arrival' => '2026-06-09T14:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '102'],
        ]],
    ]);

    $stats = app(AdminService::class)->getStats();

    expect($stats['flights'])->toBe(1)
        ->and($stats['legs'])->toBe(1)
        ->and($stats['segments'])->toBe(2)
        ->and($stats['idempotency_records'])->toBe(0);
});

it('returns recent flights with relations', function () {
    $flightService = app(FlightService::class);
    $flightService->createFlight([
        ['segments' => [
            ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
        ]],
    ]);

    $flights = app(AdminService::class)->getRecentFlights();

    expect($flights)->toHaveCount(1)
        ->and($flights->first()->legs)->toHaveCount(1)
        ->and($flights->first()->legs->first()->segments)->toHaveCount(1);
});

it('returns job counts', function () {
    $counts = app(AdminService::class)->getJobCounts();

    expect($counts)->toHaveKeys(['pending', 'failed'])
        ->and($counts['pending'])->toBe(0)
        ->and($counts['failed'])->toBe(0);
});

it('returns environment config', function () {
    $env = app(AdminService::class)->getEnv();

    expect($env)->toHaveKeys([
        'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
        'PHP_VERSION', 'LARAVEL_VERSION',
        'DB_CONNECTION', 'QUEUE_CONNECTION',
        'API_RATE_LIMIT',
    ])
        ->and($env['PHP_VERSION'])->toBe(PHP_VERSION);
});
