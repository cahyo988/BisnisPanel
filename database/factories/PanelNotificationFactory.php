<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PanelNotification>
 */
class PanelNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(8),
            'type' => fake()->randomElement(['system', 'device', 'message']),
            'read_at' => null,
        ];
    }
}

