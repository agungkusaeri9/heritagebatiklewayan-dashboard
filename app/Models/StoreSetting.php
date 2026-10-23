<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'whatsapp',
        'phone',
        'email',
        'website',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'description',
        'address',
        'province_id',
        'city_id',
        'subdistrict_id',
        'privacy_policy',
        'policy',
        'cancellation_policy',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }
}
