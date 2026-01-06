<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppDevice extends Model
{
    use HasFactory;
    use HasUserScope;

    protected $table = 'whatsapp_devices';

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'status',
        'session',
        'last_connected_at',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session' => 'array',
            'last_connected_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, WhatsAppDevice>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MessageLog>
     */
    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class, 'whatsapp_device_id');
    }

    /**
     * @return HasMany<AutoReplyRule>
     */
    public function autoReplyRules(): HasMany
    {
        return $this->hasMany(AutoReplyRule::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
