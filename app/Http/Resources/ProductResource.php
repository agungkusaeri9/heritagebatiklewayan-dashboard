<?php

namespace App\Http\Resources;

use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    protected static ?StoreSetting $cachedStoreSetting = null;

    protected static function resolveStoreSetting(): ?StoreSetting
    {
        if (static::$cachedStoreSetting === null) {
            static::$cachedStoreSetting = StoreSetting::first();
        }

        return static::$cachedStoreSetting;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productStore = $this->relationLoaded('store')
            ? $this->store
            : ($this->store_id ? $this->store : null);

        $storeData = null;
        if ($productStore instanceof Store) {
            $storeData = StoreResource::make($productStore)->resolve($request);
        } else {
            $fallbackStore = static::resolveStoreSetting();
            if ($fallbackStore) {
                $storeData = [
                    'id' => $fallbackStore->id,
                    'name' => $fallbackStore->name,
                    'logo_url' => $fallbackStore->logo_url,
                    'description' => $fallbackStore->description,
                    'address' => $fallbackStore->address,
                    'phone' => $fallbackStore->phone,
                    'whatsapp' => $fallbackStore->whatsapp,
                    'email' => $fallbackStore->email,
                    'website' => $fallbackStore->website,
                ];
            }
        }

        $soldCount = (int) OrderDetail::query()
            ->where('product_id', $this->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['processing', 'success', 'completed']))
            ->sum('quantity');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'sku' => $this->sku,
            'weight' => (int) $this->weight,
            'sold_count' => $soldCount,
            'is_virtual_try_on' => (bool) $this->is_virtual_try_on,
            'is_active' => (bool) $this->is_active,
            'primary_image_url' => $this->primary_image_url,
            'store_id' => $this->store_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'store' => $storeData,
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
