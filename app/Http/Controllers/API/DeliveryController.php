<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    /**
     * List current user's deliveries.
     */
    public function index(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $deliveries,
        ]);
    }

    /**
     * Store a new delivery.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone'            => ['required', 'string', 'max:20'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_title' => ['required', 'string', 'max:255'],
            'items.*.product_sku'   => ['nullable', 'string', 'max:100'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.price'         => ['required', 'numeric', 'min:0'],
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
                'product_title' => $item['product_title'],
                'product_sku'   => $item['product_sku'] ?? null,
                'quantity'      => $item['quantity'],
                'price'         => $item['price'],
            ]);
        }

        $delivery->recalculateTotal();
        $delivery->load('items');

        return response()->json([
            'success' => true,
            'data'    => $delivery,
            'message' => 'Заявка на доставку создана.',
        ], 201);
    }

    /**
     * Show single delivery.
     */
    public function show(Delivery $delivery): JsonResponse
    {
        if ($delivery->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещён.',
            ], 403);
        }

        $delivery->load('items', 'user');

        return response()->json([
            'success' => true,
            'data'    => $delivery,
        ]);
    }

    /**
     * Update delivery (only if status is "new").
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        if ($delivery->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещён.',
            ], 403);
        }

        if ($delivery->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'Редактирование возможно только для заявок со статусом «Новая».',
            ], 422);
        }

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone'            => ['required', 'string', 'max:20'],
            'items'            => ['required', 'array', 'min:1'],
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

        $delivery->items()->delete();
        foreach ($validated['items'] as $item) {
            $delivery->items()->create([
                'product_title' => $item['product_title'],
                'product_sku'   => $item['product_sku'] ?? null,
                'quantity'      => $item['quantity'],
                'price'         => $item['price'],
            ]);
        }

        $delivery->recalculateTotal();
        $delivery->load('items');

        return response()->json([
            'success' => true,
            'data'    => $delivery,
            'message' => 'Заявка обновлена.',
        ]);
    }

    /**
     * Delete delivery.
     */
    public function destroy(Delivery $delivery): JsonResponse
    {
        if ($delivery->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещён.',
            ], 403);
        }

        $delivery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Заявка удалена.',
        ]);
    }
}
