<?php

namespace App\Services;

use App\Models\ChatPresence;
use App\Models\User;

class ChatPresenceService
{
    private const ONLINE_WINDOW_SECONDS = 45;
    private const TYPING_WINDOW_SECONDS = 6;

    public function heartbeat(User $user, string $channel = 'public-lobby'): void
    {
        $presence = ChatPresence::firstOrNew([
            'channel' => $channel,
            'user_id' => $user->id,
        ]);

        $presence->last_seen_at = now();

        if (! $presence->exists) {
            $presence->is_typing = false;
            $presence->typing_updated_at = null;
            $presence->first_seen_at = now();
            $presence->message_count_today = 0;
        }

        // 每日计数器归零
        if ($presence->last_seen_at && $presence->last_seen_at->startOfDay()->lt(now()->startOfDay())) {
            $presence->message_count_today = 0;
        }

        $presence->save();
    }

    public function setTyping(User $user, string $channel, bool $typing): void
    {
        $presence = ChatPresence::firstOrNew([
            'channel' => $channel,
            'user_id' => $user->id,
        ]);

        $presence->last_seen_at = now();
        $presence->is_typing = $typing;
        $presence->typing_updated_at = $typing ? now() : null;
        $presence->save();
    }

    public function snapshot(string $channel = 'public-lobby', ?int $excludeUserId = null): array
    {
        $onlineThreshold = now()->subSeconds(self::ONLINE_WINDOW_SECONDS);
        $typingThreshold = now()->subSeconds(self::TYPING_WINDOW_SECONDS);

        $presences = ChatPresence::where('channel', $channel)
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->with('user:id,username,avatar,role')
            ->orderByDesc('last_seen_at')
            ->get()
            ->filter(fn (ChatPresence $presence) => $presence->user)
            ->values();

        $typingUsers = $presences
            ->filter(function (ChatPresence $presence) use ($typingThreshold, $excludeUserId) {
                if ($excludeUserId && (int) $presence->user_id === (int) $excludeUserId) {
                    return false;
                }

                return $presence->is_typing
                    && $presence->typing_updated_at
                    && $presence->typing_updated_at->gte($typingThreshold);
            })
            ->map(fn (ChatPresence $presence) => [
                'id' => $presence->user->id,
                'username' => $presence->user->username,
                'avatar' => $presence->user->avatar,
                'role' => $presence->user->role,
            ])
            ->values()
            ->all();

        $members = $presences
            ->map(fn (ChatPresence $presence) => [
                'id' => $presence->user->id,
                'username' => $presence->user->username,
                'avatar' => $presence->user->avatar,
                'role' => $presence->user->role,
                'is_typing' => (bool) ($presence->is_typing && $presence->typing_updated_at && $presence->typing_updated_at->gte($typingThreshold)),
                'last_seen_at' => optional($presence->last_seen_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'online_count' => count($members),
            'typing_users' => $typingUsers,
            'members' => $members,
        ];
    }

    public function incrementMessageCount(User $user, string $channel = 'public-lobby'): void
    {
        $presence = ChatPresence::firstOrNew([
            'channel' => $channel,
            'user_id' => $user->id,
        ]);

        if (! $presence->exists) {
            $presence->is_typing = false;
            $presence->first_seen_at = now();
            $presence->message_count_today = 0;
        }

        if ($presence->last_seen_at && $presence->last_seen_at->startOfDay()->lt(now()->startOfDay())) {
            $presence->message_count_today = 0;
        }

        $presence->message_count_today = ($presence->message_count_today ?? 0) + 1;
        $presence->last_seen_at = now();
        $presence->save();
    }

    public function isNewUser(User $user, string $channel = 'public-lobby', int $thresholdMinutes = 10): bool
    {
        $presence = ChatPresence::where('channel', $channel)
            ->where('user_id', $user->id)
            ->first();

        if (! $presence || ! $presence->first_seen_at) {
            return false;
        }

        return $presence->first_seen_at->diffInMinutes(now()) <= $thresholdMinutes;
    }

    public function getMessageCount(User $user, string $channel = 'public-lobby'): int
    {
        return (int) ChatPresence::where('channel', $channel)
            ->where('user_id', $user->id)
            ->value('message_count_today') ?? 0;
    }
}