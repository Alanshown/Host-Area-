<?php

namespace App\Http\Controllers;

use App\Services\EdgeTTSService;
use Illuminate\Http\Request;

class TTSController extends Controller
{
    public function __construct(
        private EdgeTTSService $tts
    ) {
    }

    /**
     * POST /api/tts/speak
     * Synthesize speech and return audio
     */
    public function speak(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'voice' => 'nullable|string|in:zh-CN-XiaoxiaoNeural,zh-CN-XiaoyiNeural,zh-CN-YunxiNeural,zh-CN-XiaohanNeural',
            'rate' => 'nullable|numeric|min:0.5|max:2.0',
            'pitch' => 'nullable|numeric|min:0.5|max:2.0',
        ]);

        $audioDataUrl = $this->tts->speak($validated['text'], [
            'voice' => $validated['voice'] ?? 'zh-CN-XiaoxiaoNeural',
            'rate' => (float) ($validated['rate'] ?? 1.15),
            'pitch' => (float) ($validated['pitch'] ?? 1.25),
        ]);

        if ($audioDataUrl) {
            return response()->json([
                'success' => true,
                'audio' => $audioDataUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'TTS synthesis failed',
        ], 500);
    }

    /**
     * GET /api/tts/voices
     * Get available voices
     */
    public function voices()
    {
        return response()->json([
            'voices' => $this->tts->getVoices(),
        ]);
    }
}
