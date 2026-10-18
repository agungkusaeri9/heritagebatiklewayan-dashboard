<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Product $product;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->user = $this->customer->user;

        $category = Category::create([
            'name' => 'Batik Pria',
            'slug' => 'batik-pria',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Tulis Solo',
            'slug' => 'kemeja-batik-tulis-solo',
            'price' => 350000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-REVIEW-001',
            'customer_id' => $this->customer->id,
            'subtotal' => 350000,
            'shipping_fee' => 15000,
            'total_amount' => 365000,
            'status' => 'success', // Order completed
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Dago No. 10',
        ]);

        OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'price' => 350000,
            'quantity' => 1,
            'total_price' => 350000,
        ]);

        Transaction::create([
            'order_id' => $this->order->id,
            'amount' => 365000,
            'status' => 'paid', // Payment paid
        ]);
    }

    public function test_guest_cannot_create_review(): void
    {
        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_review_if_order_not_completed(): void
    {
        Sanctum::actingAs($this->user);

        // Change order status to pending
        $this->order->update(['status' => 'pending']);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bagus sekali!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order_id']);

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_cannot_review_if_payment_not_paid(): void
    {
        Sanctum::actingAs($this->user);

        // Change transaction status to pending
        $this->order->transaction->update(['status' => 'pending']);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bagus!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order_id']);

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_cannot_review_other_customers_order(): void
    {
        $otherCustomer = Customer::factory()->create();
        Sanctum::actingAs($otherCustomer->user);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_cannot_review_product_not_in_order(): void
    {
        Sanctum::actingAs($this->user);

        $otherProduct = Product::create([
            'name' => 'Kain Batik Madura',
            'slug' => 'kain-batik-madura',
            'price' => 200000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $otherProduct->id,
            'rating' => 5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_can_create_review_when_order_completed_and_payment_paid(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bahan halus dan motif sangat otentik!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('meta.code', 201)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Bahan halus dan motif sangat otentik!')
            ->assertJsonPath('data.customer_name', $this->user->name);

        $this->assertDatabaseHas('product_reviews', [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'comment' => 'Bahan halus dan motif sangat otentik!',
        ]);
    }

    public function test_cannot_review_same_product_in_same_order_twice(): void
    {
        Sanctum::actingAs($this->user);

        ProductReview::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'rating' => 4,
            'comment' => 'Ulasan pertama',
            'is_visible' => true,
        ]);

        $response = $this->postJson(route('api.v1.reviews.store'), [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Ulasan kedua',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_can_review_via_product_nested_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.products.reviews.store', $this->product->id), [
            'order_id' => $this->order->id,
            'rating' => 4,
            'comment' => 'Cakep bajunya',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 4);
    }
}
