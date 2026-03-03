<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Models\IdempotentRequest;
use App\Models\Leg;
use App\Models\Segment;
use Illuminate\Console\Command;

class FlightsStats extends Command
{
    protected $signature = 'flights:stats';

    protected $description = 'Show flight database statistics';

    public function handle(): int
    {
        $flights = Flight::count();
        $legs = Leg::count();
        $segments = Segment::count();
        $idempotencyKeys = IdempotentRequest::count();
        $latestFlight = Flight::latest()->first();

        $this->components->info('Flight Signals — Database Stats');

        $this->table(['Metric', 'Value'], [
            ['Flights', number_format($flights)],
            ['Legs', number_format($legs)],
            ['Segments', number_format($segments)],
            ['Idempotency records', number_format($idempotencyKeys)],
            ['Avg legs/flight', $flights > 0 ? round($legs / $flights, 1) : '—'],
            ['Avg segments/leg', $legs > 0 ? round($segments / $legs, 1) : '—'],
            ['Last created', $latestFlight?->created_at?->diffForHumans() ?? '—'],
        ]);

        return self::SUCCESS;
    }
}
