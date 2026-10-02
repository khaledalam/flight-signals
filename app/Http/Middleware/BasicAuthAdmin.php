<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::check($request)) {
            return response('Unauthorized.', 401, [
                'WWW-Authenticate' => 'Basic realm="Admin Dashboard"',
            ]);
        }

        return $next($request);
    }

    public static function check(Request $request): bool
    {
        $username = config('services.admin.username');
        $password = config('services.admin.password');

        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            return false;
        }

        return hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());
    }
}
