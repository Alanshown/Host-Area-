<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageRecall extends Model
{
    protected $fillable = [
        'channel',
        'message_id',
        'recalled_by',
        'original_author_name',
        'original_author_id',
    ];

    public function recalledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recalled_by');
    }
}
