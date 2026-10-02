<?php

namespace Tests;

function segment(string $origin, string $destination, string $departure, string $arrival, string $flightNumber = '101'): array
{
    return [
        'origin' => $origin,
        'destination' => $destination,
        'departure' => $departure,
        'arrival' => $arrival,
        'cabinClass' => 'Y',
        'airline' => 'UA',
        'flightNumber' => $flightNumber,
    ];
}

function apiHeaders(array $extra = []): array
{
    return array_merge([
        'Api-Key' => config('services.api.key'),
        'Accept' => 'application/json',
    ], $extra);
}

function sampleLegs(): array
{
    return [
        'legs' => [
            [
                'segments' => [
                    [
                        'origin' => 'BCN',
                        'destination' => 'LON',
                        'departure' => '2026-06-09T06:45:00',
                        'arrival' => '2026-06-09T10:55:00',
                        'cabinClass' => 'Y',
                        'airline' => 'UA',
                        'flightNumber' => '101',
                    ],
                    [
                        'origin' => 'LON',
                        'destination' => 'JFK',
                        'departure' => '2026-06-09T11:55:00',
                        'arrival' => '2026-06-09T14:55:00',
                        'cabinClass' => 'Y',
                        'airline' => 'UA',
                        'flightNumber' => '102',
                    ],
                ],
            ],
            [
                'segments' => [
                    [
                        'origin' => 'JFK',
                        'destination' => 'LON',
                        'departure' => '2026-06-25T18:45:00',
                        'arrival' => '2026-06-26T06:55:00',
                        'cabinClass' => 'Y',
                        'airline' => 'UA',
                        'flightNumber' => '101',
                    ],
                    [
                        'origin' => 'LON',
                        'destination' => 'BCN',
                        'departure' => '2026-06-26T08:55:00',
                        'arrival' => '2026-06-26T11:55:00',
                        'cabinClass' => 'Y',
                        'airline' => 'UA',
                        'flightNumber' => '102',
                    ],
                ],
            ],
        ],
    ];
}

function updatePayload(): array
{
    return [
        'legs' => [[
            'segments' => [
                [
                    'origin' => 'BCN',
                    'destination' => 'LON',
                    'departure' => '2026-06-09T06:40:00',
                    'arrival' => '2026-06-09T10:50:00',
                    'cabinClass' => 'Y',
                    'airline' => 'UA',
                    'flightNumber' => '101',
                ],
                [
                    'origin' => 'LON',
                    'destination' => 'JFK',
                    'departure' => '2026-06-09T11:55:00',
                    'arrival' => '2026-06-09T14:55:00',
                    'cabinClass' => 'Y',
                    'airline' => 'UA',
                    'flightNumber' => '102',
                ],
            ],
        ]],
    ];
}
