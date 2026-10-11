<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Аутентификация для REST API (JWT).
 *
 * Guard: 'api' (config/auth.php → driver 'jwt', provider 'users').
 * Токен выдаётся tymon/jwt-auth.
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/login — получить JWT-токен.
     *
     * Принимает JSON: { email, password }.
     * Валидация: email обязателен и email, password обязателен.
     *
     * Если JWTAuth::attempt() вернул токен — возвращаем
     * { access_token, token_type, expires_in }.
     * Иначе 401 с ошибкой "Неверный email или пароль".
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'error' => 'Неверный email или пароль.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * POST /api/auth/me — данные текущего пользователя.
     *
     * Требует валидный JWT (middleware 'auth:api').
     * Возвращает объект User (через toJson).
     */
    public function me(): JsonResponse
    {
        return response()->json(Auth::user());
    }

    /**
     * POST /api/auth/logout — инвалидировать текущий токен.
     *
     * JWTAuth::invalidate() кладёт токен в blacklist (если настроено),
     * либо просто отменяет его (зависит от конфигурации jwt-auth).
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Вы вышли из системы.',
        ]);
    }

    /**
     * POST /api/auth/refresh — получить новый токен взамен текущего.
     *
     * JWTAuth::refresh() выдаёт новый токен с обновлённым сроком жизни.
     * Старый токен инвалидируется.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(JWTAuth::refresh());
    }

    /**
     * Вспомогательный метод: стандартный JSON-ответ с токеном.
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60, // TTL в секундах
        ]);
    }
}