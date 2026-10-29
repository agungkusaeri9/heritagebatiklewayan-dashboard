<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVirtualTryOn extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'virtual_try_on_model_id',
        'user_id',
        'gender',
        'photo',
        'video',
        'status',
        'progress',
        'step_label',
        'task_id_photo',
        'task_id_video',
        'generate_video',
        'error_message',
    ];

    protected $casts = [
        'progress' => 'integer',
        'generate_video' => 'boolean',
    ];

    protected $appends = [
        'photo_url',
        'video_url',
        'is_default',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function virtualModel(): BelongsTo
    {
        return $this->belongsTo(VirtualTryOnModel::class, 'virtual_try_on_model_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video) {
            return null;
        }

        if (Str::startsWith($this->video, ['http://', 'https://', '/'])) {
            return $this->video;
        }

        return Storage::disk('public')->url($this->video);
    }

    public function getIsDefaultAttribute(): bool
    {
        return is_null($this->user_id);
    }
}
