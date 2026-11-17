<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan role 'admin store' tersedia
        $adminStoreRole = Role::where('name', 'admin store')->where('guard_name', 'web')->first();
        if (! $adminStoreRole) {
            $this->call(RolePermissionSeeder::class);
            $adminStoreRole = Role::where('name', 'admin store')->where('guard_name', 'web')->first();
        }

        // 2. Pastikan kategori dan varian tersedia
        if (Category::count() === 0) {
            $this->call(CategorySeeder::class);
        }
        if (VariantValue::count() === 0) {
            $this->call(VariantTypeSeeder::class);
        }

        $catTulis = Category::where('slug', 'batik-tulis-premium')->first();
        $catCap = Category::where('slug', 'batik-cap-klasik')->first();
        $catKemeja = Category::where('slug', 'kemeja-pria-formal')->first();
        $catDress = Category::where('slug', 'dress-tunik-wanita')->first();
        $catKain = Category::where('slug', 'kain-panjang-jarik')->first();
        $catCouple = Category::where('slug', 'batik-couple-sarimbit')->first();

        $variantValues = VariantValue::all();
        $password = Hash::make('password');

        $storesData = [
            // Store 1: Batik Putra Laweyan
            [
                'user' => [
                    'name' => 'Admin Batik Putra Laweyan',
                    'username' => 'admin_putralaweyan',
                    'email' => 'putra.laweyan@batik.com',
                ],
                'store' => [
                    'name' => 'Batik Putra Laweyan',
                    'email' => 'info@putralaweyan.com',
                    'phone' => '0271714856',
                    'whatsapp' => '6281226123001',
                    'address' => 'Jl. Sidoluhur No. 32, Kampung Batik Laweyan, Surakarta',
                    'description' => 'Sentra batik tulis dan cap Laweyan dengan spesialisasi kemeja pria formal dan busana sutra primisima.',
                ],
                'products' => [
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Putra Katun Primisima Parang Kusumo',
                        'slug' => 'kemeja-batik-putra-katun-primisima-parang-kusumo',
                        'description' => 'Kemeja pria lengan panjang berbahan katun primisima halus dengan furing arrow lembut. Menampilkan motif klasik Parang Kusumo khas Surakarta.',
                        'price' => 375000,
                        'stock' => 35,
                        'sku' => 'BPL-PRD-001',
                        'weight' => 350,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Putra Slim Fit Truntum Garuda',
                        'slug' => 'kemeja-batik-putra-slim-fit-truntum-garuda',
                        'description' => 'Kemeja potongan modern slim fit bermotif Truntum berpadu siluet Garuda gagah. Memberikan kesan maskulin dan berwibawa.',
                        'price' => 395000,
                        'stock' => 28,
                        'sku' => 'BPL-PRD-002',
                        'weight' => 320,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Sutra Lengan Panjang Sidomukti',
                        'slug' => 'kemeja-batik-sutra-lengan-panjang-sidomukti',
                        'description' => 'Kemeja sutra halus ATBM berlapisan furing sutra sejuk dengan motif filosofis Sidomukti yang melambangkan kemakmuran abadi.',
                        'price' => 850000,
                        'stock' => 15,
                        'sku' => 'BPL-PRD-003',
                        'weight' => 300,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Kain Batik Tulis Putra Mahakarya Sekar Jagad',
                        'slug' => 'kain-batik-tulis-putra-mahakarya-sekar-jagad',
                        'description' => 'Mahakarya batik tulis canting 100% buatan tangan pengrajin senior Laweyan. Berbahan katun bendera dengan proses 45 hari.',
                        'price' => 2100000,
                        'stock' => 8,
                        'sku' => 'BPL-PRD-004',
                        'weight' => 450,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Kain Panjang Putra Sogan Ceplok Kasunanan',
                        'slug' => 'kain-panjang-putra-sogan-ceplok-kasunanan',
                        'description' => 'Kain panjang jarik sogan cokelat khas Keraton Solo motif Ceplok, cocok untuk upacara adat dan bahan kebaya resmi.',
                        'price' => 285000,
                        'stock' => 40,
                        'sku' => 'BPL-PRD-005',
                        'weight' => 400,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Batik Cap Putra Klasik Semen Rante',
                        'slug' => 'batik-cap-putra-klasik-semen-rante',
                        'description' => 'Batik cap berkualitas ekspor berbahan katun primisima dingin dengan pola Semen Rante yang rapat dan rapi.',
                        'price' => 240000,
                        'stock' => 50,
                        'sku' => 'BPL-PRD-006',
                        'weight' => 380,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Dress Modern Putra A-Line Kawung Manis',
                        'slug' => 'dress-modern-putra-a-line-kawung-manis',
                        'description' => 'Dress wanita potongan A-Line yang modis dengan motif Kawung Manis, nyaman dikenakan seharian untuk kerja maupun pesta.',
                        'price' => 420000,
                        'stock' => 22,
                        'sku' => 'BPL-PRD-007',
                        'weight' => 420,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Batik Couple Putra Sekar Melati',
                        'slug' => 'set-batik-couple-putra-sekar-melati',
                        'description' => 'Pasangan serasi kemeja pria dan gamis wanita dengan nuansa sogan motif Sekar Melati, sempurna untuk perhelatan keluarga.',
                        'price' => 760000,
                        'stock' => 14,
                        'sku' => 'BPL-PRD-008',
                        'weight' => 750,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Putra Lengan Pendek Megamendung',
                        'slug' => 'kemeja-batik-putra-lengan-pendek-megamendung',
                        'description' => 'Kemeja kasual pria lengan pendek motif Megamendung gradasi biru laut. Ringan, adem, dan menyerap keringat.',
                        'price' => 265000,
                        'stock' => 45,
                        'sku' => 'BPL-PRD-009',
                        'weight' => 280,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Tunik Batik Katun Putra Dobby Tambal',
                        'slug' => 'tunik-batik-katun-putra-dobby-tambal',
                        'description' => 'Tunik wanita berbahan katun dobby timbul motif Tambal penuh filosofi memperbaiki diri. Berpotongan loose fit nyaman.',
                        'price' => 380000,
                        'stock' => 30,
                        'sku' => 'BPL-PRD-010',
                        'weight' => 360,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                ],
            ],

            // Store 2: Batik Mahkota Laweyan
            [
                'user' => [
                    'name' => 'Admin Mahkota Laweyan',
                    'username' => 'admin_mahkotalaweyan',
                    'email' => 'mahkota.laweyan@batik.com',
                ],
                'store' => [
                    'name' => 'Batik Mahkota Laweyan',
                    'email' => 'info@mahkotabatik.com',
                    'phone' => '0271714421',
                    'whatsapp' => '6281226123002',
                    'address' => 'Jl. Sayangan Wetan No. 54, Kampung Batik Laweyan, Surakarta',
                    'description' => 'Pelopor inovasi batik abstrak kontemporer serta batik tulis canting bersertifikasi warisan budaya nusantara.',
                ],
                'products' => [
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Kain Batik Tulis Mahkota Sutra ATBM Sekar Jagad',
                        'slug' => 'kain-batik-tulis-mahkota-sutra-atbm-sekar-jagad',
                        'description' => 'Kain panjang sutra ATBM asli dengan batik tulis canting motif Sekar Jagad berwarna sogan kencana khas Mahkota Laweyan.',
                        'price' => 2950000,
                        'stock' => 6,
                        'sku' => 'BML-PRD-001',
                        'weight' => 480,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Kain Batik Tulis Mahkota Kontemporer Abstrak Merah',
                        'slug' => 'kain-batik-tulis-mahkota-kontemporer-abstrak-merah',
                        'description' => 'Batik lukis kontemporer inovasi studio seni Mahkota dengan semburat warna merah marun berani dan berkelas.',
                        'price' => 1750000,
                        'stock' => 10,
                        'sku' => 'BML-PRD-002',
                        'weight' => 420,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Mahkota Lengan Panjang Udan Liris',
                        'slug' => 'kemeja-batik-mahkota-lengan-panjang-udan-liris',
                        'description' => 'Kemeja batik formal pria motif garis diagonal Udan Liris yang sarat berkah kemakmuran, berfuring nyaman.',
                        'price' => 460000,
                        'stock' => 25,
                        'sku' => 'BML-PRD-003',
                        'weight' => 340,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Dress Batik Silk Mahkota Peacock Cantik',
                        'slug' => 'dress-batik-silk-mahkota-peacock-cantik',
                        'description' => 'Gaun terusan berbahan sutra licin motif merak anggun berpadu warna teal emas yang mewah untuk resepsi pernikahan.',
                        'price' => 680000,
                        'stock' => 18,
                        'sku' => 'BML-PRD-004',
                        'weight' => 380,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Tunik Batik Tulis Mahkota Floral Pastel',
                        'slug' => 'tunik-batik-tulis-mahkota-floral-pastel',
                        'description' => 'Tunik tulis sentuhan modern bernuansa warna pastel lembut, aksen kancing depan tersembunyi yang anggun.',
                        'price' => 520000,
                        'stock' => 20,
                        'sku' => 'BML-PRD-005',
                        'weight' => 350,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Jarik Batik Klasik Mahkota Motif Lereng Prabu',
                        'slug' => 'jarik-batik-klasik-mahkota-motif-lereng-prabu',
                        'description' => 'Kain jarik tradisional motif Lereng Prabu agung dengan pewarnaan malam alami berkualitas tinggi.',
                        'price' => 310000,
                        'stock' => 30,
                        'sku' => 'BML-PRD-006',
                        'weight' => 420,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Kain Batik Cap Mahkota Tiga Negeri Tradisional',
                        'slug' => 'kain-batik-cap-mahkota-tiga-negeri-tradisional',
                        'description' => 'Batik cap harmoni tiga budaya dengan palet merah, biru, dan sogan yang memukau pencinta batik nusantara.',
                        'price' => 275000,
                        'stock' => 38,
                        'sku' => 'BML-PRD-007',
                        'weight' => 390,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Sarimbit Mahkota Royal Kencana',
                        'slug' => 'set-sarimbit-mahkota-royal-kencana',
                        'description' => 'Busana pasangan mewah motif ornamen emas kencana dipadukan katun dobby jacquard berkualitas premium.',
                        'price' => 890000,
                        'stock' => 12,
                        'sku' => 'BML-PRD-008',
                        'weight' => 780,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Blouse Batik Mahkota Modern Asimetris',
                        'slug' => 'blouse-batik-mahkota-modern-asimetris',
                        'description' => 'Blouse wanita berpotongan asimetris kontemporer, kombinasi batik tulis dan kain katun polos warna sage.',
                        'price' => 345000,
                        'stock' => 26,
                        'sku' => 'BML-PRD-009',
                        'weight' => 300,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Pria Mahkota Slim Fit Sido Asih',
                        'slug' => 'kemeja-batik-pria-mahkota-slim-fit-sido-asih',
                        'description' => 'Kemeja batik slim fit motif Sido Asih bernuansa romantis dan hangat, berlapis tricot halus tanpa gerah.',
                        'price' => 415000,
                        'stock' => 32,
                        'sku' => 'BML-PRD-010',
                        'weight' => 330,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                ],
            ],

            // Store 3: Batik Cokrokusumo Laweyan
            [
                'user' => [
                    'name' => 'Admin Cokrokusumo',
                    'username' => 'admin_cokrokusumo',
                    'email' => 'cokrokusumo.laweyan@batik.com',
                ],
                'store' => [
                    'name' => 'Batik Cokrokusumo Laweyan',
                    'email' => 'info@cokrokusumo.com',
                    'phone' => '0271723490',
                    'whatsapp' => '6281226123003',
                    'address' => 'Jl. Dr. Rajiman No. 512, Kampung Batik Laweyan, Surakarta',
                    'description' => 'Pusat konservasi dan produksi motif klasik keraton Solo, pewarnaan sogan alami, dan busana pakem adat Jawa.',
                ],
                'products' => [
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Kain Jarik Cokrokusumo Sogan Sidomukti Ageng',
                        'slug' => 'kain-jarik-cokrokusumo-sogan-sidomukti-ageng',
                        'description' => 'Kain batik jarik sogan legendaris Keraton Solo, warna cokelat tembaga kaya pigmen kulit pohon tingi alami.',
                        'price' => 350000,
                        'stock' => 45,
                        'sku' => 'BCK-PRD-001',
                        'weight' => 420,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Kain Jarik Pengantin Cokrokusumo Sido Luhur',
                        'slug' => 'kain-jarik-pengantin-cokrokusumo-sido-luhur',
                        'description' => 'Kain jarik motif Sido Luhur yang agung untuk prosesi pernikahan adat Jawa panggih dan siraman.',
                        'price' => 480000,
                        'stock' => 20,
                        'sku' => 'BCK-PRD-002',
                        'weight' => 440,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Cokrokusumo Sogan Parang Barong',
                        'slug' => 'kemeja-batik-cokrokusumo-sogan-parang-barong',
                        'description' => 'Kemeja batik motif Parang Barong berukuran besar yang melambangkan kepemimpinan dan integritas tinggi.',
                        'price' => 520000,
                        'stock' => 24,
                        'sku' => 'BCK-PRD-003',
                        'weight' => 360,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Klasik Cokrokusumo Wahyu Tumurun',
                        'slug' => 'kemeja-batik-klasik-cokrokusumo-wahyu-tumurun',
                        'description' => 'Kemeja batik formal pria motif Wahyu Tumurun yang dipercaya membawa berkah dan petunjuk hidup luhur.',
                        'price' => 450000,
                        'stock' => 30,
                        'sku' => 'BCK-PRD-004',
                        'weight' => 350,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Batik Tulis Halus Cokrokusumo Bondhet Canting',
                        'slug' => 'batik-tulis-halus-cokrokusumo-bondhet-canting',
                        'description' => 'Batik tulis rumit motif Bondhet dengan isen-isen cecek halus, dikerjakan selama 3 bulan oleh maestro Laweyan.',
                        'price' => 2600000,
                        'stock' => 5,
                        'sku' => 'BCK-PRD-005',
                        'weight' => 460,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Batik Cap Tradisional Cokrokusumo Gringsing',
                        'slug' => 'batik-cap-tradisional-cokrokusumo-gringsing',
                        'description' => 'Kain batik cap motif sisik ikan Gringsing kuno yang bermakna tolak bala dan perlindungan keluarga.',
                        'price' => 230000,
                        'stock' => 55,
                        'sku' => 'BCK-PRD-006',
                        'weight' => 380,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Sarimbit Cokrokusumo Manten Klasik',
                        'slug' => 'set-sarimbit-cokrokusumo-manten-klasik',
                        'description' => 'Setelan pasangan kemeja pria dan kebaya kurung batik sogan klasik yang anggun memancarkan kharisma ningrat.',
                        'price' => 820000,
                        'stock' => 16,
                        'sku' => 'BCK-PRD-007',
                        'weight' => 800,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Outer Batik Cokrokusumo Panjang Kawung Picis',
                        'slug' => 'outer-batik-cokrokusumo-panjang-kawung-picis',
                        'description' => 'Outer kardigan panjang wanita dengan motif Kawung Picis mini bernuansa etnik kontemporer berpadu katun prima.',
                        'price' => 360000,
                        'stock' => 28,
                        'sku' => 'BCK-PRD-008',
                        'weight' => 370,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Dress Batik Sogan Cokrokusumo Anggun Keraton',
                        'slug' => 'dress-batik-sogan-cokrokusumo-anggun-keraton',
                        'description' => 'Gaun midi wanita dengan aksen lipit depan bermotif sogan keraton, elegan untuk acara pesta dan kantor.',
                        'price' => 440000,
                        'stock' => 22,
                        'sku' => 'BCK-PRD-009',
                        'weight' => 410,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Selendang Batik Tulis Cokrokusumo Sutra Halus',
                        'slug' => 'selendang-batik-tulis-cokrokusumo-sutra-halus',
                        'description' => 'Selendang bahu wanita bahan sutra berhias rumbai etnik dan ornamen batik tulis halus bernilai seni tinggi.',
                        'price' => 750000,
                        'stock' => 18,
                        'sku' => 'BCK-PRD-010',
                        'weight' => 220,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                ],
            ],

            // Store 4: Batik Merak Manis Laweyan
            [
                'user' => [
                    'name' => 'Admin Merak Manis',
                    'username' => 'admin_merakmanis',
                    'email' => 'merakmanis.laweyan@batik.com',
                ],
                'store' => [
                    'name' => 'Batik Merak Manis Laweyan',
                    'email' => 'info@merakmanis.com',
                    'phone' => '0271718872',
                    'whatsapp' => '6281226123004',
                    'address' => 'Jl. Tiga Negeri No. 18, Kampung Batik Laweyan, Surakarta',
                    'description' => 'Spesialis busana batik modern wanita, dress tunik sutra dobby, dan setelan sarimbit elegan pesta keluarga.',
                ],
                'products' => [
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Dress Batik Silk Merak Manis Truntum Emas',
                        'slug' => 'dress-batik-silk-merak-manis-truntum-emas',
                        'description' => 'Dress terusan sutra mengkilap mewah dengan motif Truntum bertabur prada emas nan gemerlap.',
                        'price' => 595000,
                        'stock' => 24,
                        'sku' => 'BMM-PRD-001',
                        'weight' => 390,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Tunik Batik Merak Manis Dobby Buketan',
                        'slug' => 'tunik-batik-merak-manis-dobby-buketan',
                        'description' => 'Tunik wanita katun dobby tekstur timbul motif Buketan kembang Eropa berpadu warna pastel anggun.',
                        'price' => 430000,
                        'stock' => 32,
                        'sku' => 'BMM-PRD-002',
                        'weight' => 340,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Gamis Batik Merak Manis Kombinasi Brokat',
                        'slug' => 'gamis-batik-merak-manis-kombinasi-brokat',
                        'description' => 'Gamis muslimah anggun memadukan kain batik cap halus dan renda brokat prancis di dada dan pergelangan.',
                        'price' => 650000,
                        'stock' => 19,
                        'sku' => 'BMM-PRD-003',
                        'weight' => 580,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Sarimbit Merak Manis Pesta Lavender',
                        'slug' => 'set-sarimbit-merak-manis-pesta-lavender',
                        'description' => 'Baju couple pesta keluarga warna ungu lavender lembut bermotif flora Laweyan eksklusif dan menawan.',
                        'price' => 840000,
                        'stock' => 15,
                        'sku' => 'BMM-PRD-004',
                        'weight' => 770,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Couple Merak Manis Kemeja Rok Lilit',
                        'slug' => 'set-couple-merak-manis-kemeja-rok-lilit',
                        'description' => 'Kombinasi modern kemeja pria pendek dan rok lilit instan wanita yang praktis dipakai saat kondangan.',
                        'price' => 620000,
                        'stock' => 20,
                        'sku' => 'BMM-PRD-005',
                        'weight' => 620,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Pria Merak Manis Lengan Panjang Gurda',
                        'slug' => 'kemeja-pria-merak-manis-lengan-panjang-gurda',
                        'description' => 'Kemeja pria motif sayap Garuda kepak emas dengan bahan primisima sanforized anti kusut dan tidak ciut.',
                        'price' => 380000,
                        'stock' => 36,
                        'sku' => 'BMM-PRD-006',
                        'weight' => 340,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Pria Merak Manis Slim Fit Katun Halus',
                        'slug' => 'kemeja-pria-merak-manis-slim-fit-katun-halus',
                        'description' => 'Kemeja kerja pria kekinian beraksen kerah shanghai motif ornamen merak geometris modern.',
                        'price' => 340000,
                        'stock' => 40,
                        'sku' => 'BMM-PRD-007',
                        'weight' => 310,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Kain Batik Cap Merak Manis Pastel Alam',
                        'slug' => 'kain-batik-cap-merak-manis-pastel-alam',
                        'description' => 'Kain bahan meteran batik cap pewarnaan alam ramah lingkungan dengan corak dedaunan lembut.',
                        'price' => 210000,
                        'stock' => 50,
                        'sku' => 'BMM-PRD-008',
                        'weight' => 370,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Batik Tulis Sutra Merak Manis Maharani',
                        'slug' => 'batik-tulis-sutra-merak-manis-maharani',
                        'description' => 'Koleksi istimewa kain batik tulis sutra krep motif Maharani dengan warna gradasi emas dan merah anggur.',
                        'price' => 2450000,
                        'stock' => 7,
                        'sku' => 'BMM-PRD-009',
                        'weight' => 460,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Kain Selendang Merak Manis Sekar Melati',
                        'slug' => 'kain-selendang-merak-manis-sekar-melati',
                        'description' => 'Selendang bahu sifon sutra beraksen batik canting Sekar Melati harum yang menambah pesona kebaya adat.',
                        'price' => 320000,
                        'stock' => 25,
                        'sku' => 'BMM-PRD-010',
                        'weight' => 210,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                ],
            ],

            // Store 5: Batik Al-Huda Laweyan
            [
                'user' => [
                    'name' => 'Admin Batik Al-Huda',
                    'username' => 'admin_alhudalaweyan',
                    'email' => 'alhuda.laweyan@batik.com',
                ],
                'store' => [
                    'name' => 'Batik Al-Huda Laweyan',
                    'email' => 'info@alhudabatik.com',
                    'phone' => '0271719933',
                    'whatsapp' => '6281226123005',
                    'address' => 'Jl. Jongke No. 27, Kampung Batik Laweyan, Surakarta',
                    'description' => 'Produsen batik cap dan kombinasi tulis kualitas ekspor dengan bahan primisima ramah lingkungan.',
                ],
                'products' => [
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Batik Cap Al-Huda Kombinasi Tulis Parang Klithik',
                        'slug' => 'batik-cap-alhuda-kombinasi-tulis-parang-klithik',
                        'description' => 'Kain batik cap presisi tinggi dengan sentuhan isen-isen canting tulis motif Parang Klithik yang anggun.',
                        'price' => 310000,
                        'stock' => 42,
                        'sku' => 'BAH-PRD-001',
                        'weight' => 390,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCap?->id,
                        'name' => 'Batik Cap Primisima Al-Huda Babon Angrem',
                        'slug' => 'batik-cap-primisima-alhuda-babon-angrem',
                        'description' => 'Kain batik cap katun primisima motif Babon Angrem lambang kasih sayang ibu yang tulus dan menentramkan.',
                        'price' => 260000,
                        'stock' => 48,
                        'sku' => 'BAH-PRD-002',
                        'weight' => 380,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Al-Huda Kantoran Lengan Pendek',
                        'slug' => 'kemeja-batik-alhuda-kantoran-lengan-pendek',
                        'description' => 'Kemeja batik katun prima lengan pendek dengan saku paspol rapi, dirancang khusus untuk kenyamanan kerja harian.',
                        'price' => 245000,
                        'stock' => 50,
                        'sku' => 'BAH-PRD-003',
                        'weight' => 290,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKemeja?->id,
                        'name' => 'Kemeja Batik Pria Al-Huda Satrio Manah',
                        'slug' => 'kemeja-batik-pria-alhuda-satrio-manah',
                        'description' => 'Kemeja batik lengan panjang motif Satrio Manah yang gagah berfuring katun sejuk anti gerah.',
                        'price' => 390000,
                        'stock' => 30,
                        'sku' => 'BAH-PRD-004',
                        'weight' => 350,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Tunik Batik Al-Huda Casual Kopi Pecah',
                        'slug' => 'tunik-batik-alhuda-casual-kopi-pecah',
                        'description' => 'Tunik batik katun motif modern Kopi Pecah berwarna terra cotta alami, cocok dipadankan celana kulot.',
                        'price' => 360000,
                        'stock' => 35,
                        'sku' => 'BAH-PRD-005',
                        'weight' => 330,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Dress Katun Al-Huda Motif Sirkuit Modern',
                        'slug' => 'dress-katun-alhuda-motif-sirkuit-modern',
                        'description' => 'Dress santai wanita berbahan katun jepang kombinasi batik cap motif geometris modern kekinian.',
                        'price' => 410000,
                        'stock' => 26,
                        'sku' => 'BAH-PRD-006',
                        'weight' => 390,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catKain?->id,
                        'name' => 'Kain Jarik Al-Huda Sogan Truntum Sri',
                        'slug' => 'kain-jarik-alhuda-sogan-truntum-sri',
                        'description' => 'Kain panjang jarik klasik motif Truntum kencana sogan khas Laweyan untuk kelengkapan busana beskap dan kebaya.',
                        'price' => 295000,
                        'stock' => 38,
                        'sku' => 'BAH-PRD-007',
                        'weight' => 410,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catTulis?->id,
                        'name' => 'Batik Tulis Al-Huda Sutra Sido Drajat',
                        'slug' => 'batik-tulis-alhuda-sutra-sido-drajat',
                        'description' => 'Kain batik tulis eksklusif sutra tenun manual motif Sido Drajat lambang derajat dan kemuliaan tinggi.',
                        'price' => 2350000,
                        'stock' => 6,
                        'sku' => 'BAH-PRD-008',
                        'weight' => 470,
                        'is_virtual_try_on' => false,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catCouple?->id,
                        'name' => 'Set Sarimbit Keluarga Al-Huda Hari Raya',
                        'slug' => 'set-sarimbit-keluarga-alhuda-hari-raya',
                        'description' => 'Setelan busana keluarga serasi ayah dan ibu bermotif bunga sekar manggar warna hijau zamrud berkilau.',
                        'price' => 870000,
                        'stock' => 14,
                        'sku' => 'BAH-PRD-009',
                        'weight' => 790,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                    [
                        'category_id' => $catDress?->id,
                        'name' => 'Outer Kimono Batik Cap Al-Huda Etnik Modern',
                        'slug' => 'outer-kimono-batik-cap-alhuda-etnik-modern',
                        'description' => 'Outer model kimono lengan 7/8 motif batik cap kontemporer yang fleksibel dipakai gaya formal maupun kasual.',
                        'price' => 330000,
                        'stock' => 30,
                        'sku' => 'BAH-PRD-010',
                        'weight' => 340,
                        'is_virtual_try_on' => true,
                        'is_active' => true,
                    ],
                ],
            ],
        ];

        foreach ($storesData as $item) {
            // A. Buat atau temukan user admin store
            $userData = $item['user'];
            $seller = User::firstOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ]
            );

            if ($adminStoreRole && ! $seller->hasRole('admin store', 'web')) {
                $seller->assignRole($adminStoreRole);
            }

            // B. Buat atau temukan toko yang dimiliki oleh seller ini
            $storeData = $item['store'];
            $store = Store::firstOrCreate(
                ['name' => $storeData['name']],
                array_merge($storeData, ['user_id' => $seller->id])
            );

            $storeUpdates = [];
            if ($store->user_id !== $seller->id) {
                $storeUpdates['user_id'] = $seller->id;
            }
            if (empty($store->address) && ! empty($storeData['address'])) {
                $storeUpdates['address'] = $storeData['address'];
            }
            if ($storeUpdates !== []) {
                $store->update($storeUpdates);
            }

            // C. Buat 10 produk untuk toko ini
            foreach ($item['products'] as $prodData) {
                $product = Product::firstOrCreate(
                    ['slug' => $prodData['slug']],
                    array_merge($prodData, [
                        'store_id' => $store->id,
                    ])
                );

                if ($product->store_id !== $store->id) {
                    $product->update(['store_id' => $store->id]);
                }

                // Hubungkan varian jika belum terhubung
                if ($variantValues->isNotEmpty() && $product->variantValues()->count() === 0) {
                    $randomValues = $variantValues->random(min(4, $variantValues->count()))->pluck('id')->all();
                    $product->variantValues()->sync($randomValues);
                }
            }
        }
    }
}
