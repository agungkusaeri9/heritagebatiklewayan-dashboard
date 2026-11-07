<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\NotificationSetting;
use App\Models\Order;

class OrderNotificationService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function orderCreated(Order $order): void
    {
        $this->notify(
            $order,
            'Pesanan Berhasil Dibuat',
            "Pesanan #{$order->order_number} berhasil dibuat. Silakan lakukan pembayaran sesuai metode yang dipilih."
        );
    }

    public function paymentStatusChanged(Order $order): void
    {
        $status = $order->transaction?->status;

        [$title, $message] = match ($status) {
            'paid' => [
                'Pembayaran Berhasil',
                "Pembayaran untuk pesanan #{$order->order_number} telah kami validasi sebagai LUNAS.",
            ],
            'failed' => [
                'Pembayaran Gagal',
                "Pembayaran untuk pesanan #{$order->order_number} gagal divalidasi. Silakan unggah ulang bukti pembayaran.",
            ],
            default => [
                'Status Pembayaran Diperbarui',
                "Status pembayaran pesanan #{$order->order_number} kini: {$status}.",
            ],
        };

        $this->notify($order, $title, $message);
    }

    public function statusChanged(Order $order): void
    {
        $status = ucfirst((string) $order->status);

        $this->notify(
            $order,
            'Status Pesanan Diperbarui',
            "Status pesanan #{$order->order_number} telah diubah menjadi {$status}."
        );
    }

    public function shippingUpdated(Order $order): void
    {
        $courier = $order->shipping_name ?: 'kurir pengiriman';
        $resi = $order->tracking_number ? " Nomor resi: {$order->tracking_number}." : '';

        $this->notify(
            $order,
            'Paket Telah Dikirim',
            "Pesanan #{$order->order_number} telah dikirim via {$courier}.{$resi}"
        );
    }

    protected function notify(Order $order, string $title, string $message): void
    {
        $userId = $this->resolveUserId($order);

        if (! $userId) {
            return;
        }

        $setting = NotificationSetting::where('code', 'ORDER_STATUS_UPDATE')->first();

        $this->notificationService->createNotification([
            'notification_setting_id' => $setting?->id,
            'title' => $title,
            'message' => $message,
            'recipient_type' => 'SPECIFIC_USER',
        ], null, [$userId]);
    }

    protected function resolveUserId(Order $order): ?int
    {
        return Customer::whereKey($order->customer_id)->value('user_id');
    }
}
