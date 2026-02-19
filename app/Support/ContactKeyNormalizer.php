<?php

namespace App\Support;

use App\Models\ChannelAccount;

class ContactKeyNormalizer
{
    public static function normalizeForChannel(string $channel, ?string $value): string
    {
        if ($channel === ChannelAccount::CHANNEL_WHATSAPP) {
            return self::normalizeWhatsApp($value);
        }

        return trim((string) $value);
    }

    public static function normalizeWhatsApp(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits ?? '';
    }
}
