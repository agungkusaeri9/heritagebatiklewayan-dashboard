<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catTulis = Category::where('slug', 'batik-tulis-premium')->first();
        $catCap = Category::where('slug', 'batik-cap-klasik')->first();
        $catKemeja = Category::where('slug', 'kemeja-pria-formal')->first();
        $catDress = Category::where('slug', 'dress-tunik-wanita')->first();
        $catKain = Category::where('slug', 'kain-panjang-jarik')->first();
        $catCouple = Category::where('slug', 'batik-couple-sarimbit')->first();

        $variantValues = VariantValue::all();
        $stores = Store::orderBy('id')->get();

        $products = [
            [
                'category_id' => $catKemeja?->id,
                'name' => 'Kemeja Batik Pria Lengan Panjang Motif Parang Rusak',
                'slug' => 'kemeja-batik-pria-lengan-panjang-motif-parang-rusak',
                'description' => 'Kemeja batik pria eksklusif berbahan Katun Primisima dengan lapisan furing lembut. Motif Parang Rusak melambangkan keberanian dan kepemimpinan.',
                'price' => 385000,
                'stock' => 45,
                'sku' => 'KMB-PRG-001',
                'weight' => 350,
                'is_virtual_try_on' => true,
                'is_active' => true,
            ],
            [
                'category_id' => $catKemeja?->id,
                'name' => 'Kemeja Batik Pria Slim Fit Motif Megamendung Cirebon',
                'slug' => 'kemeja-batik-pria-slim-fit-motif-megamendung-cirebon',
                'description' => 'Kemeja pria modern potongan slim fit dengan motif Megamendung gradasi biru elegan. Cocok untuk outfit kantor maupun acara semi formal.',
                'price' => 320000,
                'stock' => 30,
                'sku' => 'KMB-MGM-002',
                'weight' => 300,
                'is_virtual_try_on' => true,
                'is_active' => true,
            ],
            [
                'category_id' => $catDress?->id,
                'name' => 'Dress Batik Wanita Modern A-Line Motif Kawung',
                'slug' => 'dress-batik-wanita-modern-a-line-motif-kawung',
                'description' => 'Dress anggun motif Kawung klasik dengan sentuhan potongan A-Line modern yang memberikan siluet ramping dan elegan.',
                'price' => 450000,
                'stock' => 25,
                'sku' => 'DRS-KWG-001',
                'weight' => 400,
                'is_virtual_try_on' => true,
                'is_active' => true,
            ],
            [
                'category_id' => $catDress?->id,
                'name' => 'Tunik Batik Silk Wanita Motif Truntum Emas',
                'slug' => 'tunik-batik-silk-wanita-motif-truntum-emas',
                'description' => 'Tunik premium wanita berbahan dobby silk lembut berkilau dengan aksen motif Truntum kencana emas bernuansa mewah.',
                'price' => 495000,
                'stock' => 20,
                'sku' => 'TNK-TRN-002',
                'weight' => 350,
                'is_virtual_try_on' => true,
                'is_active' => true,
            ],
            [
                'category_id' => $catTulis?->id,
                'name' => 'Kain Batik Tulis Asli Sutra ATBM Motif Sekar Jagad',
                'slug' => 'kain-batik-tulis-asli-sutra-atbm-motif-sekar-jagad',
                'description' => 'Mahakarya kain batik tulis canting 100% tangan di atas sutra ATBM tenun manual. Waktu pengerjaan 3 bulan dengan motif Sekar Jagad kaya makna keberagaman.',
                'price' => 2750000,
                'stock' => 5,
                'sku' => 'TLS-SKJ-001',
                'weight' => 500,
                'is_virtual_try_on' => false,
                'is_active' => true,
            ],
            [
                'category_id' => $catCap?->id,
                'name' => 'Kain Jarik Batik Cap Solo Motif Sidomukti Sogan',
                'slug' => 'kain-jarik-batik-cap-solo-motif-sidomukti-sogan',
                'description' => 'Kain batik cap tradisi Solo dengan pewarnaan sogan klasik motif Sidomukti yang melambangkan kemakmuran dan kedamaian hidup.',
                'price' => 225000,
                'stock' => 60,
                'sku' => 'CAP-SDM-001',
                'weight' => 400,
                'is_virtual_try_on' => false,
                'is_active' => true,
            ],
            [
                'category_id' => $catCouple?->id,
                'name' => 'Set Sarimbit Batik Pasangan Motif Semen Rante',
                'slug' => 'set-sarimbit-batik-pasangan-motif-semen-rante',
                'description' => 'Sepasang baju kemeja pria dan gamis dress wanita dengan motif selaras Semen Rante melambangkan ikatan cinta yang kokoh.',
                'price' => 780000,
                'stock' => 15,
                'sku' => 'SRM-SMR-001',
                'weight' => 800,
                'is_virtual_try_on' => true,
                'is_active' => true,
            ],
        ];

        foreach ($products as $index => $prodData) {
            $assignedStore = $stores->isNotEmpty()
                ? $stores[$index % $stores->count()]
                : null;

            $product = Product::firstOrCreate(
                ['slug' => $prodData['slug']],
                array_merge($prodData, [
                    'store_id' => $assignedStore?->id,
                ])
            );

            if ($product->store_id === null && $assignedStore !== null) {
                $product->update(['store_id' => $assignedStore->id]);
            }

            // Sync random variant values if not attached
            if ($variantValues->isNotEmpty() && $product->variantValues()->count() === 0) {
                $randomValues = $variantValues->random(min(4, $variantValues->count()))->pluck('id')->all();
                $product->variantValues()->sync($randomValues);
            }
        }
    }
}
