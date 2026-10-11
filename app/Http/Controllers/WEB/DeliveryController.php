<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CRUD заявок на доставку (пользовательская часть).
 *
 * Все действия доступны только авторизованным пользователям
 * (middleware 'auth' в конструкторе).
 *
 * Правила доступа:
 * - index/create/store — доступны любому авторизованному;
 * - show — владелец заявки или админ;
 * - edit/update — только владелец и только если статус "new";
 * - destroy — владелец или админ (soft delete).
 */
class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /deliveries — список своих заявок.
     *
     * Пагинация по 10, с подгрузкой items (eager loading).
     */
    public function index(): View
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('deliveries.index', [
            'deliveries' => $deliveries,
        ]);
    }

    /**
     * GET /deliveries/create — форма создания заявки.
     *
     * Статусы передаются в вьюху (хотя в create они не используются,
     * статус всегда 'new' при создании).
     */
    public function create(): View
    {
        return view('deliveries.create', [
            'statuses' => Delivery::statuses(),
        ]);
    }

    /**
     * POST /deliveries — создать заявку.
     *
     * Валидация:
     * - title, delivery_address, phone — обязательны;
     * - items — массив минимум из 1 элемента;
     * - каждая позиция: product_title, quantity (>=1), price (>=0);
     * - product_id — опционально (для связки с товаром магазина).
     *
     * product_id заполняется скрытым полем в форме,
     * если пользователь перешел с каталога.
     *
     * После создания пересчитывается total_price и редирект на show.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone'            => ['required', 'string', 'max:20'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'integer'],
            'items.*.product_title' => ['required', 'string', 'max:255'],
            'items.*.product_sku'   => ['nullable', 'string', 'max:100'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.price'         => ['required', 'numeric', 'min:0'],
        ], [
            'title.required'            => 'Введите название.',
            'delivery_address.required' => 'Введите адрес доставки.',
            'phone.required'            => 'Введите телефон.',
            'items.required'            => 'Добавьте хотя бы один товар.',
            'items.min'                 => 'Добавьте хотя бы один товар.',
            'items.*.product_title.required' => 'Укажите название товара.',
            'items.*.quantity.required'      => 'Укажите количество.',
            'items.*.quantity.min'           => 'Количество должно быть не менее 1.',
            'items.*.price.required'         => 'Укажите цену.',
            'items.*.price.min'              => 'Цена не может быть отрицательной.',
        ]);

        $delivery = Auth::user()->deliveries()->create([
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'phone'            => $validated['phone'],
            'status'           => 'new',
        ]);

        // Позиции создаются с product_id (может быть null)
        foreach ($validated['items'] as $item) {
            $delivery->items()->create([
                'product_id'    => $item['product_id'] ?? null,
                'product_title' => $item['product_title'],
                'product_sku'   => $item['product_sku'] ?? null,
                'quantity'      => $item['quantity'],
                'price'         => $item['price'],
            ]);
        }

        $delivery->recalculateTotal();

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('success', 'Заявка на доставку создана.');
    }

    /**
     * GET /deliveries/{delivery} — просмотр одной заявки.
     *
     * Проверка доступа: владелец или админ. Иначе 403.
     * Подгружаем items и user для вьюхи.
     */
    public function show(Delivery $delivery): View
    {
        if ($delivery->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $delivery->load('items', 'user');

        return view('deliveries.show', [
            'delivery' => $delivery,
        ]);
    }

    /**
     * GET /deliveries/{delivery}/edit — форма редактирования.
     *
     * Проверки:
     * 1. Только владелец (403 если чужой).
     * 2. Только статус "new" — иначе редирект с ошибкой.
     * Подгружаем items для заполнения формы.
     */
    public function edit(Delivery $delivery): \Illuminate\Http\RedirectResponse|View
    {
        if ($delivery->user_id !== Auth::id()) {
            abort(403);
        }

        if ($delivery->status !== 'new') {
            return redirect()
                ->route('deliveries.show', $delivery)
                ->with('error', 'Редактирование возможно только для заявок со статусом «Новая».');
        }

        $delivery->load('items');

        return view('deliveries.edit', [
            'delivery' => $delivery,
        ]);
    }

    /**
     * PUT /deliveries/{delivery} — обновить заявку.
     *
     * Те же проверки доступа, что и edit.
     * Валидация такая же, как в store.
     * Позиции полностью заменяются (старые удаляются, новые создаются).
     * total_price пересчитывается.
     */
    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        if ($delivery->user_id !== Auth::id()) {
            abort(403);
        }

        if ($delivery->status !== 'new') {
            return redirect()
                ->route('deliveries.show', $delivery)
                ->with('error', 'Редактирование возможно только для заявок со статусом «Новая».');
        }

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone'            => ['required', 'string', 'max:20'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'integer'],
            'items.*.product_title' => ['required', 'string', 'max:255'],
            'items.*.product_sku'   => ['nullable', 'string', 'max:100'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.price'         => ['required', 'numeric', 'min:0'],
        ]);

        $delivery->update([
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'phone'            => $validated['phone'],
        ]);

        // Полная замена позиций
        $delivery->items()->delete();
        foreach ($validated['items'] as $item) {
            $delivery->items()->create([
                'product_id'    => $item['product_id'] ?? null,
                'product_title' => $item['product_title'],
                'product_sku'   => $item['product_sku'] ?? null,
                'quantity'      => $item['quantity'],
                'price'         => $item['price'],
            ]);
        }

        $delivery->recalculateTotal();

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with('success', 'Заявка обновлена.');
    }

    /**
     * DELETE /deliveries/{delivery} — мягкое удаление.
     *
     * Владелец или админ. SoftDeletes — запись остаётся в БД с deleted_at.
     */
    public function destroy(Delivery $delivery): RedirectResponse
    {
        if ($delivery->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $delivery->delete();

        return redirect()
            ->route('deliveries.index')
            ->with('success', 'Заявка удалена.');
    }
}