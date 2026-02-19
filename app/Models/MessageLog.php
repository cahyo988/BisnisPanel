<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    use HasFactory;
    use HasUserScope;

    public const DIRECTION_INCOMING = 'incoming';

    public const DIRECTION_OUTGOING = 'outgoing';

    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_BUTTON = 'button';

    public const TYPE_LIST = 'list';

    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'user_id',
        'channel',
        'channel_account_id',
        'conversation_id',
        'whatsapp_device_id',
        'batch_id',
        'direction',
        'type',
        'phone',
        'message',
        'status',
        'error_message',
        'raw_payload',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'gateway_message_id',
        'external_message_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, MessageLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ChannelAccount, MessageLog>
     */
    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    /**
     * @return BelongsTo<Conversation, MessageLog>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<WhatsAppDevice, MessageLog>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(WhatsAppDevice::class, 'whatsapp_device_id');
    }
}
