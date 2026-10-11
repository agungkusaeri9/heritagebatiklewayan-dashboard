<?php

namespace App\Http\Resources;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StoreSetting
 */
class StoreSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'description' => $this->description,
            'contact' => [
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'email' => $this->email,
                'website' => $this->website,
            ],
            'social_media' => [
                'facebook_url' => $this->facebook_url,
                'twitter_url' => $this->twitter_url,
                'instagram_url' => $this->instagram_url,
            ],
            'address' => $this->address,
            'policies' => [
                'privacy_policy' => $this->privacy_policy ?? '',
                'policy' => $this->policy ?? '',
            ],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
