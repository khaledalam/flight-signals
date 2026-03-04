<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\IdempotentRequest;
use App\Models\Leg;
use App\Models\Segment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function getStats(): array
    {
        return [
            'flights' => Flight::count(),
            'legs' => Leg::count(),
            'segments' => Segment::count(),
            'idempotency_records' => IdempotentRequest::count(),
        ];
    }

    public function getRecentFlights(int $limit = 10): Collection
    {
        return Flight::with('legs.segments')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getJobCounts(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }

    public function getEnv(): array
    {
        return [
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
    }
}
