<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\SensitiveWord;
use App\Models\User;
use App\Models\UserBanRecord;
use App\Models\ChatChannelMute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModerationInspectionService
{
    protected $siliconFlowService;

    private const MESSAGE_INSPECTION_INTERVAL = 20;

    public function __construct(SiliconFlowService $siliconFlowService)
    {
        $this->siliconFlowService = $siliconFlowService;
    }

    public function checkAndPerformInspection(User $user, string $channel): ?array
    {
        $count = $this->getMessageCountSinceLastInspection($user, $channel);

        if ($count >= self::MESSAGE_INSPECTION_INTERVAL) {
            return $this->performInspection($user, $channel);
        }

        return null;
    }

    protected function getMessageCountSinceLastInspection(User $user, string $channel): int
    {
        $lastInspection = ChatMessage::where('channel', $channel)
            ->where('user_id', $user->id)
            ->where('message_type', 'inspection_report')
            ->latest('id')
            ->first();

        $query = ChatMessage::where('channel', $channel)
            ->where('user_id', $user->id)
            ->where('author_role', '!=', 'bot')
            ->where('author_role', '!=', 'system');

        if ($lastInspection) {
            $query->where('id', '>', $lastInspection->id);
        }

        return $query->count();
    }

    protected function performInspection(User $user, string $channel): array
    {
        $recentMessages = $this->getRecentMessages($user, $channel, self::MESSAGE_INSPECTION_INTERVAL);
        $result = ['action' => 'none', 'violations' => [], 'report' => null];

        if ($recentMessages->isEmpty()) {
            return $result;
        }

        $inspectionResult = $this->inspectWithAI($user, $recentMessages, $channel);
        $keywordMatches = $this->checkKeywords($recentMessages);

        $violations = array_merge($inspectionResult['violations'] ?? [], $keywordMatches);

        if (!empty($violations)) {
            $highestLevel = $this->determineHighestLevel($violations);
            $result = $this->executePunishment($user, $channel, $highestLevel, $violations, $recentMessages);
        }

        $this->logInspectionReport($user, $channel, $result, $recentMessages);

        return $result;
    }

    protected function getRecentMessages(User $user, string $channel, int $limit): \Illuminate\Support\Collection
    {
        return ChatMessage::where('channel', $channel)
            ->where('user_id', $user->id)
            ->whereIn('author_role', ['user', 'admin'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    protected function inspectWithAI(User $user, \Illuminate\Support\Collection $messages, string $channel): array
    {
        $messageTexts = $messages->map(function ($msg, $index) {
            return "[消息{$index}] {$msg->content}";
        })->implode("\n\n");

        $systemPrompt = <<<'EOT'
你是一个内容审核专家，负责检查用户发送的消息是否违规。

【违规定义】
严重违规（必须封禁账号1天）：
1. 直接辱骂、恶意人身攻击、威胁恐吓他人
2. 传播色情内容、露点描写
3. 教唆暴力、传授犯罪方法
4. 政治敏感内容、危害国家安全
5. 恶意造谣、诽谤他人

一般违规（禁言30分钟）：
1. 使用脏话、谐音脏话
2. 轻微骚扰、频繁@他人
3. 发布垃圾广告信息
4. 讨论擦边内容

【现代语言理解】
用户发送的网络梗言或梗图，表面上看起来违规但实际上是开玩笑，属于正常社交互动，不判定为违规。
例如："笑死"、"绝绝子"、"yyds"、"摆烂"、"躺平"等都是正常用语。

【判断逻辑】
请仔细理解用户的真实意图，区分以下情况：
- 真辱骂 vs 开玩笑/调侃（如"笑死你"、"绝绝子"）
- 擦边内容 vs 正常讨论
- 情绪宣泄 vs 恶意攻击

请返回JSON格式：
{
  "violations": [
    {
      "type": "abuse|violence|porn|politics|spam|harassment",
      "severity": "severe|minor",
      "reason": "违规原因说明",
      "message_index": 消息索引
    }
  ],
  "summary": "简要总结检查结果"
}
EOT;

        $userPrompt = "请检查以下用户在频道 {$channel} 发送的消息：\n\n{$messageTexts}";

        try {
            $response = $this->siliconFlowService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], [
                'model' => 'glm-4-flash',
                'temperature' => 0.1,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';

            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                return json_decode($matches[0], true) ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('moderation.inspection_ai_failed', [
                'user_id' => $user->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function checkKeywords(\Illuminate\Support\Collection $messages): array
    {
        $violations = [];
        $activeWords = SensitiveWord::getActiveWords();

        if (empty($activeWords)) {
            return $violations;
        }

        foreach ($messages as $index => $message) {
            $content = $message->content ?? '';

            foreach ($activeWords as $word) {
                if (mb_stripos($content, $word) !== false) {
                    $wordRecord = SensitiveWord::where('word', $word)->first();
                    $violations[] = [
                        'type' => $wordRecord->category ?? 'custom',
                        'severity' => $wordRecord->level === 'ban' ? 'severe' : 'minor',
                        'reason' => "包含敏感词: {$word}",
                        'message_index' => $index,
                        'matched_word' => $word,
                    ];
                }
            }
        }

        return $violations;
    }

    protected function determineHighestLevel(array $violations): string
    {
        $hasSevere = false;

        foreach ($violations as $violation) {
            if (($violation['severity'] ?? 'minor') === 'severe') {
                $hasSevere = true;
                break;
            }
        }

        return $hasSevere ? 'severe' : 'minor';
    }

    protected function executePunishment(User $user, string $channel, string $level, array $violations, \Illuminate\Support\Collection $messages): array
    {
        $isMute = ($level === 'minor');
        $duration = $isMute ? 30 : 1440;

        $violationTypes = array_unique(array_column($violations, 'type'));
        $reason = '严重违规: ' . implode(', ', $violationTypes);

        $evidenceIds = $messages->pluck('id')->toArray();

        if ($isMute) {
            ChatChannelMute::updateOrCreate(
                ['channel' => $channel, 'user_id' => $user->id],
                [
                    'muted_by' => 0,
                    'muted_until' => now()->addMinutes($duration),
                ]
            );

            $messageContent = "@{$user->username} 检测到违规内容，已被禁言{$duration}分钟。请注意文明用语，遵守社区规范。";
        } else {
            $user->update([
                'banned_until' => now()->addDays(1),
                'ban_reason' => $reason,
            ]);

            UserBanRecord::create([
                'user_id' => $user->id,
                'banned_by' => 0,
                'ban_type' => UserBanRecord::TYPE_BAN,
                'reason' => $reason,
                'detail' => '系统自动巡检检测到严重违规',
                'source' => UserBanRecord::SOURCE_AUTO,
                'evidence' => $evidenceIds,
                'banned_until' => now()->addDays(1),
            ]);

            $messageContent = "@{$user->username} 检测到严重违规内容，账号已被封禁1天。请遵守社区规范，共同维护良好环境。";
        }

        $reportMessage = ChatMessage::create([
            'channel' => $channel,
            'user_id' => null,
            'author_name' => 'Alma',
            'author_role' => 'bot',
            'message_type' => 'inspection_report',
            'content' => $messageContent,
            'attachments' => [],
            'meta' => [
                'inspection' => true,
                'target_user_id' => $user->id,
                'target_username' => $user->username,
                'level' => $level,
                'violations' => $violations,
                'is_mute' => $isMute,
            ],
        ]);

        return [
            'action' => $isMute ? 'mute' : 'ban',
            'level' => $level,
            'duration' => $duration,
            'violations' => $violations,
            'report_message_id' => $reportMessage->id,
        ];
    }

    protected function logInspectionReport(User $user, string $channel, array $result, \Illuminate\Support\Collection $messages): void
    {
        Log::info('moderation.inspection_performed', [
            'user_id' => $user->id,
            'username' => $user->username,
            'channel' => $channel,
            'message_count' => $messages->count(),
            'action' => $result['action'],
            'violations_count' => count($result['violations']),
            'level' => $result['level'] ?? null,
        ]);
    }
}
