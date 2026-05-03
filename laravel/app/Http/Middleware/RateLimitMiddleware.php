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
        $limit = $isAuth ? $maxAttempts : max(20, (int)($maxAttempts / 4));
        $key = 'rate_limit:' . ($isAuth ? $request->user()->id : $request->ip());
        $ttl = $decayMinutes * 60;

        // Atomic window init: Cache::add only sets if key does not exist
        Cache::add($key . ':timer', time(), $ttl);
        Cache::add($key, 0, $ttl);

        // Atomic increment preserves the original TTL set by Cache::add
        $attempts = Cache::increment($key);

        if ($attempts > $limit) {
            $windowStart = Cache::get($key . ':timer', time());
            $retryAfter = max(1, $ttl - (time() - (int)$windowStart));
            return response()->json([
                'error'   => 'Too Many Requests',
                'message' => "Rate limit exceeded. Try again in {$retryAfter} seconds.",
            ], 429)->withHeaders([
                'Retry-After'           => $retryAfter,
                'X-RateLimit-Limit'     => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-RateLimit-Limit', (string)$limit);
            $response->headers->set('X-RateLimit-Remaining', (string)max(0, $limit - $attempts));
        }

        return $response;
    }
}
