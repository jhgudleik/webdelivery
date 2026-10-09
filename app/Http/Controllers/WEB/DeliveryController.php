<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List current user's deliveries.
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
     * Show create form.
     */
    public function create(): View
    {
        return view('deliveries.create', [
            'statuses' => Delivery::statuses(),
        ]);
    }

    /**
     * Store a new delivery.
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
     * Show a single delivery.
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
     * Show edit form.
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
     * Update delivery.
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

        // Replace items
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
     * Soft-delete delivery.
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
