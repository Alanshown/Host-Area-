<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatChannelPreference extends Model
{
    protected $fillable = [
        'channel',
        'user_id',
        'theme_variant',
        'custom_background_path',
        'hide_bot',
    ];

    protected $casts = [
        'hide_bot' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}