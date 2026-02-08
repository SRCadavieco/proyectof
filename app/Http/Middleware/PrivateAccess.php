<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivateAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = env('APP_GATE_USER');
        $pass = env('APP_GATE_PASSWORD');

        // If gate not configured, allow access
        if (!$user || !$pass) {
            return $next($request);
        }

        $ok = false;
        $auth = $request->header('Authorization');
        if ($auth && str_starts_with($auth, 'Basic ')) {
            $decoded = base64_decode(substr($auth, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$u, $p] = explode(':', $decoded, 2);
                $ok = ($u === $user && $p === $pass);
            }
        }

        if (!$ok) {
            return response('Unauthorized', 401)
                ->header('WWW-Authenticate', 'Basic realm="Restricted"');
        }

        return $next($request);
    }
}
