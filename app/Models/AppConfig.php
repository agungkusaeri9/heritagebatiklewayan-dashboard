<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a configuration value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = static::query()->where('key', $key)->first();

        if (! $config || $config->value === null || $config->value === '') {
            return $default;
        }

        return $config->value;
    }

    /**
     * Set or update a configuration key and value.
     */
    public static function setValue(string $key, ?string $value, ?string $description = null): self
    {
        $attributes = ['value' => $value];
        if ($description !== null) {
            $attributes['description'] = $description;
        }

        return static::query()->updateOrCreate(
            ['key' => $key],
            $attributes
        );
    }
}
