<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legs' => ['required', 'array', 'min:1', 'max:10'],
            'legs.*.segments' => ['required', 'array', 'min:1', 'max:20'],
            'legs.*.segments.*.origin' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.destination' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.departure' => ['required', 'date'],
            'legs.*.segments.*.arrival' => ['required', 'date', 'after:legs.*.segments.*.departure'],
            'legs.*.segments.*.cabinClass' => ['required', 'string', 'max:5'],
            'legs.*.segments.*.airline' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.flightNumber' => ['required', 'string', 'max:20'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('legs', []) as $legIndex => $leg) {
                $segments = $leg['segments'] ?? [];
                for ($i = 1; $i < count($segments); $i++) {
                    $prevArrival = $segments[$i - 1]['arrival'] ?? null;
                    $currDeparture = $segments[$i]['departure'] ?? null;

                    if ($prevArrival && $currDeparture && strtotime($currDeparture) < strtotime($prevArrival)) {
                        $validator->errors()->add(
                            "legs.{$legIndex}.segments.{$i}.departure",
                            "Segment {$i} departure must be at or after segment ".($i - 1).' arrival.'
                        );
                    }
                }
            }
        });
    }
}
