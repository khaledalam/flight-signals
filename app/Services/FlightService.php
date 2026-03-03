<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Leg;
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

    /**
     * Match incoming legs to existing ones by comparing the route signature
     * (ordered origin→destination pairs of each segment).
     */
    public function updateFlight(Flight $flight, array $legsData): void
    {
        DB::transaction(function () use ($flight, $legsData) {
            $existingLegs = $flight->legs()->with('segments')->get();

            foreach ($legsData as $incomingLeg) {
                $incomingRoute = $this->buildRouteSignature($incomingLeg['segments']);
                $matched = $existingLegs->first(
                    fn (Leg $leg) => $this->buildRouteSignature(
                        $leg->segments->map(fn ($s) => [
                            'origin' => $s->origin,
                            'destination' => $s->destination,
                        ])->toArray()
                    ) === $incomingRoute
                );

                if (! $matched) {
                    Log::warning('No matching leg found for route', ['route' => $incomingRoute, 'flight_id' => $flight->id]);

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

    /**
     * Build a string like "BCN>LON|LON>JFK" to uniquely identify a leg's route.
     */
    private function buildRouteSignature(array $segments): string
    {
        return collect($segments)
            ->map(fn (array $s) => $s['origin'].'>'.$s['destination'])
            ->implode('|');
    }
}
