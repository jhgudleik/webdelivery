<?php

/*
|--------------------------------------------------------------------------
| API Routes — WebDelivery
|--------------------------------------------------------------------------
|
| REST API сервиса доставки. Авторизация — JWT (tymon/jwt-auth),
| guard "api" (config/auth.php → 'api' => ['driver' => 'jwt']).
|
| Токен получают через POST /api/auth/login и передают
| в заголовке:  Authorization: Bearer <token>
|
| Все ответы — JSON формата { success, data, message }.
|
*/

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DeliveryController;
use Illuminate\Support\Facades\Route;

// ─── Публичный эндпоинт ────────────────────────────
// Логин: принимает { email, password }, возвращает
// { access_token, token_type, expires_in }.
Route::post('/auth/login', [AuthController::class, 'login']);

// ─── Защищённые эндпоинты (требуют JWT) ────────────
Route::middleware('auth:api')->group(function () {

    // Управление токеном и текущим пользователем
    Route::post('/auth/logout', [AuthController::class, 'logout']);   // инвалидировать токен
    Route::post('/auth/refresh', [AuthController::class, 'refresh']); // получить новый токен
    Route::post('/auth/me', [AuthController::class, 'me']);           // данные текущего пользователя

    // ─── CRUD заявок на доставку ───────────────────
    // Правила доступа совпадают с веб-версией:
    //   - список и создание — любой авторизованный;
    //   - просмотр/удаление — владелец или админ;
    //   - обновление — только владелец и только в статусе "new"
    //     (иначе 422, см. API\DeliveryController::update).
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show']);
    Route::put('/deliveries/{delivery}', [DeliveryController::class, 'update']);
    Route::delete('/deliveries/{delivery}', [DeliveryController::class, 'destroy']);
});
