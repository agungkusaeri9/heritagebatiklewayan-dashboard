<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $customers = Customer::with('user')->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('Products or customers not found. Run ProductSeeder and CustomerSeeder first.');

            return;
        }

        $paymentMethod = PaymentMethod::first();

        $commentsByRating = [
            5 => [
                'Bahan batiknya sangat halus, adem saat dipakai dan motifnya sangat elegan. Sangat puas belanja di sini!',
                'Kualitas jahitan sangat rapi, warna sesuai dengan foto di katalog. Packaging rapi dan pengiriman super cepat.',
                'Batik premium dengan harga yang sangat terjangkau. Pas banget di badan, keliatan mewah saat dipakai ke acara formal.',
                'Sangat suka dengan detail coraknya! Bahan katun prima nya lembut dan tidak luntur saat dicuci.',
                'Pelayanan ramah, barang sampai dalam kondisi sangat baik. Pasti akan order motif lainnya!',
                'Kombinasi warna batiknya bagus sekali, kain tebal tapi tetap dingin di kulit.',
                'Sangat recommended! Cocok untuk seragam kantor atau kondangan.',
            ],
            4 => [
                'Bagus sekali batiknya, motifnya modern. Hanya saja pengiriman agak terlambat 1 hari, selebihnya memuaskan.',
                'Kualitas bahan oke, ukuran sesuai dengan deskripsi tabel size chart. Mantap!',
                'Motif batiknya cakep, warna cerah dan kain cukup nyaman dipakai seharian.',
                'Barang sesuai pesanan, jahitan rapi. Kualitas sebanding dengan harganya.',
                'Secara keseluruhan puas, packing rapi dan kain tidak kaku.',
            ],
            3 => [
                'Kain batik lumayan bagus, motif sesuai. Tapi bahannya sedikit lebih tipis dari perkiraan saya.',
                'Warna agak sedikit berbeda dari foto katalog karena lighting, tapi masih cukup oke dipakai.',
            ],
            2 => [
                'Ukuran agak kekecilan dibanding ukuran standar, sebaiknya pesan satu ukuran lebih besar.',
            ],
            1 => [
                'Bahan agak kaku dan jahitan di bagian kerah kurang rapi.',
            ],
        ];

        // Ensure every product has some successful orders with reviews
        foreach ($products as $product) {
            // Give each product between 6 and 14 reviews to showcase pagination and fractional ratings (e.g. 4.6)
            $targetReviewsCount = rand(7, 14);

            // Rating pattern designed to create realistic average ratings around 4.5 - 4.8 (e.g., 4.6)
            $ratingPool = [5, 5, 5, 5, 4, 4, 4, 5, 5, 4, 3, 5, 4];
            shuffle($ratingPool);

            $selectedCustomers = $customers->random(min($targetReviewsCount, $customers->count()));

            $createdCount = 0;
            foreach ($selectedCustomers as $customer) {
                if ($createdCount >= $targetReviewsCount) {
                    break;
                }

                // Check if customer already has a success order with this product
                $existingOrder = Order::where('customer_id', $customer->id)
                    ->where('status', 'success')
                    ->whereHas('details', fn ($q) => $q->where('product_id', $product->id))
                    ->first();

                if (! $existingOrder) {
                    // Create a completed purchase order for this customer and product
                    $price = (float) $product->price;
                    $qty = rand(1, 2);
                    $subtotal = $price * $qty;
                    $shipping = 15000;
                    $total = $subtotal + $shipping;

                    $existingOrder = Order::create([
                        'order_number' => 'ORD-'.strtoupper(Str::random(10)),
                        'customer_id' => $customer->id,
                        'payment_method_id' => $paymentMethod?->id,
                        'subtotal' => $subtotal,
                        'shipping_fee' => $shipping,
                        'total_amount' => $total,
                        'status' => 'success',
                        'province_id' => 1,
                        'city_id' => 1,
                        'subdistrict_id' => 1,
                        'address' => 'Jl. Pembeli Batik No. '.rand(1, 100),
                        'courier_id' => null,
                        'created_at' => now()->subDays(rand(10, 60)),
                    ]);

                    OrderDetail::create([
                        'order_id' => $existingOrder->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'quantity' => $qty,
                        'total_price' => $subtotal,
                        'created_at' => $existingOrder->created_at,
                    ]);

                    Transaction::create([
                        'order_id' => $existingOrder->id,
                        'amount' => $total,
                        'payment_proof' => null,
                        'status' => 'paid',
                        'created_at' => $existingOrder->created_at,
                    ]);
                }

                // Check if already reviewed for this order & product
                $hasReview = ProductReview::where('order_id', $existingOrder->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (! $hasReview) {
                    $rating = $ratingPool[$createdCount % count($ratingPool)];
                    $comments = $commentsByRating[$rating];
                    $comment = $comments[array_rand($comments)];

                    ProductReview::create([
                        'order_id' => $existingOrder->id,
                        'product_id' => $product->id,
                        'customer_id' => $customer->id,
                        'rating' => $rating,
                        'comment' => $comment,
                        'is_visible' => true,
                        'created_at' => now()->subDays(rand(1, 30))->subHours(rand(1, 23)),
                    ]);

                    $createdCount++;
                }
            }
        }
    }
}
