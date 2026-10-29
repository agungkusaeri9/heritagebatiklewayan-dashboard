<?php

namespace Database\Factories;

use App\Models\AppConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppConfig>
 */
class AppConfigFactory extends Factory
{
    protected $model = AppConfig::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->sentence(),
            'description' => fake()->sentence(),
        ];
    }
}
