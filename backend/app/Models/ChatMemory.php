<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMemory extends Model
{
    protected $fillable = [
        'channel',
        'last_message_id',
        'summary',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function lastMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}