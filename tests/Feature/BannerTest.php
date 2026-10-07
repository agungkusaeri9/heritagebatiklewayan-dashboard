<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_banners()
    {
        $response = $this->get(route('banners.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_banners_list()
    {
        $user = User::factory()->create();
        Banner::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('banners.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banners/Index')
            ->has('banners.data', 3)
            ->has('filters')
        );
    }

    public function test_authenticated_users_can_create_banner_with_image_and_product_target()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Batik Tulis',
            'slug' => 'batik-tulis',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Premium',
            'slug' => 'kemeja-batik-premium',
            'price' => 250000,
            'stock' => 10,
            'weight' => 200,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('mobile-banner.jpg', 1080, 540);

        $response = $this->actingAs($user)->post(route('banners.store'), [
            'title' => 'Promo Batik Nusantara 2026',
            'image' => $image,
            'target_type' => 'product',
            'product_id' => $product->id,
            'sort_order' => 1,
            'is_active' => true,
            'description' => 'Promo spesial batik nusantara mobile app',
        ]);

        $response->assertRedirect(route('banners.index'));
        $this->assertDatabaseHas('banners', [
            'title' => 'Promo Batik Nusantara 2026',
            'target_type' => 'product',
            'product_id' => $product->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $banner = Banner::where('title', 'Promo Batik Nusantara 2026')->first();
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_authenticated_users_can_update_banner()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $banner = Banner::create([
            'title' => 'Banner Awal',
            'image' => 'banners/old.jpg',
            'target_type' => 'none',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('banners.update', $banner), [
            'title' => 'Banner Diperbarui',
            'target_type' => 'url',
            'target_url' => 'https://batik.com/special',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('banners.index'));
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Banner Diperbarui',
            'target_type' => 'url',
            'target_url' => 'https://batik.com/special',
            'sort_order' => 2,
            'is_active' => false,
        ]);
    }

    public function test_authenticated_users_can_toggle_banner_status()
    {
        $user = User::factory()->create();

        $banner = Banner::create([
            'title' => 'Banner Toggle',
            'image' => 'banners/toggle.jpg',
            'target_type' => 'none',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('banners.toggle', $banner));

        $response->assertSessionHasNoErrors();
        $this->assertFalse($banner->fresh()->is_active);

        // Toggle back
        $response = $this->actingAs($user)->post(route('banners.toggle', $banner));
        $this->assertTrue($banner->fresh()->is_active);
    }

    public function test_authenticated_users_can_delete_banner()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('delete-banner.jpg');
        $path = $image->store('banners', 'public');

        $banner = Banner::create([
            'title' => 'Banner Dihapus',
            'image' => $path,
            'target_type' => 'none',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($user)->delete(route('banners.destroy', $banner));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('banners', [
            'id' => $banner->id,
        ]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_authenticated_users_can_view_edit_banner_page()
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Batik Cap',
            'slug' => 'batik-cap',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Solo',
            'slug' => 'kemeja-batik-solo',
            'price' => 180000,
            'stock' => 5,
            'weight' => 200,
            'is_active' => true,
        ]);

        $banner = Banner::create([
            'title' => 'Banner Edit Page Test',
            'image' => 'banners/test.jpg',
            'target_type' => 'product',
            'product_id' => $product->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('banners.edit', $banner));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('banners/Edit')
            ->has('banner')
            ->has('products')
            ->has('categories')
            ->where('banner.title', 'Banner Edit Page Test')
        );
    }
}
