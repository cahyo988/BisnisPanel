<?php

use App\Models\ChannelAccount;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\ConversationRegistry;

it('reuses same whatsapp conversation for plus and non plus phones', function (): void {
    $user = User::factory()->create();

    $account = ChannelAccount::query()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_WHATSAPP,
        'name' => 'WA Device 1',
        'external_id' => '1',
        'status' => 'connected',
    ]);

    $incoming = MessageLog::factory()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_WHATSAPP,
        'channel_account_id' => $account->id,
        'direction' => MessageLog::DIRECTION_INCOMING,
        'phone' => '+621234567890',
    ]);

    /** @var ConversationRegistry $registry */
    $registry = app(ConversationRegistry::class);

    $registry->assign($incoming, $account, '+621234567890');

    $outgoing = MessageLog::factory()->create([
        'user_id' => $user->id,
        'channel' => ChannelAccount::CHANNEL_WHATSAPP,
        'channel_account_id' => $account->id,
        'direction' => MessageLog::DIRECTION_OUTGOING,
        'phone' => '621234567890',
    ]);

    $registry->assign($outgoing, $account, '621234567890');

    expect($incoming->refresh()->conversation_id)->toBe($outgoing->refresh()->conversation_id);

    $this->assertDatabaseCount('conversations', 1);
    $this->assertDatabaseHas('conversations', [
        'channel' => ChannelAccount::CHANNEL_WHATSAPP,
        'channel_account_id' => $account->id,
        'contact_key' => '621234567890',
    ]);
});
