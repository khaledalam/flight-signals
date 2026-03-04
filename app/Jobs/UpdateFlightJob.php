<?php

namespace App\Jobs;

use App\Models\Flight;
use App\Models\IdempotentRequest;
use App\Services\FlightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateFlightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 30, 60];

    public function __construct(
        public readonly string $flightId,
        public readonly array $legsData,
        public readonly string $idempotencyKey,
    ) {}

    public function handle(FlightService $service): void
    {
        $flight = Flight::find($this->flightId);

        if (! $flight) {
            Log::error('UpdateFlightJob: flight not found', ['flight_id' => $this->flightId]);

            return;
        }

        Log::info('UpdateFlightJob: processing', [
            'flight_id' => $this->flightId,
            'idempotency_key' => $this->idempotencyKey,
        ]);

        $service->updateFlight($flight, $this->legsData);

        Log::info('UpdateFlightJob: completed', ['flight_id' => $this->flightId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateFlightJob: permanently failed', [
            'flight_id' => $this->flightId,
            'idempotency_key' => $this->idempotencyKey,
            'error' => $exception->getMessage(),
        ]);

        IdempotentRequest::where('idempotency_key', $this->idempotencyKey)
            ->where('route', "PUT /api/flights/{$this->flightId}")
            ->delete();
    }
}
