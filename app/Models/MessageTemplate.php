<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    use HasFactory;
    use HasUserScope;

    protected $fillable = [
        'user_id',
        'name',
        'body',
    ];

    /**
     * @return BelongsTo<User, MessageTemplate>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
