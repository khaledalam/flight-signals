<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Jobs\UpdateFlightJob;
use App\Models\Flight;
use App\Models\IdempotentRequest;
use App\Services\FlightService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FlightController
{
    public function __construct(
        private readonly FlightService $flightService,
    ) {}

    public function store(CreateFlightRequest $request): JsonResponse
    {
        $flight = $this->flightService->createFlight($request->validated()['legs']);

        return response()->json(['flightId' => $flight->id], 201);
    }

    public function update(UpdateFlightRequest $request, string $flightId): JsonResponse
    {
        $flight = Flight::find($flightId);

        if (! $flight) {
            return response()->json(['message' => 'Flight not found.'], 404);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 422);
        }

        $route = "PUT /api/flights/{$flightId}";

        $existing = IdempotentRequest::where('idempotency_key', $idempotencyKey)
            ->where('route', $route)
            ->first();

        if ($existing) {
            Log::info('Idempotency hit', ['key' => $idempotencyKey, 'route' => $route]);

            return response()->json($existing->response_body, $existing->response_status);
        }

        // Use insert-or-ignore to handle concurrent submissions safely.
        // The unique(idempotency_key, route) constraint guarantees exactly-once.
        try {
            IdempotentRequest::create([
                'idempotency_key' => $idempotencyKey,
                'route' => $route,
                'response_status' => 204,
                'response_body' => null,
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::info('Idempotency race resolved', ['key' => $idempotencyKey]);

            return response()->json(null, 204);
        }

        UpdateFlightJob::dispatch($flightId, $request->validated()['legs'], $idempotencyKey);

        Log::info('Update flight job dispatched', [
            'flight_id' => $flightId,
            'idempotency_key' => $idempotencyKey,
        ]);

        return response()->json(null, 204);
    }

    public function show(string $flightId): JsonResponse
    {
        $flight = Flight::with('legs.segments')->find($flightId);

        if (! $flight) {
            return response()->json(['message' => 'Flight not found.'], 404);
        }

        $legs = $flight->legs->map(fn ($leg) => [
            'segments' => $leg->segments->map(fn ($segment) => [
                'origin' => $segment->origin,
                'destination' => $segment->destination,
                'departure' => $segment->departure->format('Y-m-d\TH:i:s'),
                'arrival' => $segment->arrival->format('Y-m-d\TH:i:s'),
                'cabinClass' => $segment->cabin_class,
                'airline' => $segment->airline,
                'flightNumber' => $segment->flight_number,
            ])->values()->toArray(),
        ])->values()->toArray();

        return response()->json(['legs' => $legs]);
    }
}
