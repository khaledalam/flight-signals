<?php

namespace App\Http\Requests;

use App\Models\Leg;
use App\Services\FlightService;
use App\Support\ItineraryValidator;
use Illuminate\Validation\Validator;

class UpdateFlightRequest extends FlightPayloadRequest
{
    protected function afterItinerary(Validator $validator): void
    {
        $flight = $this->route('flight')->loadMissing('legs.segments');
        $incomingLegs = $this->input('legs');

        $matches = app(FlightService::class)->matchLegs($flight->legs, $incomingLegs);

        foreach ($matches as $index => $leg) {
            if (! $leg) {
                $validator->errors()->add("legs.{$index}", 'No leg of this flight matches this route.');
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $merged = $flight->legs->mapWithKeys(fn (Leg $leg) => [
            $leg->id => ['segments' => $leg->segments->map->toPayload()->all()],
        ])->all();

        foreach ($matches as $index => $leg) {
            $merged[$leg->id] = $incomingLegs[$index];
        }

        if (ItineraryValidator::errors(array_values($merged)) !== []) {
            $validator->errors()->add('legs', 'The updated legs would overlap with the other legs of this flight.');
        }
    }
}
