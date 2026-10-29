<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VirtualTryOnModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function tryOns(): HasMany
    {
        return $this->hasMany(ProductVirtualTryOn::class, 'virtual_try_on_model_id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        if (Str::startsWith($this->photo, ['http://', 'https://', '/'])) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
