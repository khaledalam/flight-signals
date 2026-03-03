<?php

use App\Jobs\UpdateFlightJob;
use Illuminate\Support\Facades\Queue;

use function Tests\apiHeaders;
use function Tests\sampleLegs;
use function Tests\updatePayload;

it('dispatches the update job and returns 204', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'idem-1',
    ]))->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, fn ($job) => $job->flightId === $flightId);
});

it('requires the Idempotency-Key header', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders())
        ->assertStatus(422)
        ->assertJson(['message' => 'Idempotency-Key header is required.']);
});

it('returns 404 for a non-existent flight', function () {
    $this->putJson('/api/flights/nonexistent-uuid', updatePayload(), apiHeaders([
        'Idempotency-Key' => 'idem-nope',
    ]))->assertStatus(404);
});

it('rejects invalid segment data on update', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", [
        'legs' => [['segments' => [['origin' => 'BCN']]]],
    ], apiHeaders(['Idempotency-Key' => 'idem-bad']))
        ->assertStatus(422);
});

it('actually updates segments when queue processes synchronously', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'sync-key',
    ]))->assertStatus(204);

    $get = $this->getJson("/api/flights/{$flightId}", apiHeaders());

    // First leg's first segment should have the updated departure
    $get->assertJsonPath('legs.0.segments.0.departure', '2026-06-09T06:40:00');
    $get->assertJsonPath('legs.0.segments.0.arrival', '2026-06-09T10:50:00');

    // Second leg should remain unchanged
    $get->assertJsonPath('legs.1.segments.0.origin', 'JFK');
    $get->assertJsonPath('legs.1.segments.0.departure', '2026-06-25T06:45:00');
});
