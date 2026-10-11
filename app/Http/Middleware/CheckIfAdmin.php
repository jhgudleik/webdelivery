<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки прав администратора.
 *
 * Зарегистрирован в bootstrap/app.php как алиас 'admin'.
 * Используется в routes/web.php для админских маршрутов:
 *   Route::middleware(['auth', 'admin'])->...
 *
 * Логика:
 *   1. Пользователь должен быть авторизован (проверяет $request->user()).
 *   2. Поле is_admin должно быть true.
 *   3. Если нет — 403 для JSON, редирект на / для веба.
 *
 * Примечание: в старой версии проверка всегда возвращала true
 * (захардкожено), сейчас использует реальное поле is_admin.
 */
class CheckIfAdmin
{
    /**
     * Обработка входящего запроса.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Нет пользователя или не админ
        if (!$request->user() || !$request->user()->is_admin) {

            // API — отдаём JSON 403
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещён.',
                ], 403);
            }

            // Веб — редирект на главную
            return redirect('/');
        }

        return $next($request);
    }
}