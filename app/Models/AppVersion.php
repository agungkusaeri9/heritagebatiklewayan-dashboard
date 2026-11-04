<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'version',
        'version_code',
        'min_version',
        'file_path',
        'file_name',
        'file_size',
        'release_notes',
        'is_latest',
        'is_force_update',
        'is_maintenance',
        'maintenance_message',
        'is_active',
        'released_at',
    ];

    protected $appends = [
        'file_url',
        'download_url',
    ];

    protected function casts(): array
    {
        return [
            'version_code' => 'integer',
            'file_size' => 'integer',
            'is_latest' => 'boolean',
            'is_force_update' => 'boolean',
            'is_maintenance' => 'boolean',
            'is_active' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (! $this->file_path || ! $this->id) {
            return null;
        }

        return route('api.v1.app-versions.download', $this->id);
    }

    public function markAsLatest(): void
    {
        static::where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_latest' => false]);

        $this->update([
            'is_latest' => true,
            'is_active' => true,
        ]);
    }
}
