<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'pricing' => [
                'subtotal' => (float) $this->subtotal,
                'shipping_fee' => (float) $this->shipping_fee,
                'total_amount' => (float) $this->total_amount,
            ],
            'shipping_address' => [
                'address' => $this->address,
                'province' => $this->province ? [
                    'id' => $this->province->province_id,
                    'name' => $this->province->province_name,
                ] : null,
                'city' => $this->city ? [
                    'id' => $this->city->city_id,
                    'name' => $this->city->city_name,
                    'postal_code' => $this->city->postal_code,
                ] : null,
                'subdistrict' => $this->subdistrict ? [
                    'id' => $this->subdistrict->subdistrict_id,
                    'name' => $this->subdistrict->subdistrict_name,
                ] : null,
            ],
            'shipping' => [
                'courier_id' => $this->courier_id,
                'courier_name' => $this->courier?->name,
                'courier_code' => $this->courier?->code,
                'shipping_name' => $this->shipping_name,
                'tracking_number' => $this->tracking_number,
            ],
            'payment' => [
                'payment_method_id' => $this->payment_method_id,
                'payment_method_name' => $this->paymentMethod?->name,
                'payment_account_number' => $this->paymentMethod?->account_number,
                'payment_account_name' => $this->paymentMethod?->account_name,
                'payment_logo_url' => $this->paymentMethod?->logo_url,
                'transaction' => $this->transaction ? [
                    'id' => $this->transaction->id,
                    'amount' => (float) $this->transaction->amount,
                    'status' => $this->transaction->status,
                    'payment_proof' => $this->transaction->payment_proof,
                    'created_at' => $this->transaction->created_at?->toIso8601String(),
                ] : null,
            ],
            'items' => $this->details->map(fn ($detail) => [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product?->name,
                'product_slug' => $detail->product?->slug,
                'primary_image' => $detail->product?->primary_image_url,
                'variant_info' => $detail->variant_info,
                'price' => (float) $detail->price,
                'quantity' => (int) $detail->quantity,
                'total_price' => (float) $detail->total_price,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
