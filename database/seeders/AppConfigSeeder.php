<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Seeder;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'rajaongkir_api_key',
                'value' => null,
                'description' => 'API Key untuk layanan integrasi RajaOngkir / Komerce',
            ],
            [
                'key' => 'rajaongkir_base_url',
                'value' => 'https://rajaongkir.komerce.id',
                'description' => 'Base URL endpoint API untuk layanan RajaOngkir / Komerce',
            ],
            [
                'key' => 'fashn_api_key',
                'value' => null,
                'description' => 'API Key untuk layanan integrasi Fashn AI',
            ],
            [
                'key' => 'fashn_api_base_url',
                'value' => 'https://api.fashn.ai',
                'description' => 'Base URL endpoint API untuk layanan Fashn AI',
            ],
            [
                'key' => 'geoapify_api_key',
                'value' => null,
                'description' => 'API Key untuk layanan Geocoding dan Peta Geoapify',
            ],
            [
                'key' => 'geoapify_base_url',
                'value' => 'https://api.geoapify.com',
                'description' => 'Base URL endpoint API untuk layanan Geocoding dan Peta Geoapify',
            ],
            [
                'key' => 'is_product_virtual_try_on',
                'value' => '1',
                'description' => 'Status aktif fitur Product Virtual Try-On',
            ],
            [
                'key' => 'is_product_virtual_try_on_photo',
                'value' => '1',
                'description' => 'Status aktif fitur Virtual Try-On mode Foto',
            ],
            [
                'key' => 'is_product_virtual_try_on_video',
                'value' => '0',
                'description' => 'Status aktif fitur Virtual Try-On mode Video',
            ],
            [
                'key' => 'virtual_try_on_daily_limit_per_user',
                'value' => '3',
                'description' => 'Batas maksimal percobaan virtual try-on per user per hari',
            ],
            [
                'key' => 'virtual_try_on_weekly_limit_all_users',
                'value' => '100',
                'description' => 'Batas kuota maksimal percobaan virtual try-on seluruh pengguna per minggu',
            ],
            [
                'key' => 'fashn_video_duration',
                'value' => '5',
                'description' => 'Durasi video generasi Fashn AI (5 atau 10 detik)',
            ],
            [
                'key' => 'fashn_video_resolution',
                'value' => '480p',
                'description' => 'Resolusi video generasi Fashn AI (480p, 720p, 1080p)',
            ],
        ];

        foreach ($configs as $config) {
            AppConfig::firstOrCreate(
                ['key' => $config['key']],
                [
                    'value' => $config['value'],
                    'description' => $config['description'],
                ]
            );
        }
    }
}
