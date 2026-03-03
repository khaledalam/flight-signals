<?php

use App\Services\FlightService;

beforeEach(function () {
    $this->service = new FlightService;
});

it('builds a single-segment route signature', function () {
    $segments = [
        ['origin' => 'BCN', 'destination' => 'LON'],
    ];

    expect($this->service->buildRouteSignature($segments))->toBe('BCN>LON');
});

it('builds a multi-segment route signature', function () {
    $segments = [
        ['origin' => 'BCN', 'destination' => 'LON'],
        ['origin' => 'LON', 'destination' => 'JFK'],
    ];

    expect($this->service->buildRouteSignature($segments))->toBe('BCN>LON|LON>JFK');
});

it('preserves segment order in the signature', function () {
    $forward = [
        ['origin' => 'BCN', 'destination' => 'LON'],
        ['origin' => 'LON', 'destination' => 'JFK'],
    ];

    $reverse = [
        ['origin' => 'JFK', 'destination' => 'LON'],
        ['origin' => 'LON', 'destination' => 'BCN'],
    ];

    $sig1 = $this->service->buildRouteSignature($forward);
    $sig2 = $this->service->buildRouteSignature($reverse);

    expect($sig1)->not->toBe($sig2);
});

it('returns empty string for no segments', function () {
    expect($this->service->buildRouteSignature([]))->toBe('');
});

it('handles three-segment routes', function () {
    $segments = [
        ['origin' => 'BCN', 'destination' => 'LON'],
        ['origin' => 'LON', 'destination' => 'FRA'],
        ['origin' => 'FRA', 'destination' => 'NRT'],
    ];

    expect($this->service->buildRouteSignature($segments))
        ->toBe('BCN>LON|LON>FRA|FRA>NRT');
});
