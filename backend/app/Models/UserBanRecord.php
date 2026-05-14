<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBanRecord extends Model
{
    protected $fillable = [
        'user_id',
        'banned_by',
        'ban_type',
        'reason',
        'detail',
        'source',
        'evidence',
        'banned_until',
        'unbanned_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'banned_until' => 'datetime',
        'unbanned_at' => 'datetime',
    ];

    public const TYPE_MUTE = 'mute';
    public const TYPE_BAN = 'ban';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AUTO = 'auto';
    public const SOURCE_REPORT = 'report';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function isActive(): bool
    {
        return $this->banned_until && $this->banned_until->isFuture() && !$this->unbanned_at;
    }

    public function scopeActive($query)
    {
        return $query->where('banned_until', '>', now())
            ->whereNull('unbanned_at');
    }

    public static function createMute(int $userId, int $bannedBy, string $reason, string $detail = '', array $evidence = []): self
    {
        return static::create([
            'user_id' => $userId,
            'banned_by' => $bannedBy,
            'ban_type' => self::TYPE_MUTE,
            'reason' => $reason,
            'detail' => $detail,
            'source' => self::SOURCE_AUTO,
            'evidence' => $evidence,
            'banned_until' => now()->addMinutes(30),
        ]);
    }

    public static function createBan(int $userId, int $bannedBy, string $reason, string $detail = '', array $evidence = []): self
    {
        return static::create([
            'user_id' => $userId,
            'banned_by' => $bannedBy,
            'ban_type' => self::TYPE_BAN,
            'reason' => $reason,
            'detail' => $detail,
            'source' => self::SOURCE_AUTO,
            'evidence' => $evidence,
            'banned_until' => now()->addDays(1),
        ]);
    }
}
