<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $category = Category::create([
            'name' => 'Kemeja Pria',
            'slug' => 'kemeja-pria',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Parang',
            'slug' => 'kemeja-batik-parang',
            'price' => 250000,
            'stock' => 10,
            'weight' => 300,
            'is_active' => true,
        ]);

        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'BCA Virtual Account',
            'account_name' => 'PT Batik Nusantara',
            'account_number' => '1234567890',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'subtotal' => 250000,
            'shipping_fee' => 15000,
            'total_amount' => 265000,
            'status' => 'processing',
            'province_id' => 1,
            'city_id' => 1,
            'subdistrict_id' => 1,
            'address' => 'Jl. Merdeka No. 45',
            'shipping_name' => 'JNE Regular',
            'tracking_number' => 'JNE123456789',
        ]);

        OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'variant_info' => 'Ukuran: XL, Warna: Hitam',
            'price' => 250000,
            'quantity' => 1,
            'total_price' => 250000,
        ]);

        Transaction::create([
            'order_id' => $this->order->id,
            'amount' => 265000,
            'status' => 'paid',
        ]);
    }

    public function test_authenticated_admin_can_view_order_details(): void
    {
        $response = $this->actingAs($this->user)->get(route('orders.show', $this->order));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('orders/Show')
            ->has('order')
            ->where('order.order_number', $this->order->order_number)
            ->where('order.shipping_name', 'JNE Regular')
            ->where('order.tracking_number', 'JNE123456789')
            ->where('order.details.0.variant_info', 'Ukuran: XL, Warna: Hitam')
        );
    }

    public function test_authenticated_admin_can_update_shipping_and_tracking_number(): void
    {
        $response = $this->actingAs($this->user)->post(route('orders.update-shipping', $this->order), [
            'shipping_name' => 'SiCepat BEST',
            'tracking_number' => 'SCP987654321',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Informasi pengiriman & nomor resi berhasil diperbarui.');

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'shipping_name' => 'SiCepat BEST',
            'tracking_number' => 'SCP987654321',
        ]);
    }

    public function test_authenticated_admin_can_update_order_status(): void
    {
        $response = $this->actingAs($this->user)->post(route('orders.update-status', $this->order), [
            'status' => 'success',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status pesanan berhasil diperbarui.');

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'success',
        ]);
    }
}
