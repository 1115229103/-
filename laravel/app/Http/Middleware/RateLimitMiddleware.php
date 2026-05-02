<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 120, int $decayMinutes = 1): Response
    {
        $isAuth = $request->user() !== null;
        // Guests get 1/4 the limit (min 20); authenticated users get the full limit
        $limit = $isAuth ? $maxAttempts : max(20, (int)($maxAttempts / 4));
        $key = 'rate_limit:' . ($isAuth ? $request->user()->id : $request->ip());
        $ttl = $decayMinutes * 60;

        $attempts = Cache::get($key, 0);

        if ($attempts >= $limit) {
            return response()->json([
                'error'   => 'Too Many Requests',
                'message' => "Rate limit exceeded. Try again in {$ttl} seconds.",
            ], 429)->withHeaders([
                'Retry-After'           => $ttl,
                'X-RateLimit-Limit'     => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        Cache::put($key, $attempts + 1, $ttl);

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-RateLimit-Limit', (string)$limit);
            $remaining = $limit - $attempts - 1;
            $response->headers->set('X-RateLimit-Remaining', (string)max(0, $remaining));
        }

        return $response;
    }
}
