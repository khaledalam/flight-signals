<?php

use App\Jobs\UpdateFlightJob;
use Illuminate\Support\Facades\Queue;

use function Tests\apiHeaders;
use function Tests\sampleLegs;
use function Tests\updatePayload;

it('returns the same response on idempotency replay without re-dispatching', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $headers = apiHeaders(['Idempotency-Key' => 'unique-key-abc']);

    $first = $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers);
    $first->assertStatus(204);

    $second = $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers);
    $second->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, 1);
    $this->assertDatabaseCount('idempotent_requests', 1);
});

it('stores the idempotency record with correct data', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'track-me',
    ]));

    $this->assertDatabaseHas('idempotent_requests', [
        'idempotency_key' => 'track-me',
        'response_status' => 204,
    ]);
});
