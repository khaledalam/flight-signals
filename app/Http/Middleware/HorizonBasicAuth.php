<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HorizonBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->getUser();
        $pass = (string) $request->getPassword();
        $expectedUser = config('services.horizon.username');
        $expectedPass = config('services.horizon.password');

        if (! $expectedUser || ! $expectedPass
            || $user !== $expectedUser
            || ! hash_equals($expectedPass, $pass)) {
            return response('Unauthorized.', 401, [
                'WWW-Authenticate' => 'Basic realm="Horizon"',
            ]);
        }

        return $next($request);
    }
}
