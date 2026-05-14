<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatPresence extends Model
{
    protected $fillable = [
        'channel',
        'user_id',
        'is_typing',
        'last_seen_at',
        'typing_updated_at',
        'first_seen_at',
        'message_count_today',
    ];

    protected $casts = [
        'is_typing' => 'boolean',
        'last_seen_at' => 'datetime',
        'typing_updated_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'message_count_today' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}