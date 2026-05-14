<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'cover_image',
        'views',
        'likes',
        'review_status',
        'review_reason',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'views' => 'integer',
        'likes' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
