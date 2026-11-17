<?php

namespace Tests\Feature;

use App\Models\AppVersion;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_renders_with_empty_database(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('settings', null)
                ->where('banners', [])
                ->where('faqs', [])
                ->where('categories', [])
                ->where('products', [])
                ->missing('tryOns')
                ->where('latestApps.android', null)
                ->where('latestApps.ios', null)
            );
    }

    public function test_public_landing_page_includes_store_settings_catalog_faqs_banners_and_app_versions(): void
    {
        StoreSetting::create([
            'name' => 'Arbatik Heritage Solo',
            'whatsapp' => '628111222333',
            'description' => 'Toko batik tulis pilihan.',
        ]);

        Banner::create([
            'title' => 'Promo Batik Sogan',
            'description' => 'Diskon 20% akhir pekan',
            'image' => 'banners/promo.jpg',
            'target_type' => 'none',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Faq::create([
            'title' => 'Apakah batik asli?',
            'description' => 'Ya, 100% asli pengrajin lokal.',
            'category' => 'Umum',
            'order' => 1,
            'is_active' => true,
        ]);

        $category = Category::factory()->create([
            'name' => 'Pria',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Sogan Parang',
            'slug' => 'kemeja-sogan-parang',
            'price' => 250000,
            'stock' => 10,
            'weight' => 300,
            'is_active' => true,
            'is_virtual_try_on' => true,
        ]);

        AppVersion::create([
            'name' => 'Arbatik Android',
            'type' => 'android',
            'version' => '1.2.0',
            'version_code' => 12,
            'is_latest' => true,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('settings.name', 'Arbatik Heritage Solo')
                ->where('settings.contact.whatsapp', '628111222333')
                ->has('banners', 1)
                ->where('banners.0.title', 'Promo Batik Sogan')
                ->has('faqs', 1)
                ->where('faqs.0.question', 'Apakah batik asli?')
                ->has('categories', 1)
                ->where('categories.0.name', 'Pria')
                ->has('products', 1)
                ->where('products.0.name', 'Kemeja Sogan Parang')
                ->where('latestApps.android.version', '1.2.0')
            );
    }

    public function test_sitemap_xml_returns_valid_xml_pointing_to_root(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml');

        $this->assertStringContainsString('<urlset', $response->getContent());
        $this->assertStringContainsString('<loc>'.rtrim(url('/'), '/').'/</loc>', $response->getContent());
    }
}
