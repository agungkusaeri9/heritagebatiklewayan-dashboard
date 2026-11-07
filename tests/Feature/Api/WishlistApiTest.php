<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $category = Category::create([
            'name' => 'Batik Tulis',
            'slug' => 'batik-tulis',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Sutra Batik Motif Parang',
            'slug' => 'kemeja-sutra-batik-motif-parang',
            'price' => 500000,
            'stock' => 50,
            'weight' => 300,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_wishlist(): void
    {
        $this->getJson(route('api.v1.wishlist.index'))->assertUnauthorized();
        $this->postJson(route('api.v1.wishlist.store'), ['product_id' => $this->product->id])
            ->assertUnauthorized();
        $this->deleteJson(route('api.v1.wishlist.destroy', $this->product->id))
            ->assertUnauthorized();
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.wishlist.store'), ['product_id' => $this->product->id])
            ->assertCreated()
            ->assertJsonPath('data.product_id', $this->product->id)
            ->assertJsonPath('meta.status', 'success');

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_adding_same_product_is_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

        $this->postJson(route('api.v1.wishlist.store'), ['product_id' => $this->product->id])
            ->assertOk();

        $this->assertSame(1, Wishlist::where('user_id', $this->user->id)->count());
    }

    public function test_store_rejects_inactive_or_missing_product(): void
    {
        Sanctum::actingAs($this->user);

        $inactive = Product::create([
            'category_id' => $this->product->category_id,
            'name' => 'Produk Nonaktif',
            'slug' => 'produk-nonaktif',
            'price' => 100000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => false,
        ]);

        $this->postJson(route('api.v1.wishlist.store'), ['product_id' => $inactive->id])
            ->assertStatus(422);

        $this->postJson(route('api.v1.wishlist.store'), ['product_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_user_can_list_wishlist(): void
    {
        Sanctum::actingAs($this->user);

        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

        $this->getJson(route('api.v1.wishlist.index'))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.product_id', $this->product->id);
    }

    public function test_user_can_remove_product_from_wishlist(): void
    {
        Sanctum::actingAs($this->user);

        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

        $this->deleteJson(route('api.v1.wishlist.destroy', $this->product->id))
            ->assertOk();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_remove_missing_wishlist_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->deleteJson(route('api.v1.wishlist.destroy', $this->product->id))
            ->assertNotFound();
    }
}
