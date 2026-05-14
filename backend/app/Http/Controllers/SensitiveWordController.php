<?php

namespace App\Http\Controllers;

use App\Models\SensitiveWord;
use App\Models\UserBanRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SensitiveWordController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAdmin($request);

        $request->validate([
            'category' => 'nullable|string',
            'level' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = SensitiveWord::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where('word', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 20);
        $items = $query->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'categories' => SensitiveWord::$categories,
            'levels' => SensitiveWord::$levels,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'word' => 'required|string|max:100|unique:sensitive_words,word',
            'category' => 'nullable|string|in:custom,abuse,violence,porn,politics',
            'level' => 'nullable|string|in:warning,mute,ban',
            'description' => 'nullable|string|max:500',
        ]);

        $word = SensitiveWord::create([
            'word' => $validated['word'],
            'category' => $validated['category'] ?? 'custom',
            'level' => $validated['level'] ?? 'warning',
            'description' => $validated['description'] ?? '',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => '敏感词添加成功',
            'data' => $word,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->requireAdmin($request);

        $word = SensitiveWord::findOrFail($id);

        $validated = $request->validate([
            'word' => 'sometimes|string|max:100|unique:sensitive_words,word,' . $id,
            'category' => 'nullable|string|in:custom,abuse,violence,porn,politics',
            'level' => 'nullable|string|in:warning,mute,ban',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $word->update($validated);

        return response()->json([
            'message' => '敏感词更新成功',
            'data' => $word->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->requireAdmin($request);

        $word = SensitiveWord::findOrFail($id);
        $word->delete();

        return response()->json([
            'message' => '敏感词已删除',
        ]);
    }

    public function toggleActive(Request $request, $id)
    {
        $this->requireAdmin($request);

        $word = SensitiveWord::findOrFail($id);
        $word->update(['is_active' => !$word->is_active]);

        return response()->json([
            'message' => $word->is_active ? '敏感词已启用' : '敏感词已禁用',
            'data' => $word,
        ]);
    }

    public function import(Request $request)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'words' => 'required|string',
            'category' => 'nullable|string|in:custom,abuse,violence,porn,politics',
            'level' => 'nullable|string|in:warning,mute,ban',
        ]);

        $lines = explode("\n", trim($validated['words']));
        $added = 0;
        $skipped = 0;

        DB::transaction(function () use ($lines, $validated, &$added, &$skipped) {
            foreach ($lines as $line) {
                $word = trim($line);
                if (empty($word) || strlen($word) > 100) {
                    $skipped++;
                    continue;
                }

                $exists = SensitiveWord::where('word', $word)->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                SensitiveWord::create([
                    'word' => $word,
                    'category' => $validated['category'] ?? 'custom',
                    'level' => $validated['level'] ?? 'warning',
                    'is_active' => true,
                ]);
                $added++;
            }
        });

        return response()->json([
            'message' => "批量导入完成：新增 {$added} 条，跳过 {$skipped} 条",
            'data' => ['added' => $added, 'skipped' => $skipped],
        ]);
    }

    // ── 封禁记录管理 ──────────────────────────────────────────────

    public function banRecords(Request $request)
    {
        $this->requireAdmin($request);

        $request->validate([
            'type' => 'nullable|in:mute,ban',
            'source' => 'nullable|in:manual,auto,report',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = UserBanRecord::with(['user:id,username,avatar', 'bannedByUser:id,username'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('ban_type', $request->type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = $request->input('per_page', 20);
        $items = $query->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function unbannedUser(Request $request, $id)
    {
        $this->requireAdmin($request);

        $record = UserBanRecord::findOrFail($id);

        if ($record->unbanned_at) {
            return response()->json(['message' => '该用户已解封'], 400);
        }

        DB::transaction(function () use ($record) {
            $record->update(['unbanned_at' => now()]);

            $user = $record->user;
            if ($record->ban_type === 'ban') {
                $user->update([
                    'banned_until' => null,
                    'ban_reason' => null,
                ]);
                $user->tokens()->delete();
            } else {
                \App\Models\ChatChannelMute::where('user_id', $user->id)->delete();
            }
        });

        return response()->json([
            'message' => '用户已解封',
            'data' => $record->fresh(),
        ]);
    }

    public function banStats(Request $request)
    {
        $this->requireAdmin($request);

        $stats = [
            'total_mutes' => UserBanRecord::where('ban_type', 'mute')->count(),
            'total_bans' => UserBanRecord::where('ban_type', 'ban')->count(),
            'auto_mutes' => UserBanRecord::where('ban_type', 'mute')->where('source', 'auto')->count(),
            'auto_bans' => UserBanRecord::where('ban_type', 'ban')->where('source', 'auto')->count(),
            'manual_mutes' => UserBanRecord::where('ban_type', 'mute')->where('source', 'manual')->count(),
            'manual_bans' => UserBanRecord::where('ban_type', 'ban')->where('source', 'manual')->count(),
            'active_mutes' => UserBanRecord::where('ban_type', 'mute')
                ->where('banned_until', '>', now())
                ->whereNull('unbanned_at')
                ->count(),
            'active_bans' => UserBanRecord::where('ban_type', 'ban')
                ->where('banned_until', '>', now())
                ->whereNull('unbanned_at')
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    private function requireAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, '权限不足，需要管理员权限');
        }
    }
}
