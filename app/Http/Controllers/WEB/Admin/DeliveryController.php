<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * List all deliveries.
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
     * Show single delivery.
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
     * Update delivery status and admin comment.
     */
    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'status'        => ['required', 'string', 'in:new,confirmed,shipped,delivered,cancelled'],
            'admin_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $delivery->update([
            'status'        => $validated['status'],
            'admin_comment' => $validated['admin_comment'] ?? $delivery->admin_comment,
        ]);

        return redirect()
            ->route('admin.deliveries.show', $delivery)
            ->with('success', 'Статус обновлён.');
    }

    /**
     * Soft-delete delivery.
     */
    public function destroy(Delivery $delivery): RedirectResponse
    {
        $delivery->delete();

        return redirect()
            ->route('admin.deliveries.index')
            ->with('success', 'Заявка удалена.');
    }
}
