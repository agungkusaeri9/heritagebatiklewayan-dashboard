<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement(['Pemesanan', 'Pengiriman', 'Produk Batik', 'Pembayaran', 'Retur']),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
