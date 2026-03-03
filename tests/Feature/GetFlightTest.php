<?php

use function Tests\apiHeaders;
use function Tests\sampleLegs;

it('returns all legs and segments for a flight', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $response = $this->getJson("/api/flights/{$flightId}", apiHeaders());

    $response->assertOk()
        ->assertJsonCount(2, 'legs')
        ->assertJsonCount(2, 'legs.0.segments')
        ->assertJsonCount(2, 'legs.1.segments')
        ->assertJsonPath('legs.0.segments.0.origin', 'BCN')
        ->assertJsonPath('legs.0.segments.0.destination', 'LON')
        ->assertJsonPath('legs.0.segments.0.cabinClass', 'Y')
        ->assertJsonPath('legs.0.segments.0.flightNumber', '101')
        ->assertJsonPath('legs.1.segments.0.origin', 'JFK');
});

it('returns 404 for a non-existent flight', function () {
    $this->getJson('/api/flights/nonexistent-uuid', apiHeaders())
        ->assertStatus(404)
        ->assertJson(['message' => 'Flight not found.']);
});
