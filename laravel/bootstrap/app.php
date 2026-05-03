<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(HandleCors::class);
        $middleware->trustProxies(at: '*');
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        $middleware->alias([
            'admin'    => \App\Http\Middleware\AdminMiddleware::class,
            'throttle' => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([]);
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson() || str_starts_with($request->path(), 'api/');
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'error'   => 'unauthenticated',
                'message' => $e->getMessage() ?: 'Unauthenticated',
            ], 401);
        });
    })->create();
