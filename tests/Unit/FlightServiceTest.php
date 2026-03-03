<?php

use App\Models\Flight;
use App\Services\FlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new FlightService;
});

it('creates a flight with correct leg and segment counts', function () {
    $flight = $this->service->createFlight([
        [
            'segments' => [
                [
                    'origin' => 'BCN', 'destination' => 'LON',
                    'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00',
                    'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101',
                ],
            ],
        ],
        [
            'segments' => [
                [
                    'origin' => 'JFK', 'destination' => 'LON',
                    'departure' => '2026-06-25T06:45:00', 'arrival' => '2026-06-25T10:55:00',
                    'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '201',
                ],
                [
                    'origin' => 'LON', 'destination' => 'BCN',
                    'departure' => '2026-06-25T11:55:00', 'arrival' => '2026-06-25T13:55:00',
                    'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '202',
                ],
            ],
        ],
    ]);

    expect($flight)->toBeInstanceOf(Flight::class);
    expect($flight->legs)->toHaveCount(2);
    expect($flight->legs[0]->segments)->toHaveCount(1);
    expect($flight->legs[1]->segments)->toHaveCount(2);
});

it('assigns sequential positions to legs and segments', function () {
    $flight = $this->service->createFlight([
        [
            'segments' => [
                [
                    'origin' => 'A', 'destination' => 'B',
                    'departure' => '2026-07-01T06:00:00', 'arrival' => '2026-07-01T10:00:00',
                    'cabinClass' => 'Y', 'airline' => 'XX', 'flightNumber' => '1',
                ],
                [
                    'origin' => 'B', 'destination' => 'C',
                    'departure' => '2026-07-01T11:00:00', 'arrival' => '2026-07-01T15:00:00',
                    'cabinClass' => 'Y', 'airline' => 'XX', 'flightNumber' => '2',
                ],
            ],
        ],
    ]);

    expect($flight->legs[0]->position)->toBe(0);
    expect($flight->legs[0]->segments[0]->position)->toBe(0);
    expect($flight->legs[0]->segments[1]->position)->toBe(1);
});

it('maps camelCase input to snake_case columns', function () {
    $flight = $this->service->createFlight([
        [
            'segments' => [
                [
                    'origin' => 'BCN', 'destination' => 'LON',
                    'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00',
                    'cabinClass' => 'J', 'airline' => 'BA', 'flightNumber' => '777',
                ],
            ],
        ],
    ]);

    $segment = $flight->legs[0]->segments[0];

    expect($segment->cabin_class)->toBe('J');
    expect($segment->flight_number)->toBe('777');
    expect($segment->airline)->toBe('BA');
});

it('updates only the matched leg and leaves others untouched', function () {
    $flight = $this->service->createFlight([
        [
            'segments' => [
                ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
                ['origin' => 'LON', 'destination' => 'JFK', 'departure' => '2026-06-09T11:55:00', 'arrival' => '2026-06-09T14:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '102'],
            ],
        ],
        [
            'segments' => [
                ['origin' => 'JFK', 'destination' => 'LON', 'departure' => '2026-06-25T06:45:00', 'arrival' => '2026-06-25T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '201'],
            ],
        ],
    ]);

    // Update only the first leg with new times
    $this->service->updateFlight($flight, [
        [
            'segments' => [
                ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T07:00:00', 'arrival' => '2026-06-09T11:00:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
                ['origin' => 'LON', 'destination' => 'JFK', 'departure' => '2026-06-09T12:00:00', 'arrival' => '2026-06-09T15:00:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '102'],
            ],
        ],
    ]);

    $flight->refresh()->load('legs.segments');

    // First leg updated
    expect($flight->legs[0]->segments[0]->departure->format('H:i'))->toBe('07:00');

    // Second leg untouched
    expect($flight->legs[1]->segments[0]->departure->format('H:i'))->toBe('06:45');
});

it('skips legs with no route match without error', function () {
    $flight = $this->service->createFlight([
        [
            'segments' => [
                ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
            ],
        ],
    ]);

    // Try to update a non-existent route — should not throw
    $this->service->updateFlight($flight, [
        [
            'segments' => [
                ['origin' => 'SFO', 'destination' => 'NRT', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '999'],
            ],
        ],
    ]);

    $flight->refresh()->load('legs.segments');

    // Original data unchanged
    expect($flight->legs[0]->segments[0]->origin)->toBe('BCN');
    expect($flight->legs[0]->segments[0]->flight_number)->toBe('101');
});
