<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CollabService
{
    /**
     * Create a new collaboration draft
     */
    public function createDraft(int $userId, string $channel = 'public-lobby', int $postId = null): array
    {
        $user = DB::table('users')->find($userId);

        $draft = DB::table('collab_drafts')->insertGetId([
            'post_id' => $postId,
            'owner_id' => $userId,
            'channel' => $channel,
            'content' => '',
            'cursors' => json_encode([]),
            'participants' => json_encode([
                [
                    'user_id' => $userId,
                    'username' => $user->username,
                    'joined_at' => now()->toIso8601String(),
                ]
            ]),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->getDraft($draft);
    }

    /**
     * Get draft by ID
     */
    public function getDraft(int $draftId): array
    {
        $draft = DB::table('collab_drafts')
            ->leftJoin('users', 'collab_drafts.owner_id', '=', 'users.id')
            ->select('collab_drafts.*', 'users.username as owner_username')
            ->find($draftId);

        if (!$draft) {
            return [];
        }

        return $this->formatDraft($draft);
    }

    /**
     * Join an existing collaboration
     */
    public function joinDraft(int $draftId, int $userId): array
    {
        $user = DB::table('users')->find($userId);
        $draft = DB::table('collab_drafts')->find($draftId);

        if (!$draft || $draft->expires_at && now()->gt($draft->expires_at)) {
            return ['error' => 'Draft expired or not found'];
        }

        $participants = json_decode($draft->participants ?? '[]', true);

        // Check if already a participant
        $exists = false;
        foreach ($participants as &$p) {
            if ($p['user_id'] === $userId) {
                $p['joined_at'] = now()->toIso8601String();
                $exists = true;
                break;
            }
        }
        unset($p);

        if (!$exists) {
            $participants[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'joined_at' => now()->toIso8601String(),
            ];
        }

        DB::table('collab_drafts')
            ->where('id', $draftId)
            ->update([
                'participants' => json_encode($participants),
                'updated_at' => now(),
            ]);

        return $this->getDraft($draftId);
    }

    /**
     * Update draft content
     */
    public function updateContent(int $draftId, int $userId, string $content, array $cursor = null): array
    {
        $draft = DB::table('collab_drafts')->find($draftId);

        if (!$draft) {
            return ['error' => 'Draft not found'];
        }

        $updates = [
            'content' => $content,
            'updated_at' => now(),
        ];

        // Update cursor position
        if ($cursor !== null) {
            $cursors = json_decode($draft->cursors ?? '{}', true);
            $cursors[$userId] = $cursor;
            $updates['cursors'] = json_encode($cursors);
        }

        DB::table('collab_drafts')
            ->where('id', $draftId)
            ->update($updates);

        return $this->getDraft($draftId);
    }

    /**
     * Update cursor position
     */
    public function updateCursor(int $draftId, int $userId, array $position): array
    {
        $draft = DB::table('collab_drafts')->find($draftId);

        if (!$draft) {
            return ['error' => 'Draft not found'];
        }

        $cursors = json_decode($draft->cursors ?? '{}', true);
        $cursors[$userId] = array_merge($cursors[$userId] ?? [], [
            'line' => $position['line'] ?? 0,
            'column' => $position['column'] ?? 0,
            'selection' => $position['selection'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ]);

        DB::table('collab_drafts')
            ->where('id', $draftId)
            ->update([
                'cursors' => json_encode($cursors),
                'updated_at' => now(),
            ]);

        return [
            'user_id' => $userId,
            'cursor' => $cursors[$userId],
        ];
    }

    /**
     * Leave a collaboration
     */
    public function leaveDraft(int $draftId, int $userId): bool
    {
        $draft = DB::table('collab_drafts')->find($draftId);

        if (!$draft) {
            return false;
        }

        $participants = json_decode($draft->participants ?? '[]', true);
        $participants = array_values(array_filter($participants, function ($p) use ($userId) {
            return $p['user_id'] !== $userId;
        }));

        $cursors = json_decode($draft->cursors ?? '{}', true);
        unset($cursors[$userId]);

        DB::table('collab_drafts')
            ->where('id', $draftId)
            ->update([
                'participants' => json_encode(array_values($participants)),
                'cursors' => json_encode($cursors),
                'updated_at' => now(),
            ]);

        return true;
    }

    /**
     * Get all active drafts for a channel
     */
    public function getChannelDrafts(string $channel): array
    {
        $drafts = DB::table('collab_drafts')
            ->leftJoin('users', 'collab_drafts.owner_id', '=', 'users.id')
            ->select('collab_drafts.*', 'users.username as owner_username')
            ->where('channel', $channel)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        return $drafts->map(fn($draft) => $this->formatDraft($draft))->toArray();
    }

    /**
     * Delete a draft
     */
    public function deleteDraft(int $draftId, int $userId): bool
    {
        $draft = DB::table('collab_drafts')->find($draftId);

        if (!$draft) {
            return false;
        }

        // Only owner can delete
        if ($draft->owner_id !== $userId) {
            return false;
        }

        DB::table('collab_drafts')->where('id', $draftId)->delete();
        return true;
    }

    /**
     * Cleanup expired drafts
     */
    public function cleanupExpired(): int
    {
        return DB::table('collab_drafts')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Format draft for API response
     */
    protected function formatDraft($draft): array
    {
        return [
            'id' => $draft->id,
            'post_id' => $draft->post_id,
            'owner_id' => $draft->owner_id,
            'owner_username' => $draft->owner_username ?? '',
            'channel' => $draft->channel,
            'content' => $draft->content ?? '',
            'cursors' => json_decode($draft->cursors ?? '{}', true),
            'participants' => json_decode($draft->participants ?? '[]', true),
            'expires_at' => $draft->expires_at,
            'created_at' => $draft->created_at,
            'updated_at' => $draft->updated_at,
        ];
    }
}
