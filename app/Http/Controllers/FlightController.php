<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Jobs\UpdateFlightJob;
use App\Models\Flight;
use App\Services\FlightService;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FlightController
{
    public function __construct(
        private readonly FlightService $flightService,
        private readonly IdempotencyService $idempotency,
    ) {}

    public function store(CreateFlightRequest $request): JsonResponse
    {
        $flight = $this->flightService->createFlight($request->validated()['legs']);

        return response()->json(['flightId' => $flight->id], 201);
    }

    public function update(UpdateFlightRequest $request, Flight $flight): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 422);
        }

        if (strlen($idempotencyKey) > 255) {
            return response()->json(['message' => 'Idempotency-Key header must not exceed 255 characters.'], 422);
        }

        $legs = $request->validated()['legs'];

        $response = $this->idempotency->execute(
            $idempotencyKey,
            UpdateFlightJob::idempotencyScope($flight->id),
            hash('sha256', json_encode($legs)),
            function () use ($flight, $legs, $idempotencyKey) {
                UpdateFlightJob::dispatch($flight->id, $legs, $idempotencyKey);

                Log::info('Update flight job dispatched', [
                    'flight_id' => $flight->id,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return ['status' => 204, 'body' => null];
            },
        );

        return response()->json($response['body'], $response['status']);
    }

    public function show(Flight $flight): JsonResponse
    {
        $legs = $flight->load('legs.segments')->legs->map(fn ($leg) => [
            'segments' => $leg->segments->map->toPayload()->values()->toArray(),
        ])->values()->toArray();

        return response()->json(['legs' => $legs]);
    }
}
