<?php

namespace App\Http\Resources;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = (float) ($this->product?->price ?? 0);
        $qty = (int) $this->quantity;
        $total = $price * $qty;
        $weight = (int) ($this->product?->weight ?? 0);
        $stock = (int) ($this->product?->stock ?? 0);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_slug' => $this->product?->slug,
            'primary_image' => $this->product?->primary_image_url,
            'product_price' => $price,
            'qty' => $qty,
            'total' => $total,
            'variant_info' => $this->variant_info,
            'weight' => $weight,
            'total_weight' => $weight * $qty,
            'stock' => $stock,
            'is_in_stock' => $stock >= $qty,
            'is_product_active' => (bool) ($this->product?->is_active ?? true),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
