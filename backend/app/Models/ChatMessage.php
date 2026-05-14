<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'channel',
        'user_id',
        'reply_to_id',
        'author_name',
        'author_role',
        'message_type',
        'content',
        'attachments',
        'meta',
    ];

    protected $casts = [
        'attachments' => 'array',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }
}