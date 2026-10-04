<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'whatsapp',
        'description',
        'address',
        'province_id',
        'city_id',
        'subdistrict_id',
    ];
}
