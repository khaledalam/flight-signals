<?php

use App\Jobs\UpdateFlightJob;
use App\Services\FlightService;
use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('logs error and returns early when flight does not exist', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'flight not found'));

    $job = new UpdateFlightJob('nonexistent-uuid', [], 'idem-key');
    $job->handle(app(FlightService::class));
});

it('processes the update when flight exists', function () {
    $service = app(FlightService::class);
    $flight = $service->createFlight([
        [
            'segments' => [
                ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
            ],
        ],
    ]);

    Log::shouldReceive('info')->atLeast()->times(1);

    $job = new UpdateFlightJob($flight->id, [
        [
            'segments' => [
                ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T07:00:00', 'arrival' => '2026-06-09T11:00:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
            ],
        ],
    ], 'idem-key');

    $job->handle($service);

    $flight->refresh()->load('legs.segments');
    expect($flight->legs[0]->segments[0]->departure->format('H:i'))->toBe('07:00');
});

it('forgets the idempotency key on permanent failure', function () {
    $flightId = 'test-flight-uuid';
    $idempotencyKey = 'fail-key';
    $service = app(IdempotencyService::class);
    $cacheKey = $service->cacheKey($idempotencyKey, UpdateFlightJob::idempotencyScope($flightId));

    Cache::put($cacheKey, ['fingerprint' => 'x', 'response' => ['status' => 204, 'body' => null]]);

    Log::shouldReceive('error')->once()->withArgs(fn ($msg) => str_contains($msg, 'permanently failed'));

    $job = new UpdateFlightJob($flightId, [], $idempotencyKey);
    $job->failed(new \RuntimeException('Something broke'));

    expect(Cache::has($cacheKey))->toBeFalse();
});
