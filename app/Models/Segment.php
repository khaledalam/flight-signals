<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
    protected $fillable = [
        'leg_id',
        'position',
        'origin',
        'destination',
        'departure',
        'arrival',
        'cabin_class',
        'airline',
        'flight_number',
    ];

    protected function casts(): array
    {
        return [
            'departure' => 'datetime',
            'arrival' => 'datetime',
        ];
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(Leg::class);
    }

    public function toPayload(): array
    {
        return [
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure' => $this->departure->format('Y-m-d\TH:i:s'),
            'arrival' => $this->arrival->format('Y-m-d\TH:i:s'),
            'cabinClass' => $this->cabin_class,
            'airline' => $this->airline,
            'flightNumber' => $this->flight_number,
        ];
    }
}
