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
        $distinctProductIds = $this->details->pluck('product_id')->filter()->unique()->values();
        $reviews = $this->relationLoaded('reviews') ? $this->reviews : collect();
        $reviewedProductIds = $reviews->pluck('product_id')->filter()->unique()->values();
        $isReviewed = $distinctProductIds->isNotEmpty()
            && $distinctProductIds->diff($reviewedProductIds)->isEmpty();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'total_amount' => (float) $this->total_amount,
            'total_items' => (int) $totalItems,
            'is_reviewed' => $isReviewed,
            'reviewed_count' => $reviewedProductIds->count(),
            'average_rating' => $reviews->isNotEmpty() ? round((float) $reviews->avg('rating'), 1) : null,
            'reviews' => $reviews->map(fn ($rev) => [
                'id' => $rev->id,
                'order_id' => $rev->order_id,
                'product_id' => $rev->product_id,
                'rating' => (int) $rev->rating,
                'comment' => $rev->comment,
                'created_at' => $rev->created_at?->toIso8601String(),
            ])->values(),
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
                'payment_proof' => $this->transaction?->payment_proof,
                'payment_proof_url' => $this->transaction?->payment_proof_url,
            ],
            'payment_proof' => $this->transaction?->payment_proof,
            'payment_proof_url' => $this->transaction?->payment_proof_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
