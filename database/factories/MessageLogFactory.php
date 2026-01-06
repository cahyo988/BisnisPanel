<?php

namespace Database\Factories;

use App\Models\MessageLog;
use App\Models\WhatsAppDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageLog>
 */
class MessageLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'whatsapp_device_id' => WhatsAppDevice::factory(),
            'direction' => $this->faker->randomElement(['incoming', 'outgoing']),
            'type' => $this->faker->randomElement(['text', 'image']),
            'phone' => $this->faker->e164PhoneNumber(),
            'message' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['queued', 'sent', 'failed']),
            'error_message' => null,
            'raw_payload' => ['sample' => $this->faker->uuid()],
        ];
    }
}
