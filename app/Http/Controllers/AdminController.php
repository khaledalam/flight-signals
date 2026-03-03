<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\IdempotentRequest;
use App\Models\Leg;
use App\Models\Segment;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController
{
    public function index(): View
    {
        $stats = [
            'flights' => Flight::count(),
            'legs' => Leg::count(),
            'segments' => Segment::count(),
            'idempotency_records' => IdempotentRequest::count(),
        ];

        $recentFlights = Flight::with('legs.segments')
            ->latest()
            ->take(10)
            ->get();

        $failedJobs = DB::table('failed_jobs')->count();
        $pendingJobs = DB::table('jobs')->count();

        $env = [
            'APP_NAME' => config('app.name'),
            'APP_ENV' => config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'APP_URL' => config('app.url'),
            'PHP_VERSION' => PHP_VERSION,
            'LARAVEL_VERSION' => app()->version(),
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
            'QUEUE_CONNECTION' => config('queue.default'),
            'REDIS_HOST' => config('database.redis.default.host'),
            'CACHE_STORE' => config('cache.default'),
            'API_RATE_LIMIT' => config('services.api.rate_limit', 200),
            'HORIZON_PREFIX' => config('horizon.prefix'),
        ];

        return view('admin', compact('stats', 'recentFlights', 'failedJobs', 'pendingJobs', 'env'));
    }
}
