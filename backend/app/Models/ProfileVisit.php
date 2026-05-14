<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileVisit extends Model
{
    protected $fillable = ['visitor_id', 'profile_user_id'];

    public function visitor()
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }

    public function profileUser()
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }
}