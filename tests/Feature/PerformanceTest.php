<?php

use Illuminate\Support\Facades\Queue;

use function Tests\apiHeaders;
use function Tests\sampleLegs;
use function Tests\updatePayload;

// Latency thresholds (milliseconds) — in-process test timings,
// not real HTTP latency, but they catch regressions in query count and logic.
const CREATE_THRESHOLD_MS = 200;
const GET_THRESHOLD_MS = 100;
const UPDATE_THRESHOLD_MS = 200;

// Warmup: first request in a test pays for DB migration overhead.
// A throwaway request inside beforeEach absorbs that cost.
beforeEach(function () {
    $this->postJson('/api/flights', sampleLegs(), apiHeaders());
});

it('creates a flight within the latency budget', function () {
    $start = microtime(true);
    $this->postJson('/api/flights', sampleLegs(), apiHeaders())->assertStatus(201);
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeLessThan(CREATE_THRESHOLD_MS,
        "Create flight took {$elapsed}ms, exceeds ".CREATE_THRESHOLD_MS.'ms budget'
    );
});

it('retrieves a flight within the latency budget', function () {
    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $start = microtime(true);
    $this->getJson("/api/flights/{$flightId}", apiHeaders())->assertOk();
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeLessThan(GET_THRESHOLD_MS,
        "Get flight took {$elapsed}ms, exceeds ".GET_THRESHOLD_MS.'ms budget'
    );
});

it('accepts an update within the latency budget', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');

    $start = microtime(true);
    $this->putJson("/api/flights/{$flightId}", updatePayload(), apiHeaders([
        'Idempotency-Key' => 'perf-update-1',
    ]))->assertStatus(204);
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeLessThan(UPDATE_THRESHOLD_MS,
        "Update flight took {$elapsed}ms, exceeds ".UPDATE_THRESHOLD_MS.'ms budget'
    );
});

it('returns idempotent replay faster than the original request', function () {
    Queue::fake();

    $flightId = $this->postJson('/api/flights', sampleLegs(), apiHeaders())->json('flightId');
    $headers = apiHeaders(['Idempotency-Key' => 'perf-idem-1']);

    $start1 = microtime(true);
    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);
    $first = (microtime(true) - $start1) * 1000;

    $start2 = microtime(true);
    $this->putJson("/api/flights/{$flightId}", updatePayload(), $headers)->assertStatus(204);
    $replay = (microtime(true) - $start2) * 1000;

    expect($replay)->toBeLessThan($first,
        "Replay ({$replay}ms) should be faster than first request ({$first}ms)"
    );
});

it('handles 50 sequential creates without degradation', function () {
    $timings = [];

    for ($i = 0; $i < 50; $i++) {
        $start = microtime(true);
        $this->postJson('/api/flights', sampleLegs(), apiHeaders())->assertStatus(201);
        $timings[] = (microtime(true) - $start) * 1000;
    }

    $this->assertDatabaseCount('flights', 51); // 50 + 1 warmup

    $avg = array_sum($timings) / count($timings);
    sort($timings);
    $p95 = $timings[(int) floor(count($timings) * 0.95)];

    expect($avg)->toBeLessThan(CREATE_THRESHOLD_MS,
        "Average create time ({$avg}ms) exceeds budget"
    );
    expect($p95)->toBeLessThan(CREATE_THRESHOLD_MS * 1.5,
        "P95 create time ({$p95}ms) exceeds 1.5x budget"
    );
});

it('retrieves a flight with many legs efficiently', function () {
    $legs = [];
    $cities = ['BCN', 'LON', 'JFK', 'LAX', 'CDG', 'FRA', 'NRT', 'SIN', 'DXB', 'SYD', 'HKG'];
    for ($i = 0; $i < 10; $i++) {
        $segments = [];
        for ($j = 0; $j < 3; $j++) {
            $segments[] = [
                'origin' => $cities[$j],
                'destination' => $cities[$j + 1],
                'departure' => '2026-07-0'.($j + 1).'T06:00:00',
                'arrival' => '2026-07-0'.($j + 1).'T10:00:00',
                'cabinClass' => 'Y',
                'airline' => 'UA',
                'flightNumber' => (string) (100 + $i * 10 + $j),
            ];
        }
        $legs[] = ['segments' => $segments];
    }

    $flightId = $this->postJson('/api/flights', ['legs' => $legs], apiHeaders())
        ->assertStatus(201)
        ->json('flightId');

    $start = microtime(true);
    $response = $this->getJson("/api/flights/{$flightId}", apiHeaders());
    $elapsed = (microtime(true) - $start) * 1000;

    $response->assertOk()
        ->assertJsonCount(10, 'legs');

    expect($elapsed)->toBeLessThan(GET_THRESHOLD_MS * 2,
        "Get flight with 10 legs took {$elapsed}ms"
    );
});
