<?php

namespace App\Http\Resources;

use App\Models\VirtualTryOnModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VirtualTryOnModel
 */
class VirtualTryOnModelResource extends JsonResource
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
            'gender' => $this->gender,
            'photo_url' => $this->photo_url,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
