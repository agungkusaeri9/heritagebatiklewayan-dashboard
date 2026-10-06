<?php

namespace Database\Factories;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationSetting>
 */
class NotificationSettingFactory extends Factory
{
    protected $model = NotificationSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => strtoupper(Str::slug($name, '_')),
            'name' => ucfirst($name),
            'description' => fake()->sentence(),
            'recipient_type' => fake()->randomElement(['ALL_USERS', 'CUSTOMER', 'ADMIN', 'SPECIFIC_USER']),
            'push_enabled' => true,
            'email_enabled' => false,
            'whatsapp_enabled' => false,
            'is_active' => true,
        ];
    }
}
