<?php

namespace App\Http\Requests;

use App\Support\AirportTimezones;
use App\Support\ItineraryValidator;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class FlightPayloadRequest extends FormRequest
{
    public const MAX_LEGS = 10;

    public const MAX_SEGMENTS_PER_LEG = 8;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $airport = ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/', $this->knownAirport(...)];
        $localTime = ['required', 'string', 'date_format:Y-m-d\TH:i:s'];

        $shape = [
            'legs' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_LEGS],
            'legs.*.segments' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_SEGMENTS_PER_LEG],
        ];

        if ($this->exceedsSizeLimits()) {
            return $shape;
        }

        return $shape + [
            'legs.*.segments.*.origin' => $airport,
            'legs.*.segments.*.destination' => $airport,
            'legs.*.segments.*.departure' => $localTime,
            'legs.*.segments.*.arrival' => $localTime,
            'legs.*.segments.*.cabinClass' => ['required', 'string', 'max:5'],
            'legs.*.segments.*.airline' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.flightNumber' => ['required', 'string', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach (ItineraryValidator::errors($this->input('legs')) as $attribute => $message) {
                    $validator->errors()->add($attribute, $message);
                }

                if ($validator->errors()->isEmpty()) {
                    $this->afterItinerary($validator);
                }
            },
        ];
    }

    protected function afterItinerary(Validator $validator): void {}

    private function exceedsSizeLimits(): bool
    {
        $legs = $this->input('legs');

        if (! is_array($legs)) {
            return false;
        }

        if (count($legs) > self::MAX_LEGS) {
            return true;
        }

        foreach ($legs as $leg) {
            if (is_array($leg['segments'] ?? null) && count($leg['segments']) > self::MAX_SEGMENTS_PER_LEG) {
                return true;
            }
        }

        return false;
    }

    private function knownAirport(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! AirportTimezones::has($value)) {
            $fail('The :attribute must be a known IATA airport or city code.');
        }
    }
}
