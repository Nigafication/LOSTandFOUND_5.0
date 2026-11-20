<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostFoundItemController;
use Illuminate\Support\Facades\Route;

// Public authentication routes.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Sanctum-protected routes. Stateful SPA requests use the Laravel session cookie;
// bearer tokens remain supported for future mobile/API clients.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/items', [LostFoundItemController::class, 'store']);
    Route::get('/saved-items', [LostFoundItemController::class, 'saved']);
    Route::post('/items/{item}/save', [LostFoundItemController::class, 'save']);
    Route::delete('/items/{item}/save', [LostFoundItemController::class, 'unsave']);
});

// Item browsing is public.
Route::get('/items', [LostFoundItemController::class, 'index']);
