<?php

use App\Jobs\UpdateFlightJob;
use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

use function Tests\apiHeaders;
use function Tests\sampleLegs;
use function Tests\updatePayload;

it('returns the same response on idempotency replay without re-dispatching', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $headers = apiHeaders(['Idempotency-Key' => 'unique-key-abc']);

    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);
    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, 1);
});

it('returns 409 while another request holds the lock for the same key', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $cacheKey = app(IdempotencyService::class)
        ->cacheKey('race-key', UpdateFlightJob::idempotencyScope($flightId));

    $lock = Cache::lock("{$cacheKey}:lock", 10);
    expect($lock->get())->toBeTrue();

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'race-key',
    ]))->assertStatus(409);

    Queue::assertNotPushed(UpdateFlightJob::class);

    $lock->release();

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'race-key',
    ]))->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, 1);
});

it('rejects reusing a key with a different payload', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $headers = apiHeaders(['Idempotency-Key' => 'reused-key']);

    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);

    $changed = updatePayload();
    $changed['legs'][0]['segments'][0]['departure'] = '2026-06-09T06:30:00';

    $this->putJson("/api/flights/{$flightId}", $changed, $headers)
        ->assertStatus(422)
        ->assertJson(['message' => 'This Idempotency-Key was already used with a different payload.']);

    Queue::assertPushed(UpdateFlightJob::class, 1);
});

it('scopes keys per flight', function () {
    Queue::fake();

    $first = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');
    $second = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $headers = apiHeaders(['Idempotency-Key' => 'shared-key']);

    $this->putJson("/api/flights/{$first}", updatePayload(), $headers)->assertStatus(204);
    $this->putJson("/api/flights/{$second}", updatePayload(), $headers)->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, 2);
});

it('stores the response in the cache, not the database', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'track-me',
    ]))->assertStatus(204);

    $cacheKey = app(IdempotencyService::class)
        ->cacheKey('track-me', UpdateFlightJob::idempotencyScope($flightId));

    expect(Cache::get($cacheKey)['response'])->toBe(['status' => 204, 'body' => null]);
});

it('allows a retry with the same key after the job permanently fails', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $headers = apiHeaders(['Idempotency-Key' => 'retry-key']);

    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);

    (new UpdateFlightJob($flightId, updatePayload()['legs'], 'retry-key'))
        ->failed(new RuntimeException('Something broke'));

    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);

    Queue::assertPushed(UpdateFlightJob::class, 2);
});

it('rejects an overly long Idempotency-Key', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => str_repeat('k', 256),
    ]))->assertStatus(422);
});
