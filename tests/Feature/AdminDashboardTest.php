<?php

use App\Services\FlightService;

it('requires basic auth credentials', function () {
    $this->get('/admin')
        ->assertStatus(401);
});

it('rejects wrong credentials', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('wrong:wrong'),
    ])->get('/admin')
        ->assertStatus(401);
});

it('allows access with correct credentials', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('admin:admin'),
    ])->get('/admin')
        ->assertStatus(200)
        ->assertViewIs('admin');
});

it('displays stats on the dashboard', function () {
    $service = app(FlightService::class);
    $service->createFlight([
        ['segments' => [
            ['origin' => 'BCN', 'destination' => 'LON', 'departure' => '2026-06-09T06:45:00', 'arrival' => '2026-06-09T10:55:00', 'cabinClass' => 'Y', 'airline' => 'UA', 'flightNumber' => '101'],
        ]],
    ]);

    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('admin:admin'),
    ])->get('/admin')
        ->assertStatus(200)
        ->assertSee('1') // flights count
        ->assertSee('BCN');
});
