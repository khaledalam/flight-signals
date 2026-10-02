<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Leg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlightService
{
    public function createFlight(array $legsData): Flight
    {
        return DB::transaction(function () use ($legsData) {
            $flight = Flight::create();

            foreach ($legsData as $legIndex => $legData) {
                $leg = $flight->legs()->create(['position' => $legIndex]);

                foreach ($legData['segments'] as $segIndex => $segmentData) {
                    $leg->segments()->create([
                        'position' => $segIndex,
                        'origin' => $segmentData['origin'],
                        'destination' => $segmentData['destination'],
                        'departure' => $segmentData['departure'],
                        'arrival' => $segmentData['arrival'],
                        'cabin_class' => $segmentData['cabinClass'],
                        'airline' => $segmentData['airline'],
                        'flight_number' => $segmentData['flightNumber'],
                    ]);
                }
            }

            Log::info('Flight created', ['flight_id' => $flight->id]);

            return $flight;
        });
    }

    public function updateFlight(Flight $flight, array $legsData): void
    {
        DB::transaction(function () use ($flight, $legsData) {
            Flight::whereKey($flight->id)->lockForUpdate()->first();

            $existingLegs = $flight->legs()->with('segments')->get();

            foreach ($this->matchLegs($existingLegs, $legsData) as $index => $matched) {
                $incomingLeg = $legsData[$index];

                if (! $matched) {
                    Log::warning('No matching leg found for route', [
                        'route' => $this->buildRouteSignature($incomingLeg['segments']),
                        'flight_id' => $flight->id,
                    ]);

                    continue;
                }

                // Delete old segments and recreate with updated data
                $matched->segments()->delete();

                foreach ($incomingLeg['segments'] as $segIndex => $segmentData) {
                    $matched->segments()->create([
                        'position' => $segIndex,
                        'origin' => $segmentData['origin'],
                        'destination' => $segmentData['destination'],
                        'departure' => $segmentData['departure'],
                        'arrival' => $segmentData['arrival'],
                        'cabin_class' => $segmentData['cabinClass'],
                        'airline' => $segmentData['airline'],
                        'flight_number' => $segmentData['flightNumber'],
                    ]);
                }

                Log::info('Leg updated', ['leg_id' => $matched->id, 'flight_id' => $flight->id]);
            }
        });
    }

    public function matchLegs(Collection $existingLegs, array $legsData): array
    {
        $available = $existingLegs->keyBy('id');
        $matches = [];

        foreach ($legsData as $index => $incomingLeg) {
            $incomingRoute = $this->buildRouteSignature($incomingLeg['segments']);

            $matched = $available->first(
                fn (Leg $leg) => $this->buildRouteSignature(
                    $leg->segments->map(fn ($s) => [
                        'origin' => $s->origin,
                        'destination' => $s->destination,
                    ])->toArray()
                ) === $incomingRoute
            );

            if ($matched) {
                $available->forget($matched->id);
            }

            $matches[$index] = $matched;
        }

        return $matches;
    }

    /**
     * Build a string like "BCN>LON|LON>JFK" to uniquely identify a leg's route.
     */
    public function buildRouteSignature(array $segments): string
    {
        return collect($segments)
            ->map(fn (array $s) => $s['origin'].'>'.$s['destination'])
            ->implode('|');
    }
}
