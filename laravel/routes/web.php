<?php

use Illuminate\Support\Facades\Route;

// Admin SPA — serve admin index.html for admin client-side routes
Route::get('/admin/{any?}', function () {
    return response()->file(public_path('admin/index.html'));
})->where('any', '.*');

// User-app SPA — serve user-app index.html for all other non-API routes
Route::get('/{any?}', function () {
    return response()->file(public_path('user-app/index.html'));
})->where('any', '^(?!api|sanctum).*$');
