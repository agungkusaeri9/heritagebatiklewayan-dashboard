<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders belonging to the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer) {
            return ApiResponse::error(
                message: 'Akun ini tidak memiliki profil customer.',
                code: 403,
            );
        }

        $query = Order::query()
            ->where('customer_id', $customer->id)
            ->with([
                'paymentMethod',
                'courier',
                'transaction',
                'details.product',
                'reviews',
            ])
            ->latest('id');

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('details.product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = $query->paginate($perPage);

        return ApiResponse::paginate(
            paginator: $orders,
            data: OrderListResource::collection($orders)->resolve($request),
            message: 'Berhasil mengambil daftar pesanan.',
        );
    }

    /**
     * Display the specified order details for the authenticated customer.
     * Looks up by order_number or primary key ID.
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer) {
            return ApiResponse::error(
                message: 'Akun ini tidak memiliki profil customer.',
                code: 403,
            );
        }

        $query = Order::query()
            ->where('customer_id', $customer->id)
            ->with([
                'customer',
                'paymentMethod',
                'courier',
                'province',
                'city',
                'subdistrict',
                'transaction',
                'details.product.images',
                'reviews',
            ]);

        $order = is_numeric($identifier)
            ? $query->where(function ($q) use ($identifier) {
                $q->where('id', (int) $identifier)
                    ->orWhere('order_number', $identifier);
            })->first()
            : $query->where('order_number', $identifier)->first();

        if (! $order) {
            return ApiResponse::error(
                message: 'Pesanan tidak ditemukan.',
                code: 404,
            );
        }

        return ApiResponse::success(
            data: OrderDetailResource::make($order)->resolve($request),
            message: 'Berhasil mengambil detail pesanan.',
        );
    }
}
