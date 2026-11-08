<?php

namespace Database\Factories;

use App\Models\AppVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppVersionFactory extends Factory
{
    protected $model = AppVersion::class;

    public function definition(): array
    {
        return [
            'name' => 'Heritage Batik Lewayan',
            'type' => 'android',
            'version' => '1.0.0',
            'version_code' => 1,
            'min_version' => '1.0.0',
            'file_path' => 'apps/heritage-batik-lewayan-v1.0.0.apk',
            'file_name' => 'heritage-batik-lewayan-v1.0.0.apk',
            'file_size' => 25165824,
            'release_notes' => 'Rilis perdana aplikasi Android Heritage Batik Lewayan.',
            'is_latest' => true,
            'is_force_update' => false,
            'is_maintenance' => false,
            'maintenance_message' => null,
            'is_active' => true,
            'released_at' => now(),
        ];
    }
}
