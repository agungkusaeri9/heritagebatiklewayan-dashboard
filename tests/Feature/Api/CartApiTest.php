<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
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

    public function test_guest_cannot_access_cart(): void
    {
        $this->getJson(route('api.v1.cart.index'))
            ->assertUnauthorized()
            ->assertJsonPath('meta.status', 'error');

        $this->postJson(route('api.v1.cart.store'), ['product_id' => $this->product->id])
            ->assertUnauthorized();

        $this->deleteJson(route('api.v1.cart.destroy', 1))
            ->assertUnauthorized();
    }

    public function test_user_can_add_item_to_cart(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: XL',
            'quantity' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('meta.code', 201)
            ->assertJsonPath('meta.message', 'Produk berhasil ditambahkan ke keranjang.')
            ->assertJsonPath('data.product_id', $this->product->id)
            ->assertJsonPath('data.product_price', 500000)
            ->assertJsonPath('data.qty', 2)
            ->assertJsonPath('data.total', 1000000)
            ->assertJsonPath('data.variant_info', 'Ukuran: XL')
            ->assertJsonPath('data.weight', 300)
            ->assertJsonPath('data.total_weight', 600)
            ->assertJsonPath('data.is_in_stock', true);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: XL',
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_and_variant_increments_quantity(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: L',
            'quantity' => 3,
        ])->assertCreated();

        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: L',
            'quantity' => 4,
        ])->assertCreated();

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: L',
            'quantity' => 7,
        ]);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_cannot_exceed_max_quantity_of_20(): void
    {
        Sanctum::actingAs($this->user);

        // Exceeds in single request
        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 21,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        // Exceeds cumulatively
        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 15,
        ])->assertCreated();

        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 6,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.code', 422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $this->product->update(['stock' => 5]);

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 6,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_cannot_add_inactive_product(): void
    {
        $this->product->update(['is_active' => false]);

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.v1.cart.store'), [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.code', 422);
    }

    public function test_user_can_list_cart_without_pagination(): void
    {
        $otherProduct = Product::create([
            'name' => 'Batik Celana Pria',
            'slug' => 'batik-celana-pria',
            'price' => 200000,
            'stock' => 10,
            'weight' => 200,
            'is_active' => true,
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'variant_info' => 'M',
            'quantity' => 2,
        ]);

        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $otherProduct->id,
            'variant_info' => null,
            'quantity' => 1,
        ]);

        // Another user's cart item
        $otherUser = User::factory()->create();
        Cart::create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'variant_info' => 'XL',
            'quantity' => 5,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.v1.cart.index'));

        $response->assertOk()
            ->assertJsonPath('meta.message', 'Berhasil mengambil daftar keranjang.')
            ->assertJsonMissingPath('meta.pagination') // NOT paginated
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.summary.total_items', 3)
            ->assertJsonPath('data.summary.total_weight', 800) // (300*2) + (200*1)
            ->assertJsonPath('data.summary.total_price', 1200000); // (500000*2) + (200000*1)
    }

    public function test_user_can_remove_item_from_cart(): void
    {
        $cartItem = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson(route('api.v1.cart.destroy', $cartItem->id))
            ->assertOk()
            ->assertJsonPath('meta.message', 'Item berhasil dihapus dari keranjang.');

        $this->assertDatabaseMissing('carts', ['id' => $cartItem->id]);
    }

    public function test_user_cannot_remove_other_users_cart_item(): void
    {
        $otherUser = User::factory()->create();
        $cartItem = Cart::create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson(route('api.v1.cart.destroy', $cartItem->id))
            ->assertNotFound()
            ->assertJsonPath('meta.message', 'Item keranjang tidak ditemukan.');

        $this->assertDatabaseHas('carts', ['id' => $cartItem->id]);
    }

    public function test_user_can_update_cart_quantity(): void
    {
        $cartItem = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson(route('api.v1.cart.update', $cartItem->id), [
            'quantity' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.message', 'Jumlah item keranjang berhasil diperbarui.')
            ->assertJsonPath('data.qty', 5)
            ->assertJsonPath('data.total', 2500000)
            ->assertJsonPath('data.total_weight', 1500);

        $this->assertDatabaseHas('carts', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_update_cart_quantity_beyond_max_or_stock(): void
    {
        $cartItem = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($this->user);

        // Exceeds 20
        $this->putJson(route('api.v1.cart.update', $cartItem->id), [
            'quantity' => 25,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        // Less than 1
        $this->putJson(route('api.v1.cart.update', $cartItem->id), [
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        // Exceeds stock
        $this->product->update(['stock' => 8]);
        $this->putJson(route('api.v1.cart.update', $cartItem->id), [
            'quantity' => 10,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_user_cannot_update_other_users_cart_item(): void
    {
        $otherUser = User::factory()->create();
        $cartItem = Cart::create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($this->user);

        $this->patchJson(route('api.v1.cart.update', $cartItem->id), [
            'quantity' => 4,
        ])
            ->assertNotFound()
            ->assertJsonPath('meta.message', 'Item keranjang tidak ditemukan.');

        $this->assertDatabaseHas('carts', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }
}
