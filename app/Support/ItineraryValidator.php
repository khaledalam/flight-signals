<?php

namespace App\Support;

class ItineraryValidator
{
    public static function errors(array $legs): array
    {
        $errors = [];
        $previousLegArrival = null;

        foreach ($legs as $l => $leg) {
            $previousArrival = null;

            foreach ($leg['segments'] as $s => $segment) {
                $attribute = "legs.{$l}.segments.{$s}";

                if ($segment['origin'] === $segment['destination']) {
                    $errors["{$attribute}.destination"] = 'The destination must differ from the origin.';

                    continue;
                }

                $departure = AirportTimezones::toUtc($segment['departure'], $segment['origin']);
                $arrival = AirportTimezones::toUtc($segment['arrival'], $segment['destination']);

                if ($arrival->lessThanOrEqualTo($departure)) {
                    $errors["{$attribute}.arrival"] = 'The arrival must be after the departure (times are local to each airport).';
                }

                if ($s === 0 && $previousLegArrival && $departure->lessThanOrEqualTo($previousLegArrival)) {
                    $errors["{$attribute}.departure"] = 'The leg must depart after the previous leg arrives.';
                }

                if ($previousArrival && $departure->lessThanOrEqualTo($previousArrival)) {
                    $errors["{$attribute}.departure"] = 'The segment must depart after the previous segment arrives.';
                }

                $previousArrival = $arrival;
            }

            $previousLegArrival = $previousArrival ?? $previousLegArrival;
        }

        return $errors;
    }
}
