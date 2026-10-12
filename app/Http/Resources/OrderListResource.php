<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalItems = $this->details->sum('quantity');

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'total_amount' => (float) $this->total_amount,
            'total_items' => (int) $totalItems,
            'shipping' => [
                'courier_name' => $this->courier?->name,
                'courier_code' => $this->courier?->code,
                'shipping_name' => $this->shipping_name,
                'tracking_number' => $this->tracking_number,
            ],
            'payment' => [
                'payment_method_id' => $this->payment_method_id,
                'payment_method_name' => $this->paymentMethod?->name,
                'payment_status' => $this->transaction?->status ?? 'pending',
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
