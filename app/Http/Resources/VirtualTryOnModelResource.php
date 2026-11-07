<?php

namespace App\Http\Resources;

use App\Models\VirtualTryOnModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $photoUrl = $this->photo_url;
        if ($this->photo && ! Str::startsWith($this->photo, ['http://', 'https://'])) {
            $photoUrl = $request->getSchemeAndHttpHost() . '/storage/' . ltrim($this->photo, '/');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'photo_url' => $photoUrl,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
