<?php

namespace Database\Seeders;

use App\Models\VariantType;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;

class VariantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Ukuran',
                'slug' => 'ukuran',
                'description' => 'Pilihan ukuran pakaian standar nasional.',
                'is_active' => true,
                'sort_order' => 1,
                'values' => ['S', 'M', 'L', 'XL', 'XXL', 'All Size'],
            ],
            [
                'name' => 'Warna Dasar',
                'slug' => 'warna-dasar',
                'description' => 'Warna utama kain dan motif batik.',
                'is_active' => true,
                'sort_order' => 2,
                'values' => ['Coklat Sogan', 'Hitam Klasik', 'Biru Indigo', 'Merah Marun', 'Hijau Sage', 'Emas'],
            ],
            [
                'name' => 'Jenis Bahan',
                'slug' => 'jenis-bahan',
                'description' => 'Material kain yang digunakan pada pakaian.',
                'is_active' => true,
                'sort_order' => 3,
                'values' => ['Katun Primisima Halus', 'Sutra ATBM', 'Katun Dobby Timbul', 'Katun Mori'],
            ],
        ];

        foreach ($types as $item) {
            $values = $item['values'];
            unset($item['values']);

            $variantType = VariantType::firstOrCreate(
                ['slug' => $item['slug']],
                $item
            );

            foreach ($values as $index => $val) {
                VariantValue::firstOrCreate(
                    [
                        'variant_type_id' => $variantType->id,
                        'value' => $val,
                    ],
                    [
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}
