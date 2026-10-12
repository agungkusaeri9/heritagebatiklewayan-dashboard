<?php

namespace App\Http\Resources;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CustomerAddress
 */
class CustomerAddressResource extends JsonResource
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
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'full_address' => $this->full_address,
            'province' => $this->whenLoaded('province', fn () => $this->province ? [
                'id' => $this->province->province_id,
                'name' => $this->province->province_name,
            ] : null),
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->city_id,
                'name' => $this->city->city_name,
            ] : null),
            'subdistrict' => $this->whenLoaded('subdistrict', fn () => $this->subdistrict ? [
                'id' => $this->subdistrict->subdistrict_id,
                'name' => $this->subdistrict->subdistrict_name,
            ] : null),
            'postal_code' => $this->postal_code,
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
