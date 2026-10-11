<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'sku' => $this->sku,
            'weight' => (int) $this->weight,
            'is_virtual_try_on' => (bool) $this->is_virtual_try_on,
            'is_active' => (bool) $this->is_active,
            'primary_image_url' => $this->primary_image_url,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'gallery_images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'sort_order' => $img->sort_order,
            ])),
            'variants' => $this->whenLoaded('variantValues', fn () => $this->variantValues->map(fn ($val) => [
                'id' => $val->id,
                'type' => $val->variantType?->name,
                'value' => $val->value,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
