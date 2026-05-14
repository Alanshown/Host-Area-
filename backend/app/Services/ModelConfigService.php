<?php

namespace App\Services;

use App\Support\ProjectEnv;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ModelConfigService
{
    private const CACHE_KEY = 'model_config_updated';
    private const CACHE_TTL = 60; // 60 seconds cache

    /**
     * 获取当前模型配置
     */
    public function getCurrentConfig(): array
    {
        return [
            'model' => ProjectEnv::get('SILICONFLOW_MODEL', 'Pro/MiniMaxAI/MiniMax-M2.5'),
            'vision_model' => ProjectEnv::get('SILICONFLOW_VISION_MODEL', 'zai-org/GLM-4.5V'),
            'max_tokens' => ProjectEnv::get('SILICONFLOW_MAX_TOKENS', 30000),
            'temperature' => ProjectEnv::get('SILICONFLOW_TEMPERATURE', 0.7),
        ];
    }

    /**
     * 获取支持的模型列表
     */
    public function getSupportedModels(): array
    {
        return [
            // 通用模型
            'chat' => [
                ['id' => 'Pro/MiniMaxAI/MiniMax-M2.5', 'name' => 'MiniMax-M2.5 (Pro)', 'description' => '高速对话模型'],
                ['id' => 'Pro/THUDM/glm-4-0520', 'name' => 'GLM-4 (Pro)', 'description' => '高性能对话模型'],
                ['id' => 'Pro/THUDM/glm-4-plus', 'name' => 'GLM-4-Plus (Pro)', 'description' => '最新高性能模型'],
                ['id' => 'Pro/deepseek-ai/DeepSeek-V3', 'name' => 'DeepSeek-V3 (Pro)', 'description' => '深度思考模型'],
                ['id' => 'Pro/Qwen/Qwen2.5-72B-Instruct', 'name' => 'Qwen2.5-72B (Pro)', 'description' => '大参数开源模型'],
                ['id' => 'Standard/THUDM/glm-4-flash', 'name' => 'GLM-4-Flash (Standard)', 'description' => '快速响应模型'],
                ['id' => 'Standard/deepseek-ai/DeepSeek-V3', 'name' => 'DeepSeek-V3 (Standard)', 'description' => '高性价比模型'],
            ],
            // 视觉模型
            'vision' => [
                ['id' => 'zai-org/GLM-4V-Plus', 'name' => 'GLM-4V-Plus', 'description' => '最新视觉模型'],
                ['id' => 'zai-org/GLM-4.5V', 'name' => 'GLM-4.5V', 'description' => '高性能视觉模型'],
                ['id' => 'Pro/THUDM/glm-4v-9b', 'name' => 'GLM-4V-9B (Pro)', 'description' => '轻量级视觉模型'],
                ['id' => 'Pro/Qwen/Qwen2.5-VL-72B-Instruct', 'name' => 'Qwen2.5-VL-72B (Pro)', 'description' => '大参数视觉模型'],
            ],
        ];
    }

    /**
     * 更新模型配置
     */
    public function updateModel(string $modelType, string $modelId): array
    {
        $key = $modelType === 'chat' ? 'SILICONFLOW_MODEL' : 'SILICONFLOW_VISION_MODEL';

        // 验证模型ID
        $supported = $this->getSupportedModels()[$modelType] ?? [];
        $isValid = collect($supported)->contains('id', $modelId);

        if (!$isValid) {
            return [
                'success' => false,
                'error' => "不支持的{$modelType}模型: {$modelId}",
            ];
        }

        $result = $this->updateEnvValue($key, $modelId);

        if ($result) {
            // 清除缓存
            Cache::forget(self::CACHE_KEY);

            Log::info('Model config updated', [
                'type' => $modelType,
                'key' => $key,
                'value' => $modelId,
            ]);

            return [
                'success' => true,
                'message' => "已切换到 {$modelId}",
                'old_config' => $this->getCurrentConfig(),
            ];
        }

        return [
            'success' => false,
            'error' => '配置更新失败',
        ];
    }

    /**
     * 更新 .env 文件中的值
     */
    private function updateEnvValue(string $key, string $value): bool
    {
        $path = base_path('.env');

        if (!is_file($path)) {
            Log::error('.env file not found', ['path' => $path]);
            return false;
        }

        // 读取文件
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $found = false;
        $updated = false;

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);

            // 跳过注释和空行
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // 解析 KEY=VALUE 格式
            if (!str_contains($line, '=')) {
                continue;
            }

            [$existingKey] = explode('=', $line, 2);
            $existingKey = trim($existingKey);

            if ($existingKey === $key) {
                // 构建新行
                $quote = '';
                $newValue = $value;

                // 如果原值有引号，保持引号
                $valuePart = substr($line, strpos($line, '=') + 1);
                if (preg_match('/^["\']/', trim($valuePart))) {
                    $quote = trim($valuePart)[0];
                }

                if ($quote) {
                    $newLine = "{$key}={$quote}{$value}{$quote}";
                } else {
                    $newLine = "{$key}={$value}";
                }

                $lines[$i] = $newLine;
                $found = true;
                $updated = true;
                break;
            }
        }

        if (!$found) {
            // 添加新行
            $lines[] = "{$key}={$value}";
            $updated = true;
        }

        if ($updated) {
            // 写回文件
            $newContent = implode("\n", $lines);
            $result = file_put_contents($path, $newContent);

            if ($result === false) {
                Log::error('Failed to write .env file', ['path' => $path]);
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * 验证配置是否更新成功
     */
    public function verifyUpdate(string $modelType, string $expectedModelId): bool
    {
        // 清除缓存以获取最新值
        Cache::forget(self::CACHE_KEY);

        $current = $this->getCurrentConfig();
        $key = $modelType === 'chat' ? 'model' : 'vision_model';

        return $current[$key] === $expectedModelId;
    }
}
