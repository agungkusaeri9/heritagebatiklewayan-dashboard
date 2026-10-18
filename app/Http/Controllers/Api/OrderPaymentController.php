<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\UploadPaymentProofRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class OrderPaymentController extends Controller
{
    /**
     * Upload payment proof image for the specified order.
     */
    public function upload(UploadPaymentProofRequest $request, string $identifier): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || ! $customer->is_active) {
            return ApiResponse::error(
                message: 'Akun ini tidak memiliki profil customer aktif.',
                code: 403,
            );
        }

        $query = Order::where('customer_id', $customer->id);

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

        $file = $request->file('payment_proof');
        $path = $file->store('payment_proofs', 'public');

        // Delete old file if present
        if ($order->transaction && $order->transaction->payment_proof) {
            if (Storage::disk('public')->exists($order->transaction->payment_proof)) {
                Storage::disk('public')->delete($order->transaction->payment_proof);
            }
        }

        if ($order->transaction) {
            $order->transaction->update([
                'payment_proof' => $path,
            ]);
        } else {
            Transaction::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_proof' => $path,
                'status' => 'pending',
            ]);
        }

        $order->load([
            'customer',
            'paymentMethod',
            'courier',
            'province',
            'city',
            'subdistrict',
            'transaction',
            'details.product.images',
        ]);

        return ApiResponse::success(
            data: OrderDetailResource::make($order)->resolve($request),
            message: 'Bukti pembayaran berhasil diunggah.',
        );
    }
}
