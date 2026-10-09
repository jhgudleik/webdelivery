<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DeliveryController;
use Illuminate\Support\Facades\Route;

// ─── Public ────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ─── JWT-protected ─────────────────────────────────
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/me', [AuthController::class, 'me']);

    // Deliveries CRUD
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show']);
    Route::put('/deliveries/{delivery}', [DeliveryController::class, 'update']);
    Route::delete('/deliveries/{delivery}', [DeliveryController::class, 'destroy']);
});
