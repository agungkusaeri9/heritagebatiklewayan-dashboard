<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->customer = Customer::factory()->create();
        $this->user = $this->customer->user;

        $category = Category::create([
            'name' => 'Batik Pria',
            'slug' => 'batik-pria',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kemeja Batik Parang',
            'slug' => 'kemeja-batik-parang',
            'price' => 250000,
            'stock' => 10,
            'weight' => 200,
            'is_active' => true,
        ]);

        $courier = Courier::create([
            'name' => 'JNE',
            'code' => 'jne',
            'is_active' => true,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'BCA Virtual Account',
            'account_name' => 'PT Batik Nusantara',
            'account_number' => '1234567890',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-PROOF123456',
            'customer_id' => $this->customer->id,
            'payment_method_id' => $paymentMethod->id,
            'courier_id' => $courier->id,
            'subtotal' => 250000,
            'shipping_fee' => 15000,
            'total_amount' => 265000,
            'status' => 'pending',
            'province_id' => 9,
            'city_id' => 23,
            'subdistrict_id' => 351,
            'address' => 'Jl. Dago Asri No. 10',
            'shipping_name' => 'JNE - Reguler',
        ]);

        OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'price' => 250000,
            'quantity' => 1,
            'total_price' => 250000,
        ]);

        Transaction::create([
            'order_id' => $this->order->id,
            'amount' => 265000,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_upload_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('proof.jpg');

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), [
            'payment_proof' => $file,
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_upload_payment_proof_for_other_customers_order(): void
    {
        $otherCustomer = Customer::factory()->create();
        Sanctum::actingAs($otherCustomer->user);

        $file = UploadedFile::fake()->image('proof.jpg');

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), [
            'payment_proof' => $file,
        ]);

        $response->assertNotFound();
    }

    public function test_file_is_required(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_proof']);
    }

    public function test_file_must_be_an_image(): void
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), [
            'payment_proof' => $file,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_proof']);
    }

    public function test_file_cannot_exceed_5_mb(): void
    {
        Sanctum::actingAs($this->user);

        // 5121 KB > 5120 KB (5 MB)
        $file = UploadedFile::fake()->create('large-proof.jpg', 5121, 'image/jpeg');

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), [
            'payment_proof' => $file,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_proof']);
    }

    public function test_can_upload_valid_payment_proof_by_order_number(): void
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('bukti_transfer.jpg', 800, 600)->size(2048); // 2 MB

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->order_number), [
            'payment_proof' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.code', 200)
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('meta.message', 'Bukti pembayaran berhasil diunggah.')
            ->assertJsonPath('data.payment.transaction.amount', 265000);

        $this->order->refresh();
        $storedPath = $this->order->transaction->payment_proof;

        $this->assertNotNull($storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertStringStartsWith('payment_proofs/', $storedPath);

        // Assert payment_proof_url is present in response
        $this->assertNotEmpty($response->json('data.payment.transaction.payment_proof_url'));
    }

    public function test_can_upload_valid_payment_proof_by_numeric_id(): void
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('bukti_transfer.png')->size(1024);

        $response = $this->postJson(route('api.v1.orders.payment-proof', $this->order->id), [
            'payment_proof' => $file,
        ]);

        $response->assertOk();

        $this->order->refresh();
        $this->assertNotNull($this->order->transaction->payment_proof);
        Storage::disk('public')->assertExists($this->order->transaction->payment_proof);
    }

    public function test_reuploading_payment_proof_deletes_old_file(): void
    {
        Sanctum::actingAs($this->user);

        // First upload
        $file1 = UploadedFile::fake()->image('proof1.jpg');
        $this->postJson(route('api.v1.orders.payment-proof', $this->order->id), [
            'payment_proof' => $file1,
        ])->assertOk();

        $this->order->refresh();
        $firstPath = $this->order->transaction->payment_proof;
        Storage::disk('public')->assertExists($firstPath);

        // Second upload
        $file2 = UploadedFile::fake()->image('proof2.jpg');
        $this->postJson(route('api.v1.orders.payment-proof', $this->order->id), [
            'payment_proof' => $file2,
        ])->assertOk();

        $this->order->refresh();
        $secondPath = $this->order->transaction->payment_proof;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }
}
