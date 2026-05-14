<?php

namespace App\Services;

use App\Models\ChatMemory;
use Illuminate\Support\Collection;

class MemoryMcpService
{
    public function __construct(private SiliconFlowService $siliconFlow)
    {
    }

    public function latest(string $channel): ?ChatMemory
    {
        return ChatMemory::where('channel', $channel)->latest('id')->first();
    }

    public function recall(string $channel, string $topic = ''): array
    {
        $query = ChatMemory::where('channel', $channel)->latest('id');

        if ($topic !== '') {
            $query->where('summary', 'like', '%' . $topic . '%');
        }

        return $query->take(3)->get(['summary', 'last_message_id', 'updated_at'])->toArray();
    }

    public function remember(string $channel, Collection $messages): ?ChatMemory
    {
        if ($messages->isEmpty()) {
            return null;
        }

        $transcript = $messages
            ->map(function ($message) {
                $content = trim((string) ($message->content ?? ''));
                return sprintf('[%s][%s] %s', $message->created_at, $message->author_name, $this->limitText($content, 260));
            })
            ->implode("\n");

        $summary = $this->siliconFlow->summarize($transcript) ?: $this->fallbackSummary($messages);

        return ChatMemory::updateOrCreate(
            ['channel' => $channel],
            [
                'last_message_id' => $messages->max('id'),
                'summary' => $summary,
                'meta' => [
                    'message_count' => $messages->count(),
                ],
            ]
        );
    }

    private function fallbackSummary(Collection $messages): string
    {
        $lines = $messages
            ->take(8)
            ->map(fn ($message) => sprintf('%s：%s', $message->author_name, $this->limitText(trim((string) $message->content), 120)))
            ->implode('；');

        return '近期频道摘要：' . $lines;
    }

    private function limitText(string $value, int $limit): string
    {
        $text = trim($value);

        if ($text === '' || $limit <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }

            return rtrim(mb_substr($text, 0, max($limit - 3, 1), 'UTF-8')) . '...';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(substr($text, 0, max($limit - 3, 1))) . '...';
    }
}