<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TavilySearchService
{
    public function search(string $query): array
    {
        $config = config('services.tavily');
        $normalizedQuery = trim($query);

        if ($normalizedQuery === '') {
            return [
                'query' => '',
                'results' => [],
                'answer' => null,
                'error' => '搜索关键词不能为空。',
            ];
        }

        if (! ($config['api_key'] ?? null)) {
            return [
                'query' => $normalizedQuery,
                'results' => [],
                'answer' => null,
                'error' => 'Tavily API Key 未配置。',
            ];
        }

        try {
            $verifyOption = $this->resolveVerifyOption($config['ca_bundle'] ?? null);
            $response = $this->performSearchRequest($config, $normalizedQuery, $verifyOption);

            $response->throw();
            $payload = $response->json();

            return [
                'query' => $normalizedQuery,
                'answer' => $payload['answer'] ?? null,
                'results' => collect($payload['results'] ?? [])
                    ->map(fn ($item) => [
                        'title' => $item['title'] ?? '',
                        'url' => $item['url'] ?? '',
                        'content' => $item['content'] ?? '',
                        'score' => $item['score'] ?? null,
                    ])
                    ->values()
                    ->all(),
                'error' => null,
                'used_insecure_retry' => false,
            ];
        } catch (\Throwable $exception) {
            $retriedInsecurely = false;

            if ($this->shouldRetryWithoutVerify($exception)) {
                try {
                    $response = $this->performSearchRequest($config, $normalizedQuery, false);
                    $response->throw();
                    $payload = $response->json();
                    $retriedInsecurely = true;

                    Log::warning('tavily.search.insecure_retry', [
                        'query' => $normalizedQuery,
                        'reason' => $exception->getMessage(),
                    ]);

                    return [
                        'query' => $normalizedQuery,
                        'answer' => $payload['answer'] ?? null,
                        'results' => collect($payload['results'] ?? [])
                            ->map(fn ($item) => [
                                'title' => $item['title'] ?? '',
                                'url' => $item['url'] ?? '',
                                'content' => $item['content'] ?? '',
                                'score' => $item['score'] ?? null,
                            ])
                            ->values()
                            ->all(),
                        'error' => null,
                        'used_insecure_retry' => true,
                    ];
                } catch (\Throwable $retryException) {
                    $exception = $retryException;
                }
            }

            Log::warning('tavily.search.failed', [
                'query' => $normalizedQuery,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'has_ca_bundle' => (bool) $this->resolveVerifyOption($config['ca_bundle'] ?? null),
                'retried_insecurely' => $retriedInsecurely,
            ]);

            return [
                'query' => $normalizedQuery,
                'results' => [],
                'answer' => null,
                'error' => '搜索服务暂时不可用，请稍后重试。',
                'diagnostic' => $exception->getMessage(),
            ];
        }
    }

    private function performSearchRequest(array $config, string $query, string|bool|null $verifyOption)
    {
        return Http::timeout(25)
            ->acceptJson()
            ->withOptions($this->resolveHttpOptions($verifyOption))
            ->post($config['api_url'], [
                'api_key' => $config['api_key'],
                'query' => $query,
                'max_results' => $config['max_results'] ?? 7,
                'include_answer' => true,
                'search_depth' => 'advanced',
            ]);
    }

    private function resolveHttpOptions(string|bool|null $verifyOption): array
    {
        $options = [];

        if ($verifyOption !== null) {
            $options['verify'] = $verifyOption;
        }

        return $options;
    }

    private function shouldRetryWithoutVerify(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'ssl')
            || str_contains($message, 'certificate')
            || str_contains($message, 'cainfo')
            || str_contains($message, 'curl error 60');
    }

    private function resolveVerifyOption(?string $caBundlePath): ?string
    {
        $path = trim((string) $caBundlePath);

        if ($path === '') {
            return null;
        }

        return is_file($path) ? $path : null;
    }
}