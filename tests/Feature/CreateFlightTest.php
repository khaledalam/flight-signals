<?php

use function Tests\apiHeaders;
use function Tests\sampleLegs;

it('creates a flight and returns its uuid', function () {
    $response = $this->postJson('/api/flights', sampleLegs(), apiHeaders());

    $response->assertStatus(201)
        ->assertJsonStructure(['flightId']);

    $this->assertDatabaseCount('flights', 1);
    $this->assertDatabaseCount('legs', 2);
    $this->assertDatabaseCount('segments', 4);
});

it('persists correct segment data', function () {
    $response = $this->postJson('/api/flights', sampleLegs(), apiHeaders());
    $flightId = $response->json('flightId');

    $flight = \App\Models\Flight::with('legs.segments')->find($flightId);

    expect($flight->legs)->toHaveCount(2);
    expect($flight->legs[0]->segments)->toHaveCount(2);
    expect($flight->legs[0]->segments[0]->origin)->toBe('BCN');
    expect($flight->legs[0]->segments[0]->destination)->toBe('LON');
    expect($flight->legs[1]->segments[0]->origin)->toBe('JFK');
});

it('rejects a request with no legs', function () {
    $this->postJson('/api/flights', [], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs']);
});

it('rejects a request with empty segments array', function () {
    $this->postJson('/api/flights', [
        'legs' => [['segments' => []]],
    ], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments']);
});

it('rejects a segment missing required fields', function () {
    $this->postJson('/api/flights', [
        'legs' => [[
            'segments' => [['origin' => 'BCN']],
        ]],
    ], apiHeaders())
        ->assertStatus(422);
});

it('rejects arrival before departure', function () {
    $this->postJson('/api/flights', [
        'legs' => [[
            'segments' => [[
                'origin' => 'BCN',
                'destination' => 'LON',
                'departure' => '2026-06-09T10:00:00',
                'arrival' => '2026-06-09T08:00:00',
                'cabinClass' => 'Y',
                'airline' => 'UA',
                'flightNumber' => '101',
            ]],
        ]],
    ], apiHeaders())
        ->assertStatus(422);
});

it('rejects segment departing before previous segment arrives', function () {
    $this->postJson('/api/flights', [
        'legs' => [[
            'segments' => [
                [
                    'origin' => 'BCN',
                    'destination' => 'LON',
                    'departure' => '2026-06-09T06:00:00',
                    'arrival' => '2026-06-09T10:00:00',
                    'cabinClass' => 'Y',
                    'airline' => 'UA',
                    'flightNumber' => '101',
                ],
                [
                    'origin' => 'LON',
                    'destination' => 'JFK',
                    'departure' => '2026-06-09T09:00:00', // before previous arrival
                    'arrival' => '2026-06-09T14:00:00',
                    'cabinClass' => 'Y',
                    'airline' => 'UA',
                    'flightNumber' => '102',
                ],
            ],
        ]],
    ], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.1.departure']);
});

it('rejects more than 10 legs', function () {
    $legs = [];
    for ($i = 0; $i < 11; $i++) {
        $legs[] = [
            'segments' => [[
                'origin' => 'BCN',
                'destination' => 'LON',
                'departure' => '2026-06-09T06:00:00',
                'arrival' => '2026-06-09T10:00:00',
                'cabinClass' => 'Y',
                'airline' => 'UA',
                'flightNumber' => '101',
            ]],
        ];
    }

    $this->postJson('/api/flights', ['legs' => $legs], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs']);
});

it('rejects more than 20 segments per leg', function () {
    $segments = [];
    for ($i = 0; $i < 21; $i++) {
        $segments[] = [
            'origin' => 'BCN',
            'destination' => 'LON',
            'departure' => '2026-07-0'.str_pad($i + 1, 2, '0', STR_PAD_LEFT).'T06:00:00',
            'arrival' => '2026-07-0'.str_pad($i + 1, 2, '0', STR_PAD_LEFT).'T10:00:00',
            'cabinClass' => 'Y',
            'airline' => 'UA',
            'flightNumber' => (string) (100 + $i),
        ];
    }

    $this->postJson('/api/flights', [
        'legs' => [['segments' => $segments]],
    ], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments']);
});
