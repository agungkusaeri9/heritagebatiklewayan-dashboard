<?php

namespace App\Http\Resources;

use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subdistrict
 */
class SubdistrictResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->subdistrict_id,
            'city_id' => (int) $this->city_id,
            'name' => (string) $this->subdistrict_name,
        ];
    }
}
