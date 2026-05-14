<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    protected $fillable = [
        'follower_id',
        'followed_user_id',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function followedUser()
    {
        return $this->belongsTo(User::class, 'followed_user_id');
    }
}