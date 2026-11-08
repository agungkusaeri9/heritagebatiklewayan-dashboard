<?php

namespace App\Http\Resources;

use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppVersion
 */
class AppVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'version' => $this->version,
            'version_code' => (int) $this->version_code,
            'min_version' => $this->min_version,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size !== null ? (int) $this->file_size : null,
            'file_url' => $this->file_url,
            'download_url' => $this->download_url,
            'release_notes' => $this->release_notes,
            'is_latest' => (bool) $this->is_latest,
            'is_force_update' => (bool) $this->is_force_update,
            'is_maintenance' => (bool) $this->is_maintenance,
            'maintenance_message' => $this->maintenance_message,
            'is_active' => (bool) $this->is_active,
            'released_at' => $this->released_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
