<?php

use App\Models\IdempotentRequest;
use Illuminate\Support\Carbon;

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

// ── flights:purge-idempotency ───────────────────────────────

it('purges old idempotency records with --force', function () {
    // Freeze time so created_at is deterministic
    Carbon::setTestNow(now());

    IdempotentRequest::create([
        'idempotency_key' => 'old-key',
        'route' => 'PUT /api/flights/abc',
        'response_status' => 204,
        'response_body' => null,
    ]);

    // Manually backdate the record
    IdempotentRequest::where('idempotency_key', 'old-key')
        ->update(['created_at' => now()->subHours(48)]);

    $this->artisan('flights:purge-idempotency --hours=24 --force')
        ->assertSuccessful();

    $this->assertDatabaseCount('idempotent_requests', 0);

    Carbon::setTestNow();
});

it('reports nothing to purge when table is clean', function () {
    $this->artisan('flights:purge-idempotency --hours=24 --force')
        ->assertSuccessful();
});

it('asks for confirmation without --force and aborts on no', function () {
    Carbon::setTestNow(now());

    IdempotentRequest::create([
        'idempotency_key' => 'confirm-key',
        'route' => 'PUT /api/flights/abc',
        'response_status' => 204,
        'response_body' => null,
    ]);

    IdempotentRequest::where('idempotency_key', 'confirm-key')
        ->update(['created_at' => now()->subHours(48)]);

    $this->artisan('flights:purge-idempotency --hours=24')
        ->expectsConfirmation('Delete 1 idempotency records older than 24h?', 'no')
        ->assertSuccessful();

    $this->assertDatabaseCount('idempotent_requests', 1);

    Carbon::setTestNow();
});

it('skips recent records', function () {
    IdempotentRequest::create([
        'idempotency_key' => 'fresh-key',
        'route' => 'PUT /api/flights/xyz',
        'response_status' => 204,
        'response_body' => null,
    ]);

    $this->artisan('flights:purge-idempotency --hours=24 --force')
        ->assertSuccessful();

    $this->assertDatabaseCount('idempotent_requests', 1);
});
