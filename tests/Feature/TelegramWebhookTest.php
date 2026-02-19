<?php

use App\Models\ChannelAccount;
use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    config([
        'services.telegram.webhook_token' => 'telegram-secret',
    ]);
});

it('stores incoming telegram webhook messages', function (): void {
    $user = User::factory()->create();

    $account = ChannelAccount::query()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_TELEGRAM,
        'name' => 'Telegram Sales',
        'external_id' => 'tg-sales-1',
        'status' => 'connected',
    ]);

    $payload = [
        'account_id' => $account->id,
        'chat_id' => '99887766',
        'type' => 'text',
        'message' => 'Hello from telegram',
        'from_name' => 'Rani',
        'message_id' => 'msg-123',
    ];

    $this->postJson('/api/webhooks/telegram/messages', $payload, ['X-Webhook-Token' => 'telegram-secret'])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('message_logs', [
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_TELEGRAM,
        'channel_account_id' => $account->id,
        'direction' => MessageLog::DIRECTION_INCOMING,
        'phone' => '99887766',
        'status' => MessageLog::STATUS_DELIVERED,
    ]);

    $this->assertDatabaseHas('conversations', [
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_TELEGRAM,
        'channel_account_id' => $account->id,
        'contact_key' => '99887766',
    ]);
});

it('updates delivery status for telegram outgoing logs', function (): void {
    $user = User::factory()->create();

    $account = ChannelAccount::query()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_TELEGRAM,
        'name' => 'Telegram CS',
        'external_id' => 'tg-cs-1',
        'status' => 'connected',
    ]);

    $log = MessageLog::factory()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_TELEGRAM,
        'channel_account_id' => $account->id,
        'direction' => MessageLog::DIRECTION_OUTGOING,
        'status' => MessageLog::STATUS_PENDING,
        'phone' => '99887766',
    ]);

    $payload = [
        'log_id' => $log->id,
        'status' => MessageLog::STATUS_DELIVERED,
        'timestamp' => Carbon::now()->toISOString(),
    ];

    $this->postJson('/api/webhooks/telegram/messages/status', $payload, ['X-Webhook-Token' => 'telegram-secret'])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $this->assertDatabaseHas('message_logs', [
        'id' => $log->id,
        'status' => MessageLog::STATUS_DELIVERED,
    ]);
});
