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
            'legs' => ['required', 'array', 'min:1'],
            'legs.*.segments' => ['required', 'array', 'min:1'],
            'legs.*.segments.*.origin' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.destination' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.departure' => ['required', 'date'],
            'legs.*.segments.*.arrival' => ['required', 'date', 'after:legs.*.segments.*.departure'],
            'legs.*.segments.*.cabinClass' => ['required', 'string', 'max:5'],
            'legs.*.segments.*.airline' => ['required', 'string', 'max:10'],
            'legs.*.segments.*.flightNumber' => ['required', 'string', 'max:20'],
        ];
    }
}
