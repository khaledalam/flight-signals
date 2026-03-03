<?php

namespace App\Console\Commands;

use App\Models\Flight;
use Illuminate\Console\Command;

class FlightInspect extends Command
{
    protected $signature = 'flights:inspect {flightId}';

    protected $description = 'Display a flight with all legs and segments';

    public function handle(): int
    {
        $flight = Flight::with('legs.segments')->find($this->argument('flightId'));

        if (! $flight) {
            $this->components->error('Flight not found.');

            return self::FAILURE;
        }

        $this->components->info("Flight {$flight->id}");
        $this->line("  Created: {$flight->created_at}");
        $this->line("  Updated: {$flight->updated_at}");
        $this->newLine();

        foreach ($flight->legs as $legIndex => $leg) {
            $this->components->twoColumnDetail(
                'Leg '.($legIndex + 1),
                $leg->segments->count().' segment(s)'
            );

            $rows = $leg->segments->map(fn ($s) => [
                $s->origin,
                $s->destination,
                $s->departure->format('Y-m-d H:i'),
                $s->arrival->format('Y-m-d H:i'),
                $s->airline.' '.$s->flight_number,
                $s->cabin_class,
            ])->toArray();

            $this->table(
                ['From', 'To', 'Departure', 'Arrival', 'Flight', 'Cabin'],
                $rows
            );
        }

        return self::SUCCESS;
    }
}
