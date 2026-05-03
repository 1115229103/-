<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\WorkController;

/*
|--------------------------------------------------------------------------
| API Routes — User-facing (prefix: /api/v1)
|--------------------------------------------------------------------------
|
| Public routes use throttle:120,1 — guests auto-limited to 30/min.
| Protected routes use auth:sanctum BEFORE throttle — so user gets 120/min.
*/

// Public routes (guest rate limit: 120→30/min)
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'AIStory API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    Route::get('/health/deep', function () {
        $cacheKey = 'health:deep:result';
        $cacheTtl = 15; // seconds

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached, $cached['status'] === 'ok' ? 200 : 503);
        }

        $checks = [];
        $healthy = true;
        $timeout = 1; // second

        // DB check — use existing PDO connection (fast)
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'driver' => DB::connection()->getDriverName()];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Redis check — only if Redis is configured as a driver
        $usesRedis = str_contains(env('CACHE_STORE', ''), 'redis')
            || env('QUEUE_CONNECTION') === 'redis'
            || env('SESSION_DRIVER') === 'redis';
        if ($usesRedis) {
            try {
                $redisHost = env('REDIS_HOST', '127.0.0.1');
                $redisPort = (int) env('REDIS_PORT', 6379);
                $fp = @fsockopen($redisHost, $redisPort, $errNo, $errStr, $timeout);
                if ($fp) {
                    fclose($fp);
                    $checks['redis'] = ['status' => 'ok'];
                } else {
                    throw new \Exception($errStr ?: 'Connection refused');
                }
            } catch (\Exception $e) {
                $checks['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
                $healthy = false;
            }
        }

        // FastAPI check — quick TCP socket
        try {
            $fastapiUrl = rtrim(env('FASTAPI_URL', 'http://localhost:8001'), '/');
            $fastapiParts = parse_url($fastapiUrl);
            $fastapiHost = $fastapiParts['host'] ?? 'localhost';
            $fastapiPort = $fastapiParts['port'] ?? 8001;
            $fp = @fsockopen($fastapiHost, (int) $fastapiPort, $errNo, $errStr, $timeout);
            if ($fp) {
                fclose($fp);
                $checks['fastapi'] = ['status' => 'ok'];
            } else {
                throw new \Exception($errStr ?: 'Connection refused');
            }
        } catch (\Exception $e) {
            $checks['fastapi'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        $result = [
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => 'AIStory API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        Cache::put($cacheKey, $result, $cacheTtl);

        return response()->json($result, $healthy ? 200 : 503);
    });

    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    Route::post('/auth/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store']);
    Route::post('/auth/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store']);

    Route::get('/models/categories', [ModelController::class, 'categories']);
    Route::get('/models', [ModelController::class, 'index']);
    Route::get('/plans', [\App\Http\Controllers\Api\PlanController::class, 'index']);
});

// Protected routes (auth runs first, so throttle sees the user → 120/min)
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::patch('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::delete('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'deleteAccount']);
    Route::post('/auth/change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);

    Route::get('/user/model-configs', [ModelController::class, 'myConfigs']);
    Route::post('/user/model-configs', [ModelController::class, 'storeConfig']);
    Route::put('/user/model-configs/{id}', [ModelController::class, 'updateConfig']);
    Route::delete('/user/model-configs/{id}', [ModelController::class, 'deleteConfig']);
    Route::post('/user/model-configs/{id}/verify', [ModelController::class, 'verifyConfig']);

    // Works (Projects)
    Route::get('/works', [WorkController::class, 'index']);
    Route::post('/works', [WorkController::class, 'store']);
    Route::get('/works/{id}', [WorkController::class, 'show']);
    Route::put('/works/{id}', [WorkController::class, 'update']);
    Route::delete('/works/{id}', [WorkController::class, 'destroy']);
    Route::post('/works/{id}/pipeline/start', [WorkController::class, 'startPipeline']);
    Route::get('/works/{id}/pipeline/progress', [WorkController::class, 'pipelineProgress']);

    // Membership (plan moved to public group above)
    Route::get('/membership', [\App\Http\Controllers\Api\PlanController::class, 'myMembership']);
    Route::post('/orders', [\App\Http\Controllers\Api\PlanController::class, 'createOrder']);

    // Admin routes (require admin role)
    Route::prefix('admin')->middleware('admin')->group(base_path('routes/admin.php'));
});
