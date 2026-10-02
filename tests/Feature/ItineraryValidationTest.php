<?php

use App\Jobs\UpdateFlightJob;
use Illuminate\Support\Facades\Queue;

use function Tests\apiHeaders;
use function Tests\sampleLegs;
use function Tests\segment;
use function Tests\updatePayload;

function createFlight(array $legs): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/flights', ['legs' => $legs], apiHeaders());
}

it('rejects a segment departing before the previous segment arrives', function () {
    createFlight([['segments' => [
        segment('BCN', 'LON', '2026-06-09T06:45:00', '2026-06-09T10:55:00'),
        segment('LON', 'JFK', '2026-06-09T06:45:00', '2026-06-09T14:55:00', '102'),
    ]]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.1.departure']);
});

it('rejects a leg departing before the previous leg arrives', function () {
    createFlight([
        ['segments' => [segment('BCN', 'LON', '2026-06-09T06:45:00', '2026-06-09T10:55:00')]],
        ['segments' => [segment('LON', 'BCN', '2026-06-08T08:00:00', '2026-06-08T11:00:00', '102')]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.1.segments.0.departure']);
});

it('rejects a segment whose origin and destination are the same', function () {
    createFlight([['segments' => [segment('BCN', 'BCN', '2026-06-09T06:45:00', '2026-06-09T10:55:00')]]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.0.destination']);
});

it('rejects an arrival that only looks later in wall-clock time', function () {
    createFlight([['segments' => [segment('JFK', 'LHR', '2026-06-25T06:45:00', '2026-06-25T10:55:00')]]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.0.arrival']);
});

it('accepts an arrival that looks earlier in wall-clock time but is later in UTC', function () {
    createFlight([['segments' => [segment('SYD', 'LAX', '2026-06-10T10:00:00', '2026-06-10T06:00:00')]]])
        ->assertStatus(201);
});

it('rejects unknown or malformed airport codes', function (string $code) {
    createFlight([['segments' => [segment($code, 'LHR', '2026-06-09T06:45:00', '2026-06-09T10:55:00')]]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.0.origin']);
})->with(['ZZZ', 'bcn', 'BARCELONA', '']);

it('rejects times that carry an offset or are not local ISO-8601', function (string $time) {
    createFlight([['segments' => [segment('BCN', 'LHR', $time, '2026-06-09T10:55:00')]]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.0.departure']);
})->with(['2026-06-09T06:45:00+02:00', '2026-06-09 06:45', 'tomorrow']);

it('rejects a leg made of repeated identical segments', function () {
    $segments = array_fill(0, 5, segment('BCN', 'LON', '2026-06-09T06:45:00', '2026-06-09T10:55:00'));

    createFlight([['segments' => $segments]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.1.departure']);
});

it('caps the number of segments per leg and legs per flight', function () {
    $segment = segment('BCN', 'LON', '2026-06-09T06:45:00', '2026-06-09T10:55:00');

    createFlight([['segments' => array_fill(0, 9, $segment)]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments']);

    createFlight(array_fill(0, 11, ['segments' => [$segment]]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs']);
});

it('rejects a huge payload quickly instead of timing out', function () {
    $segment = segment('BCN', 'LON', '2026-06-09T06:45:00', '2026-06-09T10:55:00');
    $legs = array_fill(0, 200, ['segments' => array_fill(0, 200, $segment)]);

    $start = microtime(true);
    createFlight($legs)->assertStatus(422);

    expect(microtime(true) - $start)->toBeLessThan(5);
});

it('rejects legs sent as an object instead of a list', function () {
    $this->postJson('/api/flights', ['legs' => ['a' => sampleLegs()['legs'][0]]], apiHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs']);
});

it('rejects an update leg that matches no existing leg instead of dropping it', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $this->putJson("/api/flights/{$flightId}", ['legs' => [['segments' => [
        segment('BCN', 'CDG', '2026-06-09T06:45:00', '2026-06-09T08:55:00'),
    ]]]], apiHeaders(['Idempotency-Key' => 'unmatched']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0']);

    Queue::assertNotPushed(UpdateFlightJob::class);
});

it('rejects an update that would overlap the other legs of the flight', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $payload = updatePayload();
    $payload['legs'][0]['segments'][0]['departure'] = '2026-06-27T06:40:00';
    $payload['legs'][0]['segments'][0]['arrival'] = '2026-06-27T10:50:00';
    $payload['legs'][0]['segments'][1]['departure'] = '2026-06-27T11:55:00';
    $payload['legs'][0]['segments'][1]['arrival'] = '2026-06-27T14:55:00';

    $this->putJson("/api/flights/{$flightId}", $payload, apiHeaders(['Idempotency-Key' => 'overlap']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs']);

    Queue::assertNotPushed(UpdateFlightJob::class);
});

it('rejects an update whose segments are out of sequence', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $payload = updatePayload();
    $payload['legs'][0]['segments'][1]['departure'] = '2026-06-09T06:45:00';

    $this->putJson("/api/flights/{$flightId}", $payload, apiHeaders(['Idempotency-Key' => 'out-of-seq']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['legs.0.segments.1.departure']);
});
