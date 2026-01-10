<?php

use App\Jobs\SendMessageJob;
use App\Models\AutoReplyRule;
use App\Models\MessageLog;
use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config([
        'services.whatsapp.webhook_token' => 'secret-token',
        'services.whatsapp.base_url' => null,
        'services.whatsapp.token' => null,
    ]);
});

it('stores incoming webhook messages and triggers auto replies', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $device = WhatsAppDevice::factory()->for($user)->connected()->create();

    AutoReplyRule::factory()
        ->for($user)
        ->state([
            'whatsapp_device_id' => $device->id,
            'keyword' => 'promo',
            'match_mode' => 'contains',
            'reply_text' => 'Automatic reply.',
        ])
        ->create();

    $payload = [
        'device_id' => $device->id,
        'from' => '+62111111',
        'type' => 'text',
        'message' => 'promo please',
    ];

    $this->postJson('/api/webhooks/baileys/messages', $payload, ['X-Webhook-Token' => 'secret-token'])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('message_logs', [
        'user_id' => $user->id,
        'whatsapp_device_id' => $device->id,
        'direction' => MessageLog::DIRECTION_INCOMING,
        'phone' => '+62111111',
        'status' => MessageLog::STATUS_DELIVERED,
    ]);

    $this->assertDatabaseHas('message_logs', [
        'direction' => MessageLog::DIRECTION_OUTGOING,
        'phone' => '+62111111',
        'status' => MessageLog::STATUS_PENDING,
    ]);

    $this->assertDatabaseHas('panel_notifications', [
        'user_id' => $user->id,
        'title' => 'Incoming WhatsApp message',
    ]);

    Queue::assertPushed(SendMessageJob::class);
});

it('updates device status via webhook', function (): void {
    $user = User::factory()->create();
    $device = WhatsAppDevice::factory()->for($user)->create([
        'status' => 'disconnected',
        'session' => null,
    ]);

    $payload = [
        'device_id' => $device->id,
        'status' => 'connected',
        'session' => ['qr' => 'abc'],
        'last_connected_at' => Carbon::now()->toISOString(),
        'last_seen_at' => Carbon::now()->toISOString(),
    ];

    $this->postJson('/api/webhooks/baileys/devices/status', $payload, ['X-Webhook-Token' => 'secret-token'])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('whatsapp_devices', [
        'id' => $device->id,
        'status' => 'connected',
    ]);

    $this->assertDatabaseHas('panel_notifications', [
        'user_id' => $user->id,
        'type' => 'device',
    ]);
});
