<?php

use function Tests\sampleLegs;

it('rejects requests without an Api-Key header', function () {
    $this->postJson('/api/flights', sampleLegs())
        ->assertStatus(401)
        ->assertJson(['message' => 'Invalid or missing Api-Key.']);
});

it('rejects requests with an incorrect Api-Key', function () {
    $this->postJson('/api/flights', sampleLegs(), [
        'Api-Key' => 'wrong-key',
        'Accept' => 'application/json',
    ])->assertStatus(401);
});

it('blocks all endpoints without auth', function () {
    $this->getJson('/api/flights/some-id')->assertStatus(401);
    $this->putJson('/api/flights/some-id', [])->assertStatus(401);
    $this->postJson('/api/flights', [])->assertStatus(401);
});
