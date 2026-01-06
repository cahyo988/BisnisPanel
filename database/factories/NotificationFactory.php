<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['system', 'message', 'device', 'broadcast']),
            'read_at' => null,
        ];
    }
}
