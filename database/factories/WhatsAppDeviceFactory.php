<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WhatsAppDevice>
 */
class WhatsAppDeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'phone_number' => $this->faker->e164PhoneNumber(),
            'status' => $this->faker->randomElement(['connected', 'disconnected']),
            'session' => ['instance' => $this->faker->uuid()],
            'last_connected_at' => now()->subMinutes(rand(1, 90)),
            'last_seen_at' => now()->subMinutes(rand(1, 90)),
        ];
    }

    /**
     * Mark the device as connected for tests.
     */
    public function connected(): self
    {
        return $this->state(fn () => [
            'status' => 'connected',
        ]);
    }

    /**
     * Mark the device as disconnected for tests.
     */
    public function disconnected(): self
    {
        return $this->state(fn () => [
            'status' => 'disconnected',
        ]);
    }
}
