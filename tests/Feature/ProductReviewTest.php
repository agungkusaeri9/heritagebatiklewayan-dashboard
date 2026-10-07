<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_page_loads_with_reviews_and_fractional_rating_stats()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Batik Tulis',
            'slug' => 'batik-tulis',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Sutra Parang',
            'slug' => 'kemeja-batik-sutra-parang',
            'price' => 350000,
            'stock' => 15,
            'weight' => 250,
            'is_active' => true,
        ]);

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'subtotal' => 350000,
            'shipping_fee' => 15000,
            'total_amount' => 365000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Test No. 123',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => 350000,
            'quantity' => 1,
            'total_price' => 350000,
        ]);

        ProductReview::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Bahan sutranya luar biasa halus dan nyaman.',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($user)->get(route('products.show', $product));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Show')
            ->has('product')
            ->has('reviews')
            ->has('rating_stats')
            ->where('rating_stats.total_count', 1)
            ->where('rating_stats.average_rating', 5.0)
            ->where('reviews.data.0.comment', 'Bahan sutranya luar biasa halus dan nyaman.')
        );
    }

    public function test_customer_can_submit_review_for_completed_order_product()
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Batik Cap',
            'slug' => 'batik-cap',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Cap Mega Mendung',
            'slug' => 'kemeja-batik-cap-mega-mendung',
            'price' => 200000,
            'stock' => 10,
            'weight' => 200,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'subtotal' => 200000,
            'shipping_fee' => 10000,
            'total_amount' => 210000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Test No. 456',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => 200000,
            'quantity' => 1,
            'total_price' => 200000,
        ]);

        $response = $this->actingAs($customerUser)->post(route('reviews.store'), [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'rating' => 4,
            'comment' => 'Motif dan warna sangat memuaskan!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_reviews', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'rating' => 4,
            'comment' => 'Motif dan warna sangat memuaskan!',
            'is_visible' => true,
        ]);
    }

    public function test_customer_cannot_review_pending_or_unpurchased_order()
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Batik Premium',
            'slug' => 'batik-premium',
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

        // Pending order
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'subtotal' => 180000,
            'shipping_fee' => 10000,
            'total_amount' => 190000,
            'status' => 'pending',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Test No. 789',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => 180000,
            'quantity' => 1,
            'total_price' => 180000,
        ]);

        $response = $this->actingAs($customerUser)->post(route('reviews.store'), [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Bagus!',
        ]);

        $response->assertSessionHasErrors('order_id');
        $this->assertDatabaseMissing('product_reviews', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_admin_can_toggle_review_visibility()
    {
        $user = User::factory()->create();
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Batik Tulis',
            'slug' => 'batik-tulis-toggle',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Batik Sutra',
            'slug' => 'batik-sutra-toggle',
            'price' => 500000,
            'stock' => 5,
            'weight' => 200,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TOGGLE',
            'customer_id' => $customer->id,
            'subtotal' => 500000,
            'shipping_fee' => 10000,
            'total_amount' => 510000,
            'status' => 'success',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Test',
        ]);

        $review = ProductReview::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Review asli',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($user)->post(route('reviews.toggle', $review));

        $response->assertSessionHasNoErrors();
        $this->assertFalse($review->fresh()->is_visible);

        // Toggle back
        $response = $this->actingAs($user)->post(route('reviews.toggle', $review));
        $this->assertTrue($review->fresh()->is_visible);
    }
}
