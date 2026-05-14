<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar',
        'profile_banners',
        'bio',
        'role',
        'banned_until',
        'ban_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'profile_banners' => 'array',
        'banned_until' => 'datetime',
    ];

    public function isBanned(): bool
    {
        return $this->banned_until && $this->banned_until->isFuture();
    }

    public function bans()
    {
        return $this->hasMany(UserBan::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function profileVisitsReceived()
    {
        return $this->hasMany(ProfileVisit::class, 'profile_user_id');
    }

    public function profileVisitsMade()
    {
        return $this->hasMany(ProfileVisit::class, 'visitor_id');
    }

    public function followingUsers()
    {
        return $this->belongsToMany(self::class, 'user_follows', 'follower_id', 'followed_user_id')
            ->withTimestamps();
    }

    public function followerUsers()
    {
        return $this->belongsToMany(self::class, 'user_follows', 'followed_user_id', 'follower_id')
            ->withTimestamps();
    }

    public function notificationStatuses()
    {
        return $this->hasMany(UserNotificationStatus::class);
    }
}
