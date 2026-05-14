<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotPendingAction extends Model
{
    protected $fillable = [
        'channel',
        'actor_id',
        'action_type',
        'payload',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function store(string $channel, int $actorId, string $actionType, array $payload, int $ttlMinutes = 5): ?self
    {
        self::where('channel', $channel)
            ->where('actor_id', $actorId)
            ->where('action_type', $actionType)
            ->delete();

        return self::create([
            'channel' => $channel,
            'actor_id' => $actorId,
            'action_type' => $actionType,
            'payload' => $payload,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public static function consume(string $channel, int $actorId, string $actionType): ?array
    {
        $record = self::where('channel', $channel)
            ->where('actor_id', $actorId)
            ->where('action_type', $actionType)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return null;
        }

        $payload = $record->payload;
        $record->delete();

        return $payload;
    }

    public static function hasActive(string $channel, int $actorId, string $actionType): bool
    {
        return self::where('channel', $channel)
            ->where('actor_id', $actorId)
            ->where('action_type', $actionType)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
