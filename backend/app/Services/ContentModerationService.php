<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ContentModerationService
{
    protected $siliconFlowService;
    protected $tavilyService;

    // Content moderation categories
    protected const CATEGORIES = [
        'spam' => [
            'name' => '垃圾信息',
            'keywords' => ['广告', '推广', '链接', '微信', 'QQ群', '刷单'],
            'weight' => 0.3,
        ],
        'politics' => [
            'name' => '政治敏感',
            'keywords' => ['国家领导人', '政治', '政府', '敏感词'],
            'weight' => 0.4,
        ],
        'porn' => [
            'name' => '色情低俗',
            'keywords' => ['色情', '裸', '性感', '成人'],
            'weight' => 0.5,
        ],
        'violence' => [
            'name' => '暴力血腥',
            'keywords' => ['杀人', '暴力', '恐怖', '血腥'],
            'weight' => 0.4,
        ],
        'abuse' => [
            'name' => '恶意攻击',
            'keywords' => ['傻逼', '智障', '脑残', '废物', '滚'],
            'weight' => 0.3,
        ],
        'privacy' => [
            'name' => '隐私泄露',
            'keywords' => ['手机号', '身份证', '住址', '密码'],
            'weight' => 0.3,
        ],
    ];

    public function __construct(SiliconFlowService $siliconFlowService, TavilySearchService $tavilyService)
    {
        $this->siliconFlowService = $siliconFlowService;
        $this->tavilyService = $tavilyService;
    }

    /**
     * Analyze content for moderation
     * Returns moderation result with categories and scores
     */
    public function analyzeContent(string $content, string $type = 'post'): array
    {
        $result = [
            'passed' => true,
            'categories' => [],
            'scores' => [],
            'suggestions' => [],
            'reason' => '',
        ];

        $totalScore = 0;
        $matchedCategories = [];

        // Keyword-based quick check
        foreach (self::CATEGORIES as $key => $category) {
            $matches = $this->checkKeywords($content, $category['keywords']);
            if (!empty($matches)) {
                $score = min(count($matches) * 0.3, 1.0) * $category['weight'];
                $totalScore += $score;
                $matchedCategories[$key] = [
                    'name' => $category['name'],
                    'matches' => $matches,
                    'score' => round($score, 3),
                ];
            }
        }

        // AI-powered deep analysis
        $aiResult = $this->analyzeWithAI($content, $type);
        if (!empty($aiResult['violations'])) {
            foreach ($aiResult['violations'] as $violation) {
                $key = $violation['category'];
                $score = $violation['severity'] * (self::CATEGORIES[$key]['weight'] ?? 0.5);
                $totalScore += $score;
                if (!isset($matchedCategories[$key])) {
                    $matchedCategories[$key] = [
                        'name' => self::CATEGORIES[$key]['name'] ?? $violation['category'],
                        'matches' => [$violation['reason']],
                        'score' => round($score, 3),
                    ];
                }
            }
            if (!empty($aiResult['suggestions'])) {
                $result['suggestions'] = $aiResult['suggestions'];
            }
        }

        // Normalize score to 0-1 range
        $totalScore = min($totalScore, 1.0);
        $result['scores'] = $matchedCategories;
        $result['totalScore'] = round($totalScore, 3);

        // Determine pass/fail
        if ($totalScore >= 0.7) {
            $result['passed'] = false;
            $result['reason'] = $this->generateReason($matchedCategories);
        } elseif ($totalScore >= 0.4) {
            // Flag for manual review
            $result['requires_review'] = true;
            $result['reason'] = $this->generateReason($matchedCategories);
        }

        return $result;
    }

    /**
     * Check content against keyword list
     */
    protected function checkKeywords(string $content, array $keywords): array
    {
        $matches = [];
        foreach ($keywords as $keyword) {
            if (mb_strpos($content, $keyword) !== false) {
                $matches[] = $keyword;
            }
        }
        return array_unique($matches);
    }

    /**
     * Use AI to analyze content for violations
     */
    protected function analyzeWithAI(string $content, string $type): array
    {
        $systemPrompt = <<<EOT
你是一个内容审核专家。你的任务是分析用户生成的内容，判断是否包含违规信息。

请检查以下类型的违规：
1. spam - 垃圾广告信息
2. politics - 政治敏感内容
3. porn - 色情低俗内容
4. violence - 暴力恐怖内容
5. abuse - 恶意人身攻击
6. privacy - 隐私泄露风险

对于每种违规类型，给出严重程度(0-1)和违规原因。

输出格式（JSON）：
{
  "violations": [
    {"category": "spam", "severity": 0.8, "reason": "包含广告链接"}
  ],
  "suggestions": ["建议删除联系方式"]
}

如果没有违规，返回空的violations数组。
只返回JSON，不要其他内容。
EOT;

        $userPrompt = "请分析以下{$type}内容：\n\n{$content}";

        try {
            $response = $this->siliconFlowService->chat(
                [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                [
                    'model' => 'glm-4-flash',
                    'temperature' => 0.1,
                ]
            );

            $content = $response['choices'][0]['message']['content'] ?? '';

            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                return json_decode($matches[0], true) ?? [];
            }

            return [];
        } catch (\Exception $e) {
            \Log::warning('Content moderation AI analysis failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate human-readable reason for moderation result
     */
    protected function generateReason(array $matchedCategories): string
    {
        if (empty($matchedCategories)) {
            return '内容可能包含违规信息，建议修改';
        }

        $reasons = array_column($matchedCategories, 'name');
        return '检测到：' . implode('、', $reasons);
    }

    /**
     * Batch analyze multiple content items
     */
    public function batchAnalyze(array $items): array
    {
        $results = [];
        foreach ($items as $id => $content) {
            $results[$id] = $this->analyzeContent($content);
        }
        return $results;
    }

    /**
     * Get moderation statistics
     */
    public function getStats(): array
    {
        return [
            'pending' => DB::table('moderation_queue')->where('status', 'pending')->count(),
            'approved' => DB::table('moderation_queue')->where('status', 'approved')->count(),
            'rejected' => DB::table('moderation_queue')->where('status', 'rejected')->count(),
            'categories' => DB::table('moderation_queue')
                ->select('category', DB::raw('count(*) as count'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
        ];
    }
}
