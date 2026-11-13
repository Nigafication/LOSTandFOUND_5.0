<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LostFoundItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/items', [LostFoundItemController::class, 'index']);
Route::middleware('auth')->group(function () {
    Route::post('/items', [LostFoundItemController::class, 'store']);
    Route::get('/saved-items', [LostFoundItemController::class, 'saved']);
    Route::post('/items/{item}/save', [LostFoundItemController::class, 'save']);
    Route::delete('/items/{item}/save', [LostFoundItemController::class, 'unsave']);
});
});
