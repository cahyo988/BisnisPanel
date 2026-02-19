<?php

namespace App\Services;

use App\Models\ChannelAccount;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Support\ContactKeyNormalizer;

class ConversationRegistry
{
    public function assign(MessageLog $log, ChannelAccount $account, string $contactKey, ?string $contactName = null): MessageLog
    {
        $normalizedContact = ContactKeyNormalizer::normalizeForChannel($account->channel, $contactKey);

        if (blank($normalizedContact)) {
            $normalizedContact = trim($contactKey);
        }

        /** @var Conversation $conversation */
        $conversation = Conversation::query()->firstOrCreate(
            [
                'channel' => $account->channel,
                'channel_account_id' => $account->id,
                'contact_key' => $normalizedContact,
            ],
            [
                'user_id' => $log->user_id,
                'contact_name' => $contactName,
                'unread_count' => 0,
            ]
        );

        $unreadCount = $conversation->unread_count;

        if ($log->direction === MessageLog::DIRECTION_INCOMING) {
            $unreadCount++;
        }

        $conversation->update([
            'user_id' => $log->user_id,
            'contact_name' => $contactName ?: $conversation->contact_name,
            'last_message_preview' => filled($log->message) ? mb_substr((string) $log->message, 0, 140) : $conversation->last_message_preview,
            'last_message_at' => $log->created_at,
            'last_incoming_at' => $log->direction === MessageLog::DIRECTION_INCOMING ? $log->created_at : $conversation->last_incoming_at,
            'last_outgoing_at' => $log->direction === MessageLog::DIRECTION_OUTGOING ? $log->created_at : $conversation->last_outgoing_at,
            'unread_count' => $unreadCount,
        ]);

        $log->forceFill([
            'channel' => $account->channel,
            'channel_account_id' => $account->id,
            'conversation_id' => $conversation->id,
            'phone' => $normalizedContact,
        ])->save();

        return $log;
    }
}
