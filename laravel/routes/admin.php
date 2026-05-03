<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ModelRegistryController;

/*
|--------------------------------------------------------------------------
| Admin API Routes — loaded from api.php
|--------------------------------------------------------------------------
| These routes are loaded via require in routes/api.php with prefix 'admin'.
| Auth via sanctum + admin role check middleware.
*/

// Admin routes are mounted from api.php with:
// Route::middleware(['auth:sanctum'])->prefix('admin')->group(base_path('routes/admin.php'));

// Dashboard
Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);

// Model Registry
Route::get('/models', [ModelRegistryController::class, 'index']);
Route::post('/models', [ModelRegistryController::class, 'store']);
Route::put('/models/{id}', [ModelRegistryController::class, 'update']);
Route::delete('/models/{id}', [ModelRegistryController::class, 'destroy']);
Route::put('/models/{id}/status', [ModelRegistryController::class, 'toggleStatus']);
Route::put('/models/sort', [ModelRegistryController::class, 'updateSort']);

// Pipeline Stages
Route::get('/pipeline-stages', [\App\Http\Controllers\Admin\PipelineStageController::class, 'index']);
Route::put('/pipeline-stages/{stage}', [\App\Http\Controllers\Admin\PipelineStageController::class, 'update']);

// Prompt Templates
Route::get('/prompt-templates', [\App\Http\Controllers\Admin\PromptTemplateController::class, 'index']);
Route::put('/prompt-templates/{stage}', [\App\Http\Controllers\Admin\PromptTemplateController::class, 'update']);

// Visual Styles
Route::apiResource('/visual-styles', \App\Http\Controllers\Admin\VisualStyleController::class);

// Voice Library
Route::apiResource('/voice-library', \App\Http\Controllers\Admin\VoiceLibraryController::class);

// Action Templates
Route::apiResource('/action-templates', \App\Http\Controllers\Admin\ActionTemplateController::class);

// Watermark
Route::get('/watermark-config', [\App\Http\Controllers\Admin\WatermarkController::class, 'show']);
Route::put('/watermark-config', [\App\Http\Controllers\Admin\WatermarkController::class, 'update']);

// Users
Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index']);
Route::get('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show']);

// Content
Route::apiResource('/works', \App\Http\Controllers\Admin\WorkController::class)->only(['index', 'show', 'destroy']);
Route::apiResource('/sensitive-words', \App\Http\Controllers\Admin\SensitiveWordController::class);
Route::apiResource('/banners', \App\Http\Controllers\Admin\BannerController::class);
Route::apiResource('/templates', \App\Http\Controllers\Admin\TemplateController::class);
Route::apiResource('/assets', \App\Http\Controllers\Admin\AssetController::class);

// Finance
Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index']);
Route::get('/finance/report', [\App\Http\Controllers\Admin\FinanceController::class, 'report']);

// Review
Route::get('/review/works', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
Route::put('/review/works/{id}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve']);
Route::put('/review/works/{id}/reject', [\App\Http\Controllers\Admin\ReviewController::class, 'reject']);

// Plans
Route::apiResource('/plans', \App\Http\Controllers\Admin\PlanController::class);
Route::put('/plans/{id}/status', [\App\Http\Controllers\Admin\PlanController::class, 'toggleStatus']);

// Roles
Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index']);
Route::put('/roles/{id}', [\App\Http\Controllers\Admin\RoleController::class, 'update']);

// System
Route::get('/system/settings', [\App\Http\Controllers\Admin\SystemController::class, 'index']);
Route::put('/system/settings', [\App\Http\Controllers\Admin\SystemController::class, 'update']);
Route::get('/system/operation-logs', [\App\Http\Controllers\Admin\OperationLogController::class, 'index']);
Route::get('/system/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index']);
Route::post('/system/backups', [\App\Http\Controllers\Admin\BackupController::class, 'create']);
Route::get('/system/backups/{id}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download']);
Route::delete('/system/backups/{id}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy']);
