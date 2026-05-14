<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\ChatChannelMute;
use App\Models\ChatChannelPreference;
use App\Models\ChatMessage;
use App\Models\ChatMessageRecall;
use App\Models\User;
use App\Models\UserBan;
use App\Services\ChatBotService;
use App\Services\ChatPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    private const CHANNELS = [
        'public-lobby' => [
            'slug' => 'public-lobby',
            'title' => '社区大厅',
            'description' => '开放聊天、机器人协作与实时交流。',
        ],
        'concept' => [
            'slug' => 'concept',
            'title' => '概念聊天室',
            'description' => '概念验证、灵感草图与原型讨论。',
        ],
    ];

    public function __construct(
        private ChatBotService $chatBot,
        private ChatPresenceService $presence
    )
    {
    }

    public function bootstrap(Request $request, ?string $channel = null)
    {
        $channel = $this->resolveChannel($channel);
        $this->presence->heartbeat($request->user(), $channel);

        $payload = $this->chatBot->bootstrap($channel);

        $payload['presence'] = $this->presence->snapshot($channel, $request->user()?->id);
        $payload['channel_meta'] = self::CHANNELS[$channel];
        $payload['settings'] = $this->serializePreference($this->preferenceFor($request->user()->id, $channel));
        $payload['mute_list'] = $request->user()?->role === 'admin' ? $this->activeMutes($channel) : [];

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function messages(Request $request, ?string $channel = null)
    {
        $validated = $request->validate([
            'after_id' => 'nullable|integer|min:0',
        ]);

        $channel = $this->resolveChannel($channel);
        $this->presence->heartbeat($request->user(), $channel);

        return response()->json([
            'data' => [
                'messages' => $this->chatBot->after($channel, (int) ($validated['after_id'] ?? 0)),
                'presence' => $this->presence->snapshot($channel, $request->user()?->id),
            ],
        ]);
    }

    public function store(Request $request, ?string $channel = null)
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:4000',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $channel = $this->resolveChannel($channel);
        $this->assertNotMuted($request->user()->id, $channel);
        $this->presence->heartbeat($request->user(), $channel);

        $streaming = $request->boolean('stream');

        $payload = $this->chatBot->handleIncomingMessage(
            $request->user(),
            $validated['content'] ?? '',
            $request->file('files', []),
            $channel,
            $streaming
        );
        $payload['presence'] = $this->presence->snapshot($channel, $request->user()?->id);

        return response()->json([
            'data' => $payload,
        ], 201);
    }

    /**
     * SSE endpoint – streams a bot reply for a previously saved user message.
     * Frontend calls this with fetch() + ReadableStream after POSTing the user message.
     *
     * GET /api/chat/channel/stream-reply?trigger_id={messageId}
     */
    public function streamBotReply(Request $request, ?string $channel = null)
    {
        $channel   = $this->resolveChannel($channel);
        $triggerId = (int) $request->query('trigger_id', 0);

        if ($triggerId <= 0) {
            return response()->json(['error' => 'trigger_id 必填'], 422);
        }

        $chatBot = $this->chatBot;

        return response()->stream(function () use ($chatBot, $channel, $triggerId) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            @ob_implicit_flush(true);

            $sendEvent = function (string $eventName, array $data) {
                $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                echo "event: {$eventName}\n";
                echo "data: {$json}\n\n";
                flush();
            };

            $botMessage = $chatBot->createBotReplyForStream(
                $channel,
                $triggerId,
                static function (string $delta) {
                    $data = json_encode(['delta' => $delta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    echo "data: {$data}\n\n";
                    flush();
                },
                $sendEvent
            );

            $doneData = json_encode(
                ['message' => $botMessage],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            echo "event: done\n";
            echo "data: {$doneData}\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function heartbeat(Request $request, ?string $channel = null)
    {
        $channel = $this->resolveChannel($channel);
        $this->presence->heartbeat($request->user(), $channel);

        return response()->json([
            'data' => $this->presence->snapshot($channel, $request->user()?->id),
        ]);
    }

    public function typing(Request $request, ?string $channel = null)
    {
        $validated = $request->validate([
            'typing' => 'required|boolean',
        ]);

        $channel = $this->resolveChannel($channel);
        $this->presence->setTyping($request->user(), $channel, (bool) $validated['typing']);

        return response()->json([
            'data' => $this->presence->snapshot($channel, $request->user()?->id),
        ]);
    }

    public function settings(Request $request, ?string $channel = null)
    {
        $channel = $this->resolveChannel($channel);

        return response()->json([
            'data' => [
                'settings' => $this->serializePreference($this->preferenceFor($request->user()->id, $channel)),
                'channel_meta' => self::CHANNELS[$channel],
                'mute_list' => $request->user()?->role === 'admin' ? $this->activeMutes($channel) : [],
            ],
        ]);
    }

    public function defaultChannelSettings(Request $request)
    {
        return $this->settings($request, 'public-lobby');
    }

    public function updateSettings(Request $request, ?string $channel = null)
    {
        $channel = $this->resolveChannel($channel);
        $validated = $request->validate([
            'theme_variant' => 'required|in:classic,claude,custom',
            'hide_bot' => 'required|boolean',
            'custom_background' => 'nullable|image|max:6144',
            'remove_custom_background' => 'nullable|boolean',
        ]);

        $preference = $this->preferenceFor($request->user()->id, $channel);

        $preference->theme_variant = $validated['theme_variant'];
        $preference->hide_bot = (bool) $validated['hide_bot'];

        if (($validated['remove_custom_background'] ?? false) && $preference->custom_background_path) {
            $this->deleteCustomBackground($preference->custom_background_path);
            $preference->custom_background_path = null;
        }

        if ($request->hasFile('custom_background')) {
            if ($preference->custom_background_path) {
                $this->deleteCustomBackground($preference->custom_background_path);
            }

            $preference->custom_background_path = $this->storeCustomBackground($request->file('custom_background'));
        }

        if ($preference->theme_variant !== 'custom' && ($validated['remove_custom_background'] ?? false)) {
            $preference->custom_background_path = null;
        }

        $preference->save();

        return response()->json([
            'data' => [
                'settings' => $this->serializePreference($preference),
            ],
        ]);
    }

    public function updateDefaultChannelSettings(Request $request)
    {
        return $this->updateSettings($request, 'public-lobby');
    }

    public function recallDefaultChannelMessage(Request $request, int $messageId = 0)
    {
        return $this->recallMessage($request, 'public-lobby', $messageId);
    }

    public function mute(Request $request, ?string $channel = null)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $channel = $this->resolveChannel($channel);
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'minutes' => 'required|integer|min:1|max:10080',
        ]);

        if ((int) $validated['user_id'] === (int) $request->user()->id) {
            throw ValidationException::withMessages([
                'user_id' => ['不能对自己执行频道禁言。'],
            ]);
        }

        $mute = ChatChannelMute::updateOrCreate(
            [
                'channel' => $channel,
                'user_id' => (int) $validated['user_id'],
            ],
            [
                'muted_by' => $request->user()->id,
                'muted_until' => now()->addMinutes((int) $validated['minutes']),
            ]
        );

        return response()->json([
            'data' => [
                'mute' => $this->serializeMute($mute->fresh(['user:id,username,avatar', 'mutedBy:id,username'])),
                'mute_list' => $this->activeMutes($channel),
            ],
        ]);
    }

    public function unmute(Request $request, ?string $channel = null, int $userId = 0)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $channel = $this->resolveChannel($channel);

        ChatChannelMute::where('channel', $channel)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'data' => [
                'mute_list' => $this->activeMutes($channel),
            ],
        ]);
    }

    private function resolveChannel(?string $channel): string
    {
        $resolved = trim((string) ($channel ?: 'public-lobby'));

        abort_unless(array_key_exists($resolved, self::CHANNELS), 404);

        return $resolved;
    }

    private function preferenceFor(int $userId, string $channel): ChatChannelPreference
    {
        return ChatChannelPreference::firstOrCreate(
            [
                'channel' => $channel,
                'user_id' => $userId,
            ],
            [
                'theme_variant' => 'classic',
                'hide_bot' => false,
            ]
        );
    }

    private function serializePreference(ChatChannelPreference $preference): array
    {
        return [
            'theme_variant' => $preference->theme_variant,
            'hide_bot' => (bool) $preference->hide_bot,
            'custom_background_path' => $this->publicStorageUrl($preference->custom_background_path),
        ];
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'uploads/')) {
            return '/' . ltrim($path, '/');
        }

        return Storage::disk('public')->url($path);
    }

    private function storeCustomBackground($file): string
    {
        $directory = public_path('uploads/channel-backgrounds');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = uniqid('channel-bg-', true) . '.' . $extension;
        $file->move($directory, $filename);

        return '/uploads/channel-backgrounds/' . $filename;
    }

    private function deleteCustomBackground(?string $path): void
    {
        if (! $path) {
            return;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'uploads/')) {
            $fullPath = public_path($normalizedPath);

            if (is_file($fullPath)) {
                @unlink($fullPath);
            }

            return;
        }

        Storage::disk('public')->delete($normalizedPath);
    }

    private function activeMutes(string $channel): array
    {
        return ChatChannelMute::where('channel', $channel)
            ->where('muted_until', '>', now())
            ->with(['user:id,username,avatar', 'mutedBy:id,username'])
            ->orderBy('muted_until')
            ->get()
            ->map(fn (ChatChannelMute $mute) => $this->serializeMute($mute))
            ->values()
            ->all();
    }

    private function serializeMute(ChatChannelMute $mute): array
    {
        return [
            'user_id' => $mute->user_id,
            'username' => $mute->user?->username,
            'avatar' => $mute->user?->avatar,
            'muted_by' => $mute->mutedBy?->username,
            'muted_until' => optional($mute->muted_until)->toIso8601String(),
        ];
    }

    private function assertNotMuted(int $userId, string $channel): void
    {
        $mute = ChatChannelMute::where('channel', $channel)
            ->where('user_id', $userId)
            ->where('muted_until', '>', now())
            ->first();

        if (! $mute) {
            return;
        }

        throw ValidationException::withMessages([
            'content' => ['你当前已被管理员禁言，解除时间：' . $mute->muted_until?->format('Y-m-d H:i')],
        ]);
    }

    // ── 消息撤回 ──────────────────────────────────────────────────────────

    public function recallMessage(Request $request, string $channel, int $messageId)
    {
        $channel = $this->resolveChannel($channel);
        $user = $request->user();

        $message = ChatMessage::where('channel', $channel)->where('id', $messageId)->first();
        abort_unless($message, 404);

        $isAdmin = $user->role === 'admin';
        $isOwner = (int) $message->user_id === (int) $user->id;
        $targetRole = (string) ($message->author_role ?? '');

        $canRecall = $isAdmin
            ? ($isOwner || $targetRole === 'user')
            : $isOwner;

        if (! $canRecall) {
            abort(403, '无权撤回此消息');
        }

        if ($isOwner && ! $isAdmin) {
            $createdAt = $message->created_at;
            if ($createdAt && $createdAt->diffInSeconds(now()) > 120) {
                return response()->json(['message' => '超过2分钟的消息无法撤回'], 422);
            }
        }

        $recall = DB::transaction(function () use ($channel, $message, $user) {
            $recall = ChatMessageRecall::create([
                'channel' => $channel,
                'message_id' => $message->id,
                'recalled_by' => $user->id,
                'original_author_name' => $message->author_name,
                'original_author_id' => $message->user_id,
            ]);

            $message->delete();

            return $recall;
        });

        return response()->json([
            'data' => [
                'recall' => $this->serializeRecall($recall->fresh(['recalledByUser:id,username,avatar'])),
            ],
        ]);
    }

    // ── 封禁用户（管理员通过 bot 指令触发） ────────────────────────────────

    public function banUser(Request $request, ?string $channel = null)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $channel = $this->resolveChannel($channel);
        $validated = $request->validate([
            'username' => 'required|string|exists:users,username',
            'duration_minutes' => 'required|integer|min:1|max:525600',
            'reason' => 'nullable|string|max:255',
        ]);

        $targetUser = User::where('username', $validated['username'])->first();
        abort_unless($targetUser, 404);

        if ($targetUser->role === 'admin') {
            return response()->json(['message' => '不能封禁管理员'], 422);
        }

        $bannedUntil = now()->addMinutes((int) $validated['duration_minutes']);

        DB::transaction(function () use ($targetUser, $request, $validated, $bannedUntil) {
            $targetUser->update([
                'banned_until' => $bannedUntil,
                'ban_reason' => $validated['reason'] ?? '管理员封禁',
            ]);

            UserBan::create([
                'user_id' => $targetUser->id,
                'banned_by' => $request->user()->id,
                'reason' => $validated['reason'] ?? '管理员封禁',
                'banned_until' => $bannedUntil,
            ]);

            // 删除用户所有 token 强制下线
            $targetUser->tokens()->delete();
        });

        // 在频道发一条系统消息记录封禁
        $banMessage = ChatMessage::create([
            'channel' => $channel,
            'user_id' => null,
            'author_name' => 'system',
            'author_role' => 'system',
            'message_type' => 'system_ban',
            'content' => sprintf(
                '管理员 %s 已将用户 %s 封禁至 %s，理由：%s',
                $request->user()->username,
                $targetUser->username,
                $bannedUntil->format('Y-m-d H:i'),
                $validated['reason'] ?? '违规行为'
            ),
            'attachments' => [],
            'meta' => [
                'action' => 'ban',
                'target_user_id' => $targetUser->id,
                'target_username' => $targetUser->username,
                'banned_until' => $bannedUntil->toIso8601String(),
                'reason' => $validated['reason'] ?? '管理员封禁',
            ],
        ]);

        return response()->json([
            'data' => [
                'ban_message' => $this->serializeMessage($banMessage),
                'banned_until' => $bannedUntil->toIso8601String(),
            ],
        ]);
    }

    // ── 获取撤回记录 ──────────────────────────────────────────────────────

    public function recalls(Request $request, ?string $channel = null)
    {
        $channel = $this->resolveChannel($channel);

        $recalls = ChatMessageRecall::where('channel', $channel)
            ->with('recalledByUser:id,username,avatar')
            ->latest('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'data' => $recalls->map(fn ($r) => $this->serializeRecall($r))->values()->all(),
        ]);
    }

    private function serializeRecall(ChatMessageRecall $recall): array
    {
        return [
            'id' => $recall->id,
            'channel' => $recall->channel,
            'message_id' => $recall->message_id,
            'recalled_by_id' => $recall->recalled_by,
            'recalled_by_name' => $recall->recalledByUser?->username,
            'recalled_by_avatar' => $recall->recalledByUser?->avatar,
            'original_author_name' => $recall->original_author_name,
            'original_author_id' => $recall->original_author_id,
            'created_at' => $recall->created_at?->toIso8601String(),
        ];
    }

    // ── Admin: 更新 bot 头像 ─────────────────────────────────────────

    public function updateBotAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            abort(403, '仅管理员可修改 bot 头像。');
        }

        $request->validate([
            'avatar' => 'required|file|image|max:4096',
        ]);

        $file = $request->file('avatar');
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'png');

        $folder = public_path('uploads/bot');
        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $storedName = 'alma-' . Str::uuid()->toString() . '.' . $ext;
        $file->move($folder, $storedName);
        $publicPath = '/uploads/bot/' . $storedName;

        BotConfig::updateAvatar('alma', $publicPath);

        return response()->json([
            'data' => ['avatar' => $publicPath],
        ]);
    }

    private function serializeMessage($message): array
    {
        return $this->chatBot->serializeMessagePublic($message);
    }
}