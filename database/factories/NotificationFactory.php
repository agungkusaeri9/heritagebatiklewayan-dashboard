<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_setting_id' => NotificationSetting::factory(),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'recipient_type' => 'ALL_USERS',
            'created_by' => User::factory(),
        ];
    }
}
