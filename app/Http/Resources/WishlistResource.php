<?php

namespace App\Http\Resources;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wishlist
 */
class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $product?->name,
            'product_slug' => $product?->slug,
            'primary_image' => $product?->primary_image_url,
            'product_price' => (float) ($product?->price ?? 0),
            'stock' => (int) ($product?->stock ?? 0),
            'is_in_stock' => (int) ($product?->stock ?? 0) > 0,
            'is_product_active' => (bool) ($product?->is_active ?? true),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
