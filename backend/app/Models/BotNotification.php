<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotNotification extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'from_name',
        'content',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    public static function unreadCount(int $userId): int
    {
        return static::where('user_id', $userId)->where('is_read', false)->count();
    }
}
