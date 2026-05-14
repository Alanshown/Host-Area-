<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SiliconFlowService
{
    public function chat(array $messages, array $options = []): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $config = config('services.siliconflow');

        $payload = array_filter([
            'model' => $options['model'] ?? $config['model'],
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $config['max_tokens'],
            'temperature' => $options['temperature'] ?? $config['temperature'],
            'stream' => false,
            'tools' => $options['tools'] ?? null,
            'tool_choice' => $options['tool_choice'] ?? null,
        ], fn ($value) => $value !== null);

        $verifyOption = $this->resolveVerifyOption($config['ca_bundle'] ?? null);
        $result = $this->sendWithRetry(
            (string) $config['api_url'],
            (string) $config['api_key'],
            $payload,
            $verifyOption,
        );

        $json = $result['json'];
        $traceId = $result['trace_id'];

        Log::info('siliconflow.chat.completed', [
            'model' => $payload['model'],
            'trace_id' => $traceId,
            'usage' => $json['usage'] ?? null,
        ]);

        $message = Arr::get($json, 'choices.0.message', []);

        return [
            'raw' => $json,
            'message' => $message,
            'content' => $this->normalizeContent($message['content'] ?? ''),
            'tool_calls' => $message['tool_calls'] ?? [],
            'usage' => $json['usage'] ?? [],
            'trace_id' => $traceId,
            'model' => $json['model'] ?? $payload['model'],
        ];
    }

    /**
     * Stream a chat completion from SiliconFlow, calling $onChunk for each delta.
     * Uses PHP native curl with CURLOPT_WRITEFUNCTION to parse SSE in real time.
     * Returns the complete accumulated content string.
     */
    public function streamChat(array $messages, callable $onChunk, array $options = []): string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $config = config('services.siliconflow');

        $payload = array_filter([
            'model'       => $options['model'] ?? $config['model'],
            'messages'    => $messages,
            'max_tokens'  => $options['max_tokens'] ?? $config['max_tokens'],
            'temperature' => $options['temperature'] ?? $config['temperature'],
            'stream'      => true,
        ], fn ($v) => $v !== null);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Failed to encode SiliconFlow streaming payload.');
        }

        $apiKey      = (string) $config['api_key'];
        $apiUrl      = (string) $config['api_url'];
        $verifyOption = $this->resolveVerifyOption($config['ca_bundle'] ?? null);

        $buffer  = '';
        $content = '';

        $ch = curl_init($apiUrl);

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/event-stream',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE      => \CURL_IPRESOLVE_V4,
            CURLOPT_PROXY          => '',
            CURLOPT_WRITEFUNCTION  => static function ($curl, string $data) use (&$buffer, &$content, $onChunk) {
                $buffer .= $data;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line   = rtrim($line, "\r");

                    if ($line === '' || $line === 'data: [DONE]') {
                        continue;
                    }

                    if (str_starts_with($line, 'data: ')) {
                        $json = json_decode(substr($line, 6), true);
                        if (!is_array($json)) {
                            continue;
                        }
                        $delta = $json['choices'][0]['delta']['content'] ?? '';
                        if ($delta !== '') {
                            $content .= $delta;
                            $onChunk($delta);
                        }
                    }
                }

                return strlen($data);
            },
        ];

        if ($verifyOption !== null) {
            $curlOptions[CURLOPT_CAINFO] = $verifyOption;
        }

        curl_setopt_array($ch, $curlOptions);
        curl_exec($ch);

        $errno = curl_errno($ch);
        if ($errno) {
            $errMsg = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(sprintf('SiliconFlow stream cURL error %d: %s', $errno, $errMsg));
        }

        curl_close($ch);

        return $content;
    }

    public function summarize(string $transcript): ?string
    {
        if (! trim($transcript)) {
            return null;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => '你是 memory-mcp 的摘要器。请把群聊历史压缩成中文摘要，保留关键决策、已知事实、待办、外部来源和未解决问题。输出纯文本，不要使用 Markdown。',
            ],
            [
                'role' => 'user',
                'content' => "请压缩下面这段频道历史：\n\n" . $transcript,
            ],
        ];

        try {
            $result = $this->chat($messages, [
                'max_tokens' => min((int) config('services.siliconflow.max_tokens', 30000), 1800),
                'temperature' => 0.2,
            ]);

            return trim((string) ($result['content'] ?? '')) ?: null;
        } catch (\Throwable $exception) {
            Log::warning('siliconflow.summary.failed', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function normalizeContent($content): string
    {
        if (is_string($content)) {
            return trim($content);
        }

        if (is_array($content)) {
            return collect($content)
                ->map(function ($block) {
                    if (is_string($block)) {
                        return $block;
                    }

                    if (($block['type'] ?? null) === 'text') {
                        return $block['text'] ?? '';
                    }

                    return $block['content'] ?? '';
                })
                ->filter()
                ->implode("\n")
                ?: '';
        }

        return '';
    }

    private function resolveVerifyOption(?string $caBundlePath): ?string
    {
        $path = trim((string) $caBundlePath);

        if ($path === '') {
            return null;
        }

        return is_file($path) ? $path : null;
    }

    private function sendWithRetry(string $url, string $apiKey, array $payload, ?string $verifyOption): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->sendRequest($url, $apiKey, $payload, $verifyOption);
            } catch (\Throwable $exception) {
                $lastException = $exception;

                Log::warning('siliconflow.chat.attempt_failed', [
                    'attempt' => $attempt,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'transport' => $this->transportName(),
                ]);

                if (! $this->shouldRetry($exception, $attempt)) {
                    throw $exception;
                }

                usleep(250000 * $attempt);
            }
        }

        throw $lastException ?? new RuntimeException('SiliconFlow request failed with unknown error.');
    }

    private function sendRequest(string $url, string $apiKey, array $payload, ?string $verifyOption): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        if (function_exists('curl_init')) {
            return $this->sendWithCurl($url, $apiKey, $payload, $verifyOption);
        }

        if ($this->canUseCurlBinary()) {
            return $this->sendWithCurlBinary($url, $apiKey, $payload, $verifyOption);
        }

        return $this->sendWithHttp($url, $apiKey, $payload, $verifyOption);
    }

    private function transportName(): string
    {
        if (function_exists('curl_init')) {
            return 'native-curl';
        }

        if ($this->canUseCurlBinary()) {
            return 'curl-binary';
        }

        return 'laravel-http';
    }

    private function sendWithCurl(string $url, string $apiKey, array $payload, ?string $verifyOption): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Failed to encode SiliconFlow payload.');
        }

        $headers = [];
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$headers) {
                $length = strlen($headerLine);
                $header = explode(':', $headerLine, 2);

                if (count($header) === 2) {
                    $headers[strtolower(trim($header[0]))] = trim($header[1]);
                }

                return $length;
            },
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
            CURLOPT_PROXY => '',
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true,
        ];

        if ($verifyOption !== null) {
            $options[CURLOPT_CAINFO] = $verifyOption;
        }

        curl_setopt_array($ch, $options);

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            $errorNumber = curl_errno($ch);
            $errorMessage = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(sprintf('cURL error %d: %s', $errorNumber, $errorMessage));
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $json = json_decode($rawResponse, true);
        if (! is_array($json)) {
            throw new RuntimeException('SiliconFlow returned invalid JSON: ' . $rawResponse);
        }

        if ($status >= 400) {
            throw new RuntimeException(sprintf('SiliconFlow API request failed with status %d: %s', $status, $rawResponse));
        }

        return [
            'json' => $json,
            'trace_id' => $headers['x-siliconcloud-trace-id'] ?? null,
        ];
    }

    private function sendWithHttp(string $url, string $apiKey, array $payload, ?string $verifyOption): array
    {
        $options = [
            'proxy' => null,
        ];

        if ($verifyOption !== null) {
            $options['verify'] = $verifyOption;
        }

        $response = Http::timeout(90)
            ->acceptJson()
            ->withToken($apiKey)
            ->withOptions($options)
            ->post($url, $payload);

        $response->throw();

        return [
            'json' => $response->json(),
            'trace_id' => $response->header('x-siliconcloud-trace-id'),
        ];
    }

    private function sendWithCurlBinary(string $url, string $apiKey, array $payload, ?string $verifyOption): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Failed to encode SiliconFlow payload.');
        }

        $parts = [
            'curl.exe',
            '--silent',
            '--show-error',
            '--ipv4',
            '--connect-timeout',
            '15',
            '--max-time',
            '90',
            '-X',
            'POST',
            '-H',
            'Accept: application/json',
            '-H',
            'Content-Type: application/json',
            '-H',
            'Authorization: Bearer ' . $apiKey,
            '-D',
            '-',
            '--data-raw',
            $body,
        ];

        if ($verifyOption !== null) {
            $parts[] = '--cacert';
            $parts[] = $verifyOption;
        }

        $parts[] = $url;

        $command = implode(' ', array_map('escapeshellarg', $parts));
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, base_path());
        if (! is_resource($process)) {
            throw new RuntimeException('Failed to start curl.exe process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'curl.exe request failed.');
        }

        [$rawHeaders, $rawBody] = $this->splitCurlBinaryResponse($stdout);
        $status = $this->extractStatusCode($rawHeaders);
        $json = json_decode($rawBody, true);

        if (! is_array($json)) {
            throw new RuntimeException('SiliconFlow returned invalid JSON: ' . $rawBody);
        }

        if ($status >= 400) {
            throw new RuntimeException(sprintf('SiliconFlow API request failed with status %d: %s', $status, $rawBody));
        }

        return [
            'json' => $json,
            'trace_id' => $this->extractTraceId($rawHeaders),
        ];
    }

    private function shouldRetry(\Throwable $exception, int $attempt): bool
    {
        if ($attempt >= 3) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'connection refused')
            || str_contains($message, 'timed out')
            || str_contains($message, 'could not resolve host')
            || str_contains($message, 'failed to connect');
    }

    private function canUseCurlBinary(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' && trim((string) @shell_exec('where curl.exe 2>NUL')) !== '';
    }

    private function splitCurlBinaryResponse(string $response): array
    {
        $separator = "\r\n\r\n";
        $position = strrpos($response, $separator);

        if ($position === false) {
            throw new RuntimeException('Failed to parse curl.exe response headers.');
        }

        return [
            substr($response, 0, $position),
            substr($response, $position + strlen($separator)),
        ];
    }

    private function extractStatusCode(string $rawHeaders): int
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($rawHeaders)) ?: [];

        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function extractTraceId(string $rawHeaders): ?string
    {
        $lines = preg_split('/\r\n|\n|\r/', trim($rawHeaders)) ?: [];

        foreach ($lines as $line) {
            if (stripos($line, 'x-siliconcloud-trace-id:') === 0) {
                return trim(substr($line, strlen('x-siliconcloud-trace-id:')));
            }
        }

        return null;
    }
}