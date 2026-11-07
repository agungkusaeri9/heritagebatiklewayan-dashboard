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
        $photoUrl = $this->photo_url;
        if ($this->photo && ! \Illuminate\Support\Str::startsWith($this->photo, ['http://', 'https://'])) {
            $photoUrl = $request->getSchemeAndHttpHost() . '/storage/' . ltrim($this->photo, '/');
        }
        $videoUrl = $this->video_url;
        if ($this->video && ! \Illuminate\Support\Str::startsWith($this->video, ['http://', 'https://'])) {
            $videoUrl = $request->getSchemeAndHttpHost() . '/storage/' . ltrim($this->video, '/');
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'price' => (float) $this->product->price,
                'sku' => $this->product->sku,
                'primary_image_url' => ($this->product->primary_image && ! \Illuminate\Support\Str::startsWith($this->product->primary_image, ['http://', 'https://']))
                    ? $request->getSchemeAndHttpHost() . '/storage/' . ltrim($this->product->primary_image, '/')
                    : $this->product->primary_image_url,
            ]),
            'virtual_try_on_model_id' => $this->virtual_try_on_model_id,
            'virtual_model' => $this->whenLoaded('virtualModel', fn () => [
                'id' => $this->virtualModel->id,
                'name' => $this->virtualModel->name,
                'gender' => $this->virtualModel->gender,
                'photo_url' => ($this->virtualModel->photo && ! \Illuminate\Support\Str::startsWith($this->virtualModel->photo, ['http://', 'https://']))
                    ? $request->getSchemeAndHttpHost() . '/storage/' . ltrim($this->virtualModel->photo, '/')
                    : $this->virtualModel->photo_url,
            ]),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'gender' => $this->gender ?? $this->virtualModel?->gender,
            'photo_url' => $photoUrl,
            'video_url' => $videoUrl,
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
