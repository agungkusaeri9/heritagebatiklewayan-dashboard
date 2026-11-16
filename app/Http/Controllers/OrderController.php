<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderNotificationService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        protected OrderNotificationService $orderNotificationService
    ) {}

    private function authorizeOrderAccess(?User $user, string $permission): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('super admin')) {
            return;
        }

        if ($user->hasRole(StoreService::ADMIN_STORE_ROLE) && ! $user->hasRole('admin')) {
            abort(403, 'Role Admin Store tidak memiliki akses ke halaman Pesanan.');
        }

        if ($user->roles()->exists() && ! $user->can($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses fitur Pesanan.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeOrderAccess($request->user(), 'view orders');

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Order::with(['customer', 'paymentMethod', 'courier'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->paginate($perPage)->withQueryString();

        return Inertia::render('orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'per_page']),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($request->user(), 'view orders');

        $order->load(['customer', 'paymentMethod', 'courier', 'province', 'city', 'subdistrict', 'details.product', 'transaction']);

        return Inertia::render('orders/Show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($request->user(), 'update order status');

        $request->validate([
            'status' => 'required|in:pending,processing,success,failed',
        ]);

        $order->update(['status' => $request->status]);

        $this->orderNotificationService->statusChanged($order);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($request->user(), 'update order payment');

        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed',
        ]);

        if ($order->transaction) {
            $order->transaction->update(['status' => $request->payment_status]);
            $this->orderNotificationService->paymentStatusChanged($order);
        }

        return back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    public function updateShipping(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($request->user(), 'update order shipping');

        $validated = $request->validate([
            'shipping_name' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $order->update($validated);

        if (! empty($validated['tracking_number']) || ! empty($validated['shipping_name'])) {
            $this->orderNotificationService->shippingUpdated($order);
        }

        return back()->with('success', 'Informasi pengiriman & nomor resi berhasil diperbarui.');
    }
}
