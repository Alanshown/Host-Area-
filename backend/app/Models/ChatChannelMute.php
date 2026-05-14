<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatChannelMute extends Model
{
    protected $fillable = [
        'channel',
        'user_id',
        'muted_by',
        'muted_until',
    ];

    protected $casts = [
        'muted_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mutedBy()
    {
        return $this->belongsTo(User::class, 'muted_by');
    }
}