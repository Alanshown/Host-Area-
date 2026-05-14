<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'link_url',
        'link_label',
        'is_active',
        'starts_at',
        'ends_at',
        'published_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}