<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelAccount extends Model
{
    use HasFactory;
    use HasUserScope;

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_TELEGRAM = 'telegram';

    protected $fillable = [
        'user_id',
        'channel',
        'name',
        'external_id',
        'status',
        'credentials',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, ChannelAccount>
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
        return $this->hasMany(MessageLog::class);
    }

    /**
     * @return HasMany<Conversation>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
