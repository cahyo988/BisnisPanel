<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_devices')
            ->orderBy('id')
            ->chunkById(200, function ($devices): void {
                foreach ($devices as $device) {
                    $account = DB::table('channel_accounts')->where([
                        'channel' => 'whatsapp',
                        'external_id' => (string) $device->id,
                    ])->first();

                    if (! $account) {
                        $accountId = DB::table('channel_accounts')->insertGetId([
                            'user_id' => $device->user_id,
                            'channel' => 'whatsapp',
                            'name' => $device->name,
                            'external_id' => (string) $device->id,
                            'status' => $device->status ?? 'disconnected',
                            'credentials' => null,
                            'meta' => json_encode([
                                'source' => 'whatsapp_devices',
                                'phone_number' => $device->phone_number,
                                'last_connected_at' => $device->last_connected_at,
                                'last_seen_at' => $device->last_seen_at,
                            ]),
                            'created_at' => $device->created_at ?? now(),
                            'updated_at' => $device->updated_at ?? now(),
                        ]);
                    } else {
                        $accountId = $account->id;
                    }

                    DB::table('message_logs')
                        ->where('whatsapp_device_id', $device->id)
                        ->whereNull('channel_account_id')
                        ->update([
                            'channel' => 'whatsapp',
                            'channel_account_id' => $accountId,
                        ]);
                }
            });

        DB::table('message_logs')
            ->whereNull('channel_account_id')
            ->update([
                'channel' => 'whatsapp',
            ]);

        DB::table('message_logs')
            ->whereNotNull('channel_account_id')
            ->orderBy('id')
            ->chunkById(300, function ($logs): void {
                foreach ($logs as $log) {
                    $conversation = DB::table('conversations')
                        ->where('channel', $log->channel)
                        ->where('channel_account_id', $log->channel_account_id)
                        ->where('contact_key', $log->phone)
                        ->first();

                    if (! $conversation) {
                        $conversationId = DB::table('conversations')->insertGetId([
                            'user_id' => $log->user_id,
                            'channel' => $log->channel,
                            'channel_account_id' => $log->channel_account_id,
                            'contact_key' => $log->phone,
                            'contact_name' => null,
                            'last_message_preview' => $log->message ? mb_substr($log->message, 0, 140) : null,
                            'last_message_at' => $log->created_at,
                            'last_incoming_at' => $log->direction === 'incoming' ? $log->created_at : null,
                            'last_outgoing_at' => $log->direction === 'outgoing' ? $log->created_at : null,
                            'unread_count' => $log->direction === 'incoming' ? 1 : 0,
                            'created_at' => $log->created_at ?? now(),
                            'updated_at' => $log->created_at ?? now(),
                        ]);
                    } else {
                        $unread = $conversation->unread_count ?? 0;

                        if ($log->direction === 'incoming') {
                            $unread++;
                        }

                        DB::table('conversations')
                            ->where('id', $conversation->id)
                            ->update([
                                'last_message_preview' => $log->message ? mb_substr($log->message, 0, 140) : $conversation->last_message_preview,
                                'last_message_at' => $log->created_at,
                                'last_incoming_at' => $log->direction === 'incoming' ? $log->created_at : $conversation->last_incoming_at,
                                'last_outgoing_at' => $log->direction === 'outgoing' ? $log->created_at : $conversation->last_outgoing_at,
                                'unread_count' => $unread,
                                'updated_at' => now(),
                            ]);

                        $conversationId = $conversation->id;
                    }

                    DB::table('message_logs')
                        ->where('id', $log->id)
                        ->update([
                            'conversation_id' => $conversationId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('message_logs')->update([
            'channel' => 'whatsapp',
            'channel_account_id' => null,
            'conversation_id' => null,
            'external_message_id' => null,
        ]);

        DB::table('conversations')->delete();
        DB::table('channel_accounts')->where('channel', 'whatsapp')->delete();
    }
};
