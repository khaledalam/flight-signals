<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    public function execute(string $key, string $scope, string $fingerprint, Closure $action): array
    {
        $cacheKey = $this->cacheKey($key, $scope);
        $lock = Cache::lock("{$cacheKey}:lock", (int) config('services.idempotency.lock_seconds'));

        if (! $lock->get()) {
            Log::info('Idempotency key in progress', ['key' => $key, 'scope' => $scope]);

            return ['status' => 409, 'body' => ['message' => 'A request with this Idempotency-Key is already in progress.']];
        }

        try {
            $stored = Cache::get($cacheKey);

            if ($stored !== null) {
                if (! hash_equals($stored['fingerprint'], $fingerprint)) {
                    return ['status' => 422, 'body' => ['message' => 'This Idempotency-Key was already used with a different payload.']];
                }

                Log::info('Idempotency hit', ['key' => $key, 'scope' => $scope]);

                return $stored['response'];
            }

            $response = $action();

            Cache::put($cacheKey, [
                'fingerprint' => $fingerprint,
                'response' => $response,
            ], (int) config('services.idempotency.ttl'));

            return $response;
        } finally {
            $lock->release();
        }
    }

    public function forget(string $key, string $scope): void
    {
        Cache::forget($this->cacheKey($key, $scope));
    }

    public function cacheKey(string $key, string $scope): string
    {
        return 'idempotency:'.hash('sha256', $scope.'|'.$key);
    }
}
