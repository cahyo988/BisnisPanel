<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoReplyRule extends Model
{
    use HasFactory;
    use HasUserScope;

    protected $fillable = [
        'user_id',
        'whatsapp_device_id',
        'keyword',
        'match_mode',
        'reply_type',
        'reply_text',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<User, AutoReplyRule>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WhatsAppDevice, AutoReplyRule>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(WhatsAppDevice::class, 'whatsapp_device_id');
    }
}

