<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\Category;
use App\Models\City;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Product $product1;

    private Product $product2;

    private Courier $courier;

    private PaymentMethod $paymentMethod;

    private CustomerAddress $address;

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

        $this->product1 = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Parang',
            'slug' => 'kemeja-batik-parang',
            'price' => 250000,
            'stock' => 10,
            'weight' => 200,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Megamendung',
            'slug' => 'kemeja-batik-megamendung',
            'price' => 300000,
            'stock' => 5,
            'weight' => 250,
            'is_active' => true,
        ]);

        $this->courier = Courier::create([
            'name' => 'JNE',
            'code' => 'jne',
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'BCA Virtual Account',
            'account_name' => 'PT Batik Nusantara',
            'account_number' => '1234567890',
            'is_active' => true,
        ]);

        $this->address = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Agung Pratama',
            'phone' => '081234567890',
            'full_address' => 'Jl. Dago Asri No. 10',
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'postal_code' => '40111',
            'is_primary' => true,
        ]);
    }

    public function test_guest_cannot_checkout(): void
    {
        $response = $this->postJson(route('api.v1.orders.checkout'), []);

        $response->assertUnauthorized();
    }

    public function test_user_without_customer_profile_cannot_checkout(): void
    {
        $userWithoutCustomer = User::factory()->create();
        Sanctum::actingAs($userWithoutCustomer);

        $response = $this->postJson(route('api.v1.orders.checkout'), [
            'address_id' => $this->address->id,
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 1],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_can_checkout_direct_items(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'address_id' => $this->address->id,
            'courier_id' => $this->courier->id,
            'shipping_name' => 'JNE - Reguler',
            'shipping_fee' => 15000,
            'payment_method_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2,
                    'variant_info' => 'Ukuran: L, Warna: Coklat',
                ],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertCreated()
            ->assertJsonPath('meta.code', 201)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.pricing.subtotal', 500000)
            ->assertJsonPath('data.pricing.shipping_fee', 15000)
            ->assertJsonPath('data.pricing.total_amount', 515000)
            ->assertJsonPath('data.shipping.courier_name', 'JNE')
            ->assertJsonPath('data.shipping.shipping_name', 'JNE - Reguler')
            ->assertJsonPath('data.payment.payment_method_name', 'BCA Virtual Account')
            ->assertJsonPath('data.payment.transaction.amount', 515000)
            ->assertJsonPath('data.payment.transaction.status', 'pending')
            ->assertJsonPath('data.items.0.product_id', $this->product1->id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.price', 250000)
            ->assertJsonPath('data.items.0.total_price', 500000);

        // Verify database records
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'subtotal' => 500000,
            'shipping_fee' => 15000,
            'total_amount' => 515000,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_details', [
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'total_price' => 500000,
            'variant_info' => 'Ukuran: L, Warna: Coklat',
        ]);

        $this->assertDatabaseHas('transactions', [
            'amount' => 515000,
            'status' => 'pending',
        ]);

        // Stock was decremented from 10 to 8
        $this->product1->refresh();
        $this->assertSame(8, $this->product1->stock);
    }

    public function test_can_checkout_from_cart_and_clears_cart(): void
    {
        Sanctum::actingAs($this->user);

        // Populate cart
        $cart1 = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'variant_info' => 'Ukuran: M',
        ]);

        $cart2 = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product2->id,
            'quantity' => 2,
            'variant_info' => 'Ukuran: XL',
        ]);

        $this->assertDatabaseCount('carts', 2);

        $payload = [
            'address_id' => $this->address->id,
            'courier_id' => $this->courier->id,
            'shipping_fee' => 20000,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertCreated();

        // 1 * 250.000 + 2 * 300.000 = 850.000 subtotal + 20.000 shipping = 870.000 total
        $response->assertJsonPath('data.pricing.subtotal', 850000)
            ->assertJsonPath('data.pricing.shipping_fee', 20000)
            ->assertJsonPath('data.pricing.total_amount', 870000);

        // Cart should now be empty
        $this->assertDatabaseMissing('carts', ['id' => $cart1->id]);
        $this->assertDatabaseMissing('carts', ['id' => $cart2->id]);
        $this->assertDatabaseCount('carts', 0);

        // Stocks decremented
        $this->product1->refresh();
        $this->assertSame(9, $this->product1->stock);

        $this->product2->refresh();
        $this->assertSame(3, $this->product2->stock);
    }

    public function test_can_checkout_using_manual_address(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Riau No. 55, Bandung Kulon',
            'courier_id' => $this->courier->id,
            'shipping_fee' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.shipping_address.address', 'Jl. Riau No. 55, Bandung Kulon');
    }

    public function test_can_checkout_using_fallback_primary_address(): void
    {
        Sanctum::actingAs($this->user);

        // Notice: address_id and manual address are omitted
        $payload = [
            'courier_id' => $this->courier->id,
            'shipping_fee' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'city_id' => 23,
        ]);
    }

    public function test_cannot_checkout_when_product_stock_is_insufficient(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'address_id' => $this->address->id,
            'items' => [
                // product1 stock is 10, requesting 11
                ['product_id' => $this->product1->id, 'quantity' => 11],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        // Stock should remain unchanged
        $this->product1->refresh();
        $this->assertSame(10, $this->product1->stock);
    }

    public function test_cannot_checkout_when_product_is_inactive(): void
    {
        Sanctum::actingAs($this->user);

        $this->product1->update(['is_active' => false]);

        $payload = [
            'address_id' => $this->address->id,
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_preview_checkout_without_modifying_database(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'address_id' => $this->address->id,
            'courier_id' => $this->courier->id,
            'shipping_fee' => 15000,
            'payment_method_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 2],
            ],
        ];

        $response = $this->postJson(route('api.v1.orders.checkout.preview'), $payload);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('data.summary.subtotal', 500000)
            ->assertJsonPath('data.summary.shipping_fee', 15000)
            ->assertJsonPath('data.summary.total_amount', 515000)
            ->assertJsonPath('data.summary.total_weight', 400)
            ->assertJsonPath('data.items.0.product_name', 'Kemeja Batik Parang')
            ->assertJsonPath('data.items.0.quantity', 2);

        // No order created
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);

        // Stock unmodified
        $this->product1->refresh();
        $this->assertSame(10, $this->product1->stock);
    }
}
