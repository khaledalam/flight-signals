<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->getUser();
        $pass = $request->getPassword();

        if ($user !== 'admin' || $pass !== 'admin') {
            return response('Unauthorized.', 401, [
                'WWW-Authenticate' => 'Basic realm="Admin Dashboard"',
            ]);
        }

        return $next($request);
    }
}
