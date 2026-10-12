<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\City;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Order $order;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Province::create(['province_id' => 9, 'province_name' => 'Jawa Barat']);
        City::create(['city_id' => 23, 'province_id' => 9, 'city_name' => 'Kota Bandung', 'postal_code' => '40111']);
        Subdistrict::create(['subdistrict_id' => 351, 'city_id' => 23, 'subdistrict_name' => 'Coblong']);

        $this->customer = Customer::factory()->create();
        $this->user = $this->customer->user;

        $category = Category::create([
            'name' => 'Batik Pria',
            'slug' => 'batik-pria',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Tulis Parang',
            'slug' => 'kemeja-batik-tulis-parang',
            'price' => 350000,
            'stock' => 15,
            'weight' => 250,
            'is_active' => true,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'BCA Virtual Account',
            'account_name' => 'PT Batik Nusantara',
            'account_number' => '8830192841',
            'is_active' => true,
        ]);

        $courier = Courier::create([
            'name' => 'JNE',
            'code' => 'jne',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-TEST123456',
            'customer_id' => $this->customer->id,
            'payment_method_id' => $paymentMethod->id,
            'courier_id' => $courier->id,
            'subtotal' => 350000,
            'shipping_fee' => 20000,
            'total_amount' => 370000,
            'status' => 'processing',
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Dago Asri No. 10',
            'shipping_name' => 'JNE - Reguler',
            'tracking_number' => 'JNE987654321',
        ]);

        OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'variant_info' => 'Ukuran: L, Warna: Coklat Klasik',
            'price' => 350000,
            'quantity' => 1,
            'total_price' => 350000,
        ]);

        Transaction::create([
            'order_id' => $this->order->id,
            'amount' => 370000,
            'status' => 'paid',
        ]);
    }

    public function test_guest_cannot_access_orders(): void
    {
        $this->getJson(route('api.v1.orders.index'))
            ->assertUnauthorized()
            ->assertJsonPath('meta.status', 'error');

        $this->getJson(route('api.v1.orders.show', 'ORD-TEST123456'))
            ->assertUnauthorized();
    }

    public function test_user_without_customer_profile_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.orders.index'))
            ->assertForbidden()
            ->assertJsonPath('meta.status', 'error');
    }

    public function test_user_can_list_only_own_orders(): void
    {
        // Other customer's order
        $otherCustomer = Customer::factory()->create();
        Order::create([
            'order_number' => 'ORD-OTHER99999',
            'customer_id' => $otherCustomer->id,
            'subtotal' => 100000,
            'shipping_fee' => 10000,
            'total_amount' => 110000,
            'status' => 'pending',
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Lain',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.v1.orders.index'));

        $response->assertOk()
            ->assertJsonPath('meta.message', 'Berhasil mengambil daftar pesanan.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-TEST123456')
            ->assertJsonPath('data.0.status', 'processing')
            ->assertJsonPath('data.0.total_amount', 370000)
            ->assertJsonPath('data.0.shipping.shipping_name', 'JNE - Reguler')
            ->assertJsonPath('data.0.payment.payment_status', 'paid');
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson(route('api.v1.orders.index', ['status' => 'processing']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_search_orders_by_order_number_or_product_name(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.orders.index', ['search' => 'TEST123456']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson(route('api.v1.orders.index', ['search' => 'Tulis Parang']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson(route('api.v1.orders.index', ['search' => 'TidakAda']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_view_order_detail_by_order_number(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.v1.orders.show', 'ORD-TEST123456'));

        $response->assertOk()
            ->assertJsonPath('meta.message', 'Berhasil mengambil detail pesanan.')
            ->assertJsonPath('data.order_number', 'ORD-TEST123456')
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.pricing.subtotal', 350000)
            ->assertJsonPath('data.pricing.shipping_fee', 20000)
            ->assertJsonPath('data.pricing.total_amount', 370000)
            ->assertJsonPath('data.shipping_address.province.name', 'Jawa Barat')
            ->assertJsonPath('data.shipping_address.city.name', 'Kota Bandung')
            ->assertJsonPath('data.shipping_address.subdistrict.name', 'Coblong')
            ->assertJsonPath('data.shipping.tracking_number', 'JNE987654321')
            ->assertJsonPath('data.payment.payment_method_name', 'BCA Virtual Account')
            ->assertJsonPath('data.payment.payment_account_number', '8830192841')
            ->assertJsonPath('data.payment.transaction.status', 'paid')
            ->assertJsonPath('data.items.0.product_name', 'Kemeja Batik Tulis Parang')
            ->assertJsonPath('data.items.0.variant_info', 'Ukuran: L, Warna: Coklat Klasik')
            ->assertJsonPath('data.items.0.quantity', 1);
    }

    public function test_user_can_view_order_detail_by_id(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.orders.show', $this->order->id))
            ->assertOk()
            ->assertJsonPath('data.id', $this->order->id)
            ->assertJsonPath('data.order_number', 'ORD-TEST123456');
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $otherCustomer = Customer::factory()->create();
        $otherOrder = Order::create([
            'order_number' => 'ORD-OTHER11111',
            'customer_id' => $otherCustomer->id,
            'subtotal' => 100000,
            'shipping_fee' => 10000,
            'total_amount' => 110000,
            'status' => 'pending',
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Lain',
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson(route('api.v1.orders.show', 'ORD-OTHER11111'))
            ->assertNotFound()
            ->assertJsonPath('meta.message', 'Pesanan tidak ditemukan.');

        $this->getJson(route('api.v1.orders.show', $otherOrder->id))
            ->assertNotFound();
    }
}
