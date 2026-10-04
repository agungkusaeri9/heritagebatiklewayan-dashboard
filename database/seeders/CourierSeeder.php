<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Courier;

class CourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $couriers = [
            [
                'name' => 'Jalur Nugraha Ekakurir (JNE)',
                'code' => 'jne',
                'description' => 'Layanan pengiriman JNE (Reguler, OKE, YES)',
                'is_active' => true,
            ],
            [
                'name' => 'POS Indonesia',
                'code' => 'pos',
                'description' => 'Layanan pengiriman PT Pos Indonesia',
                'is_active' => true,
            ],
            [
                'name' => 'Citra Van Titipan Kilat (TIKI)',
                'code' => 'tiki',
                'description' => 'Layanan pengiriman TIKI',
                'is_active' => true,
            ],
        ];

        foreach ($couriers as $courier) {
            Courier::updateOrCreate(
                ['code' => $courier['code']],
                $courier
            );
        }
    }
}
