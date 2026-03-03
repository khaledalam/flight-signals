<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('Api-Key');
        $expectedKey = config('services.api.key');

        if (! $providedKey || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Invalid or missing Api-Key.'], 401);
        }

        return $next($request);
    }
}
