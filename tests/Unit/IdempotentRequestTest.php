<?php

use App\Models\IdempotentRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores and retrieves an idempotent request', function () {
    IdempotentRequest::create([
        'idempotency_key' => 'key-1',
        'route' => 'PUT /api/flights/abc',
        'response_status' => 204,
        'response_body' => null,
    ]);

    $record = IdempotentRequest::where('idempotency_key', 'key-1')->first();

    expect($record)->not->toBeNull();
    expect($record->response_status)->toBe(204);
    expect($record->response_body)->toBeNull();
});

it('enforces unique constraint on key + route', function () {
    IdempotentRequest::create([
        'idempotency_key' => 'dup-key',
        'route' => 'PUT /api/flights/abc',
        'response_status' => 204,
        'response_body' => null,
    ]);

    IdempotentRequest::create([
        'idempotency_key' => 'dup-key',
        'route' => 'PUT /api/flights/abc',
        'response_status' => 204,
        'response_body' => null,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('allows same key on different routes', function () {
    IdempotentRequest::create([
        'idempotency_key' => 'shared-key',
        'route' => 'PUT /api/flights/aaa',
        'response_status' => 204,
        'response_body' => null,
    ]);

    IdempotentRequest::create([
        'idempotency_key' => 'shared-key',
        'route' => 'PUT /api/flights/bbb',
        'response_status' => 204,
        'response_body' => null,
    ]);

    expect(IdempotentRequest::where('idempotency_key', 'shared-key')->count())->toBe(2);
});

it('casts response_body as array', function () {
    $record = IdempotentRequest::create([
        'idempotency_key' => 'json-key',
        'route' => 'PUT /api/flights/xyz',
        'response_status' => 200,
        'response_body' => ['flightId' => 'abc-123'],
    ]);

    $fresh = $record->fresh();

    expect($fresh->response_body)->toBeArray();
    expect($fresh->response_body['flightId'])->toBe('abc-123');
});
