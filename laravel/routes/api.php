<?php

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
        $checks = [];
        $healthy = true;

        // DB check
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'driver' => DB::connection()->getDriverName()];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Redis check
        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $checks['redis'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // FastAPI check
        try {
            $fastapiUrl = rtrim(env('FASTAPI_URL', 'http://localhost:8001'), '/');
            $resp = \Illuminate\Support\Facades\Http::timeout(5)->get($fastapiUrl . '/health');
            $checks['fastapi'] = $resp->successful()
                ? ['status' => 'ok', 'code' => $resp->status()]
                : ['status' => 'error', 'code' => $resp->status()];
            if (!$resp->successful()) $healthy = false;
        } catch (\Exception $e) {
            $checks['fastapi'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => 'AIStory API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
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
