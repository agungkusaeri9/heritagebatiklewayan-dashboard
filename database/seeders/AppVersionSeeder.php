<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        $sampleFilePath = 'apps/heritage-batik-lewayan-v1.0.0.apk';
        $sampleFileName = 'heritage-batik-lewayan-v1.0.0.apk';

        if (! Storage::disk('public')->exists($sampleFilePath)) {
            Storage::disk('public')->put($sampleFilePath, "PK\x03\x04Heritage Batik Lewayan Android APK v1.0.0");
        }

        $sampleFileSize = Storage::disk('public')->size($sampleFilePath);

        $versions = [
            [
                'name' => 'Heritage Batik Lewayan',
                'type' => 'android',
                'version' => '1.0.0',
                'version_code' => 1,
                'min_version' => '1.0.0',
                'file_path' => $sampleFilePath,
                'file_name' => $sampleFileName,
                'file_size' => $sampleFileSize,
                'release_notes' => "Rilis awal aplikasi Android Heritage Batik Lewayan:\n- Katalog produk batik lengkap\n- Fitur Virtual Try-On AI\n- Keranjang & Checkout dengan cek ongkir otomatis",
                'is_latest' => true,
                'is_force_update' => false,
                'is_maintenance' => false,
                'maintenance_message' => null,
                'is_active' => true,
                'released_at' => now(),
            ],
        ];

        foreach ($versions as $version) {
            $existing = AppVersion::where('type', $version['type'])
                ->where('version', $version['version'])
                ->first();

            if ($existing) {
                if (! $existing->file_path) {
                    $existing->update([
                        'file_path' => $version['file_path'],
                        'file_name' => $version['file_name'],
                        'file_size' => $version['file_size'],
                        'is_latest' => true,
                    ]);
                }
            } else {
                AppVersion::create($version);
            }
        }
    }
}
