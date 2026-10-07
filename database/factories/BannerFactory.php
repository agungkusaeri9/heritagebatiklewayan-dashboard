<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'image' => 'banners/sample-banner-'.fake()->numberBetween(1, 4).'.jpg',
            'target_type' => 'none',
            'target_url' => null,
            'product_id' => null,
            'category_id' => null,
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'start_date' => now()->subDays(rand(1, 10)),
            'end_date' => now()->addDays(rand(10, 60)),
            'description' => fake()->paragraph(),
        ];
    }
}
