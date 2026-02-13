<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AutoReplySession extends Model
{
    protected $fillable = [
        'whatsapp_device_id',
        'sender_phone',
        'current_menu_key',
        'greeted',
        'last_interaction_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'greeted' => 'bool',
            'last_interaction_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WhatsAppDevice, AutoReplySession>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(WhatsAppDevice::class, 'whatsapp_device_id');
    }

    /**
     * Check if the session has expired based on the given timeout in minutes.
     */
    public function isExpired(int $timeoutMinutes = 30): bool
    {
        if (! $this->last_interaction_at) {
            return true;
        }

        return $this->last_interaction_at->diffInMinutes(Carbon::now()) >= $timeoutMinutes;
    }

    /**
     * Touch the session interaction timestamp and update menu key.
     */
    public function touch(?string $menuKey = null): bool
    {
        $data = ['last_interaction_at' => Carbon::now()];

        if ($menuKey !== null) {
            $data['current_menu_key'] = $menuKey;
        }

        return $this->update($data);
    }
}
