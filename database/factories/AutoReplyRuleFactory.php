<?php

namespace Database\Factories;

use App\Models\AutoReplyRule;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutoReplyRule>
 */
class AutoReplyRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'whatsapp_device_id' => WhatsAppDevice::factory(),
            'keyword' => $this->faker->word(),
            'match_type' => $this->faker->randomElement(['exact', 'contains']),
            'reply_type' => $this->faker->randomElement(['text', 'template']),
            'reply_text' => $this->faker->sentence(),
            'message_template_id' => MessageTemplate::factory(),
            'is_active' => true,
        ];
    }
}
