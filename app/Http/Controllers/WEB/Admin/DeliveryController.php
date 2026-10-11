<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\ShopApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Административная панель: управление заявками на доставку.
 *
 * Доступ: middleware ['auth', 'admin'].
 *   - 'auth' — пользователь должен быть залогинен;
 *   - 'admin' — CheckIfAdmin проверяет users.is_admin === true.
 *
 * Основная бизнес-логика:
 * - При смене статуса на "confirmed" (в updateStatus) списываются
 *   остатки товаров во внешнем магазине через ShopApiService.
 */
class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * GET /admin/deliveries — список всех заявок.
     *
     * Фильтры (GET-параметры):
     *   - status: фильтр по статусу (new, confirmed, shipped, delivered, cancelled);
     *   - search: поиск по названию, адресу, телефону, имени/email пользователя.
     *
     * withTrashed() — админ видит и удалённые заявки.
     * Пагинация 20 на страницу.
     */
    public function index(Request $request): View
    {
        $query = Delivery::with('user', 'items')->withTrashed();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $deliveries = $query->latest()->paginate(20);

        return view('admin.deliveries.index', [
            'deliveries'   => $deliveries,
            'statuses'     => Delivery::statuses(),
            'currentStatus' => $request->query('status'),
            'search'        => $request->query('search'),
        ]);
    }

    /**
     * GET /admin/deliveries/{delivery} — карточка заявки.
     *
     * Подгружаем items и user для отображения в вьюхе.
     * statuses — для селекта смены статуса.
     */
    public function show(Delivery $delivery): View
    {
        $delivery->load('items', 'user');

        return view('admin.deliveries.show', [
            'delivery' => $delivery,
            'statuses' => Delivery::statuses(),
        ]);
    }

    /**
     * PATCH /admin/deliveries/{delivery}/status — смена статуса + комментарий.
     *
     * Валидация: status из enum, admin_comment до 1000 символов.
     *
     * Ключевая логика: если статус меняется НА "confirmed" И РАНЬШЕ
     * НЕ БЫЛ "confirmed" — списываем остатки товаров во внешнем магазине.
     *
     * Логика списания:
     *   1. Грузим позиции заявки ($delivery->load('items')).
     *   2. Вызываем ShopApiService::reduceDeliveryStock($delivery).
     *   3. Там для каждой позиции с product_id вызывается PUT в магазин.
     *
     * Результат:
     *   - успех → flash success («Остатки списаны»)
     *   - ошибки у части товаров → flash warning со списком ошибок,
     *     но статус ВСЁ РАВНО сохраняется (транзакционности нет).
     *   - полная неудача → warning с ошибками.
     *
     * Важно: проверка ($newStatus === 'confirmed' && $oldStatus !== 'confirmed')
     * предотвращает двойное списание при повторном подтверждении.
     */
    public function updateStatus(Request $request, Delivery $delivery, ShopApiService $shopApi): RedirectResponse
    {
        $validated = $request->validate([
            'status'        => ['required', 'string', 'in:new,confirmed,shipped,delivered,cancelled'],
            'admin_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $delivery->status;
        $newStatus = $validated['status'];

        $delivery->update([
            'status'        => $newStatus,
            'admin_comment' => $validated['admin_comment'] ?? $delivery->admin_comment,
        ]);

        // При подтверждении — списываем остатки на складе магазина
        if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
            $delivery->load('items');
            $result = $shopApi->reduceDeliveryStock($delivery);

            if (!$result['success']) {
                $errorMsg = 'Статус обновлён, но есть ошибки при списании остатков: ' . implode('; ', $result['errors']);
                return redirect()
                    ->route('admin.deliveries.show', $delivery)
                    ->with('warning', $errorMsg);
            }

            return redirect()
                ->route('admin.deliveries.show', $delivery)
                ->with('success', 'Статус обновлён. Остатки на складе магазина списаны.');
        }

        return redirect()
            ->route('admin.deliveries.show', $delivery)
            ->with('success', 'Статус обновлён.');
    }

    /**
     * DELETE /admin/deliveries/{delivery} — мягкое удаление.
     *
     * SoftDeletes: deleted_at проставляется, запись остаётся в БД.
     * Админ видит удалённые через withTrashed() в index.
     */
    public function destroy(Delivery $delivery): RedirectResponse
    {
        $delivery->delete();

        return redirect()
            ->route('admin.deliveries.index')
            ->with('success', 'Заявка удалена.');
    }
}