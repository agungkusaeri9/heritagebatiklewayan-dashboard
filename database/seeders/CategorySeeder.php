<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Batik Tulis Premium',
                'slug' => 'batik-tulis-premium',
                'description' => 'Koleksi mahakarya batik tulis handmade asli pengrajin nusantara dengan pewarnaan alami.',
                'is_active' => true,
            ],
            [
                'name' => 'Batik Cap Klasik',
                'slug' => 'batik-cap-klasik',
                'description' => 'Batik cap berkualitas dengan ragam motif tradisional khas Solo, Jogja, dan Cirebon.',
                'is_active' => true,
            ],
            [
                'name' => 'Kemeja Pria Formal',
                'slug' => 'kemeja-pria-formal',
                'description' => 'Kemeja batik pria lengan panjang dan pendek berlapis furing nyaman untuk kerja dan acara formal.',
                'is_active' => true,
            ],
            [
                'name' => 'Dress & Tunik Wanita',
                'slug' => 'dress-tunik-wanita',
                'description' => 'Koleksi busana batik modern wanita seperti dress, tunik, outer, dan blouse elegan.',
                'is_active' => true,
            ],
            [
                'name' => 'Kain Panjang & Jarik',
                'slug' => 'kain-panjang-jarik',
                'description' => 'Kain batik meteran dan jarik tradisional untuk upacara adat, bahan kebaya, atau koleksi.',
                'is_active' => true,
            ],
            [
                'name' => 'Batik Couple & Sarimbit',
                'slug' => 'batik-couple-sarimbit',
                'description' => 'Setelan busana batik serasi pasangan dan keluarga untuk perayaan pesta dan kondangan.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
