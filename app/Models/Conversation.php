<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;
    use HasUserScope;

    protected $fillable = [
        'user_id',
        'channel',
        'channel_account_id',
        'contact_key',
        'contact_name',
        'last_message_preview',
        'last_message_at',
        'last_incoming_at',
        'last_outgoing_at',
        'unread_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_incoming_at' => 'datetime',
            'last_outgoing_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Conversation>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ChannelAccount, Conversation>
     */
    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    /**
     * @return HasMany<MessageLog>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }
}
