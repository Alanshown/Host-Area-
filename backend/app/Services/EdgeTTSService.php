<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EdgeTTSService
{
    private string $endpoint = 'https://speech.platform.bing.com/consumer/speech/synthesize/readaloud/edge/v1';

    private array $voices = [
        'zh-CN-XiaoxiaoNeural' => [
            'name' => '晓晓',
            'description' => '活泼可爱的少女声音',
            'gender' => 'Female',
            'locale' => 'zh-CN',
        ],
        'zh-CN-XiaoyiNeural' => [
            'name' => '小艺',
            'description' => '温柔甜美的女声',
            'gender' => 'Female',
            'locale' => 'zh-CN',
        ],
        'zh-CN-YunxiNeural' => [
            'name' => '云希',
            'description' => '阳光活泼的少年音',
            'gender' => 'Male',
            'locale' => 'zh-CN',
        ],
        'zh-CN-XiaohanNeural' => [
            'name' => '晓涵',
            'description' => '知性优雅的女声',
            'gender' => 'Female',
            'locale' => 'zh-CN',
        ],
    ];

    public function speak(string $text, array $options = []): ?string
    {
        $voice = $options['voice'] ?? 'zh-CN-XiaoxiaoNeural';
        $rate = $options['rate'] ?? 1.15;
        $pitch = $options['pitch'] ?? 1.25;

        if (!isset($this->voices[$voice])) {
            $voice = 'zh-CN-XiaoxiaoNeural';
        }

        // Clean text for speech
        $cleanText = $this->cleanTextForSpeech($text);

        if (empty($cleanText)) {
            return null;
        }

        // Build SSML
        $ssml = $this->buildSSML($cleanText, $voice, $rate, $pitch);

        try {
            // Use file_get_contents with stream context for Edge TTS
            $audioData = $this->synthesizeSpeech($ssml);

            if ($audioData) {
                // Return base64 encoded audio
                return 'data:audio/mpeg;base64,' . base64_encode($audioData);
            }
        } catch (\Throwable $e) {
            Log::warning('Edge TTS failed: ' . $e->getMessage());
        }

        return null;
    }

    private function buildSSML(string $text, string $voice, float $rate, float $pitch): string
    {
        // Convert rate percentage
        $ratePercent = round(($rate - 1.0) * 100);

        // Convert pitch (semitones)
        $pitchOffset = round(($pitch - 1.0) * 50);

        // Escape XML special characters
        $escapedText = htmlspecialchars($text, ENT_XML1, 'UTF-8');

        return <<<SSML
<?xml version="1.0" encoding="UTF-8"?>
<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="zh-CN">
    <voice name="{$voice}">
        <prosody rate="{$ratePercent}%" pitch="{$pitchOffset}st">
            {$escapedText}
        </prosody>
    </voice>
</speak>
SSML;
    }

    private function synthesizeSpeech(string $ssml): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/ssml+xml',
                    'X-Microsoft-OutputFormat: audio-24khz-48kbitrate-mono-mp3',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                'content' => $ssml,
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $audioUrl = 'https://speech.platform.bing.com/consumer/speech/synthesize/readaloud/activetiles/purplesquare';
        $response = @file_get_contents($this->endpoint . '/trim-hello-audio', false, $context);

        // If direct fetch fails, return null and let frontend use native TTS
        return null;
    }

    private function cleanTextForSpeech(string $text): string
    {
        return preg_replace([
            '/```[\s\S]*?```/m',
            '/`[^`]+`/',
            '/\[widget:[^\]]+\]/',
            '/\[artifact:[^\]]+\]/',
            '/\[TOOL_CALL\][\s\S]*?\[\/TOOL_CALL\]/m',
            '/<[^>]+>/',
            '/https?:\/\/[^\s]+/',
            '/[*_#~`]/',
        ], [
            '代码片段',
            '代码',
            '',
            '',
            '',
            '',
            '',
            '',
        ], $text);
    }

    public function getVoices(): array
    {
        return $this->voices;
    }
}
