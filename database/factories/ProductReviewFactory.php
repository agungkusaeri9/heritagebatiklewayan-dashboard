<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reviews = [
            5 => [
                'Bahan batiknya sangat halus, adem saat dipakai dan motifnya sangat elegan. Sangat puas belanja di sini!',
                'Kualitas jahitan sangat rapi, warna sesuai dengan foto di katalog. Packaging aman dan pengiriman cepat.',
                'Batik premium dengan harga yang sangat terjangkau. Pas banget di badan, keliatan mewah saat dipakai ke acara formal.',
                'Sangat suka dengan detail coraknya! Bahan katun prima nya lembut dan tidak luntur saat dicuci.',
                'Pelayanan ramah, barang sampai dalam kondisi sangat baik. Pasti akan order motif lainnya!',
            ],
            4 => [
                'Bagus sekali batiknya, motifnya modern. Hanya saja pengiriman agak terlambat 1 hari, selebihnya memuaskan.',
                'Kualitas bahan oke, ukuran sesuai dengan deskripsi tabel size. Mantap!',
                'Motif batiknya cakep, warna cerah dan kain cukup nyaman dipakai seharian di kantor.',
                'Barang sesuai pesanan, jahitan rapi. Recommended untuk seragam kantor maupun kondangan.',
            ],
            3 => [
                'Kain batik lumayan bagus, motif sesuai. Tapi bahannya sedikit lebih tipis dari perkiraan saya.',
                'Warna agak sedikit berbeda dari foto karena efek pencahayaan, tapi secara keseluruhan masih oke.',
            ],
            2 => [
                'Ukuran agak kekecilan dibanding panduan ukuran, mohon deskripsi size chart lebih diperjelas.',
            ],
            1 => [
                'Bahan agak kaku dan motif kurang tajam.',
            ],
        ];

        // Weight towards 4 and 5 stars for realistic positive ecommerce reviews
        $weightedRatings = [5, 5, 5, 5, 4, 4, 4, 4, 3, 5];
        $rating = fake()->randomElement($weightedRatings);
        $commentList = $reviews[$rating] ?? $reviews[5];
        $comment = fake()->randomElement($commentList);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'rating' => $rating,
            'comment' => $comment,
            'is_visible' => true,
        ];
    }
}
