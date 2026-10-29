<?php

namespace App\Http\Resources;

use App\Models\ProductVirtualTryOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVirtualTryOn
 */
class ProductVirtualTryOnResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'price' => (float) $this->product->price,
                'sku' => $this->product->sku,
                'primary_image_url' => $this->product->primary_image_url,
            ]),
            'virtual_try_on_model_id' => $this->virtual_try_on_model_id,
            'virtual_model' => $this->whenLoaded('virtualModel', fn () => [
                'id' => $this->virtualModel->id,
                'name' => $this->virtualModel->name,
                'gender' => $this->virtualModel->gender,
                'photo_url' => $this->virtualModel->photo_url,
            ]),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'gender' => $this->gender ?? $this->virtualModel?->gender,
            'photo_url' => $this->photo_url,
            'video_url' => $this->video_url,
            'status' => $this->status,
            'progress' => (int) $this->progress,
            'step_label' => $this->step_label,
            'generate_video' => (bool) $this->generate_video,
            'error_message' => $this->error_message,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

