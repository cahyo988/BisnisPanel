<?php

namespace App\Services;

use App\Models\ChannelAccount;
use App\Models\WhatsAppDevice;

class ChannelAccountRegistry
{
    public function forWhatsAppDevice(WhatsAppDevice $device): ChannelAccount
    {
        /** @var ChannelAccount $account */
        $account = ChannelAccount::query()->firstOrCreate(
            [
                'channel' => ChannelAccount::CHANNEL_WHATSAPP,
                'external_id' => (string) $device->id,
            ],
            [
                'user_id' => $device->user_id,
                'name' => $device->name,
                'status' => $device->status,
                'meta' => [
                    'phone_number' => $device->phone_number,
                ],
            ]
        );

        $account->fill([
            'user_id' => $device->user_id,
            'name' => $device->name,
            'status' => $device->status,
            'meta' => [
                'phone_number' => $device->phone_number,
            ],
        ])->save();

        return $account;
    }
}
