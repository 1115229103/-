<?php

use Illuminate\Support\Facades\Route;

// Admin SPA — serve admin/index.html for admin client-side routes
Route::get('/admin/{any}', function () {
    return response()->file(public_path('admin/index.html'));
})->where('any', '.*');

Route::get('/admin', function () {
    return response()->file(public_path('admin/index.html'));
});

// User-app SPA — catch-all for all other non-API routes
Route::get('/{any}', function () {
    return response()->file(public_path('user-app/index.html'));
})->where('any', '^(?!api|sanctum).*$');

// Root
Route::get('/', function () {
    return response()->file(public_path('user-app/index.html'));
});
