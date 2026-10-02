<?php

use function Tests\apiHeaders;
use function Tests\sampleLegs;

// ── flights:stats ───────────────────────────────────────────

it('displays database statistics', function () {
    $this->postJson('/api/flights', sampleLegs(), apiHeaders());

    $this->artisan('flights:stats')
        ->assertSuccessful();
});

it('shows zero state gracefully', function () {
    $this->artisan('flights:stats')
        ->assertSuccessful();
});

// ── flights:inspect ─────────────────────────────────────────

it('displays flight details with legs and segments', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->artisan("flights:inspect {$flightId}")
        ->assertSuccessful();
});

it('fails for a non-existent flight', function () {
    $this->artisan('flights:inspect fake-uuid')
        ->assertFailed();
});
