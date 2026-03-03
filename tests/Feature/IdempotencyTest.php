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

it('handles concurrent duplicate gracefully via unique constraint', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $route = "PUT /api/flights/{$flightId}";

    // Pre-insert so the SELECT finds it — covers the early-return path
    \App\Models\IdempotentRequest::create([
        'idempotency_key' => 'race-key',
        'route' => $route,
        'response_status' => 204,
        'response_body' => null,
    ]);

    $response = $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'race-key',
    ]));

    $response->assertStatus(204);
    Queue::assertNotPushed(UpdateFlightJob::class);
});

it('catches UniqueConstraintViolation when race passes the SELECT', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())
        ->json('flightId');

    $route = "PUT /api/flights/{$flightId}";

    // Simulate: another process inserted the record between our SELECT and INSERT.
    // We use a DB listener to insert the record right before the controller's INSERT fires.
    $intercepted = false;
    \Illuminate\Support\Facades\DB::listen(function ($query) use ($route, &$intercepted) {
        // After the SELECT (which returns null), insert the record before the controller's INSERT
        if (! $intercepted && str_contains($query->sql, 'select') && str_contains($query->sql, 'idempotent_requests')) {
            $intercepted = true;
            \Illuminate\Support\Facades\DB::table('idempotent_requests')->insert([
                'idempotency_key' => 'race-catch-key',
                'route' => $route,
                'response_status' => 204,
                'response_body' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $response = $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'race-catch-key',
    ]));

    $response->assertStatus(204);
    // The catch block should have prevented the job from being dispatched
    Queue::assertNotPushed(UpdateFlightJob::class);
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
