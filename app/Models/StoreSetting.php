<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
