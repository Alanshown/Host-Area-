/**
 * Text-to-Speech composable for Alma's voice
 * Uses Edge TTS (Microsoft's Cognitive Services) for cute loli voice
 */

interface TTSOptions {
  rate?: number
  pitch?: number
  volume?: number
  voice?: string
}

// Edge TTS voices - cute and soft
const EDGE_TTS_VOICES = {
  // Primary cute loli voice
  'zh-CN-XiaoxiaoNeural': {
    name: '晓晓',
    description: '活泼可爱的少女声音',
    gender: 'Female',
    lang: 'zh-CN',
  },
  // Alternative cute voices
  'zh-CN-XiaoyiNeural': {
    name: '小艺',
    description: '温柔甜美的女声',
    gender: 'Female',
    lang: 'zh-CN',
  },
  'zh-CN-YunxiNeural': {
    name: '云希',
    description: '阳光活泼的少年音',
    gender: 'Male',
    lang: 'zh-CN',
  },
  // More options
  'zh-CN-XiaohanNeural': {
    name: '晓涵',
    description: '知性优雅的女声',
    gender: 'Female',
    lang: 'zh-CN',
  },
}

export const useTTS = () => {
  const isSpeaking = ref(false)
  const isSupported = ref(true) // Assume supported, fallback to native
  const currentVoice = ref('zh-CN-XiaoxiaoNeural')
  const isUsingEdgeTTS = ref(false)
  let abortController: AbortController | null = null
  let audioContext: AudioContext | null = null

  // Clean text for TTS (remove markdown, code blocks, etc.)
  const cleanTextForSpeech = (text: string): string => {
    if (!text) return ''

    return text
      // Remove code blocks
      .replace(/```[\s\S]*?```/g, '代码片段')
      // Remove inline code
      .replace(/`[^`]+`/g, '代码')
      // Remove markdown links but keep text
      .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
      // Remove markdown headers
      .replace(/^#+\s*/gm, '')
      // Remove bold/italic markers
      .replace(/[*_]{1,3}([^*_]+)[*_]{1,3}/g, '$1')
      // Remove bullet points
      .replace(/^[-*+]\s+/gm, '，')
      // Remove numbered lists
      .replace(/^\d+\.\s+/gm, '，')
      // Remove widget blocks
      .replace(/\[widget:([^\]]+)\]/g, '')
      // Remove artifact blocks
      .replace(/\[artifact:([^\]]+)\]/g, '')
      // Remove HTML tags
      .replace(/<[^>]+>/g, '')
      // Clean up extra whitespace
      .replace(/\s+/g, ' ')
      .trim()
  }

  // Get Edge TTS audio using WebSocket
  const getEdgeTTSAudio = async (
    text: string,
    options: TTSOptions = {}
  ): Promise<ArrayBuffer | null> => {
    const voice = options.voice || currentVoice.value
    const rate = options.rate !== undefined ? options.rate : 1.0
    const pitch = options.pitch !== undefined ? options.pitch : 1.0

    // Edge TTS WebSocket URL
    const endpoint = 'wss://speech.platform.bing.com/consumer/speech/synthesize/readaloud/edge/v1'

    // Generate SSML for better control
    const ssml = `
      <speak version='1.0' xmlns='https://www.w3.org/2001/10/synthesis' xml:lang='zh-CN'>
        <voice name='${voice}'>
          <prosody rate='${(rate - 1) * 100}%' pitch='${(pitch - 1) * 50}%'>
            ${text.replace(/[<>]/g, '')}
          </prosody>
        </voice>
      </speak>
    `

    return new Promise((resolve) => {
      try {
        // Use fetch with WebSocket-like approach via edge-tts-proxy
        // Since direct WS might have CORS issues, we'll use a workaround

        // Create audio element and play directly
        const audio = new Audio()
        const mediaSource = new MediaSource()

        // Edge TTS direct URL (simplified approach)
        // Using a proxy or direct SSML request
        const url = `https://speech.platform.bing.com/consumer/speech/synthesize/readaloud/connect`

        // For now, we'll use a simple approach with audio element
        // The ideal solution would be a backend proxy, but for demo we'll use native TTS as fallback

        resolve(null)
      } catch {
        resolve(null)
      }
    })
  }

  // Use native TTS with best available voice
  const speakWithNativeTTS = (text: string, options: TTSOptions = {}): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (!('speechSynthesis' in window)) {
        reject(new Error('TTS not supported'))
        return
      }

      // Stop any current speech
      window.speechSynthesis.cancel()

      const utterance = new SpeechSynthesisUtterance(text)

      // Try to find best Chinese female voice
      const voices = window.speechSynthesis.getVoices()
      const preferredVoices = [
        // Microsoft Xiaoxiao
        /xiaoxiao.*neural/i,
        /晓晓/i,
        // Microsoft Xiaoyi
        /xiaoyi.*neural/i,
        /小艺/i,
        // Other Chinese female
        /zh-CN.*female/i,
        /chinese.*female/i,
        /microsoft.*zh.*female/i,
        // Google Chinese
        /google.*zh.*fe/i,
        /zh-TW.*fe/i,
        // Japanese cute voices
        /ja.*fe/i,
        / japanese.*fe/i,
        // Fallback to any Chinese
        /zh/i,
      ]

      let selectedVoice: SpeechSynthesisVoice | null = null
      for (const pattern of preferredVoices) {
        const match = voices.find((v) => pattern.test(v.name))
        if (match) {
          selectedVoice = match
          break
        }
      }

      if (selectedVoice) {
        utterance.voice = selectedVoice
        utterance.lang = selectedVoice.lang
      }

      // Cute voice parameters
      utterance.rate = options.rate ?? 1.15 // Slightly faster
      utterance.pitch = options.pitch ?? 1.25 // Higher pitch for cuteness
      utterance.volume = options.volume ?? 1.0

      utterance.onstart = () => {
        isSpeaking.value = true
      }

      utterance.onend = () => {
        isSpeaking.value = false
        resolve()
      }

      utterance.onerror = (event) => {
        isSpeaking.value = false
        if (event.error !== 'interrupted' && event.error !== 'canceled') {
          reject(new Error(event.error))
        } else {
          resolve()
        }
      }

      window.speechSynthesis.speak(utterance)
    })
  }

  // Use Edge TTS via proxy (recommended for production)
  const speakWithEdgeTTS = async (text: string, options: TTSOptions = {}): Promise<void> => {
    abortController = new AbortController()

    try {
      // Call backend proxy for Edge TTS
      const response = await fetch('/api/tts/speak', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          text,
          voice: options.voice || currentVoice.value,
          rate: options.rate ?? 1.15,
          pitch: options.pitch ?? 1.25,
        }),
        signal: abortController.signal,
      })

      if (!response.ok) {
        throw new Error('TTS request failed')
      }

      const audioBlob = await response.blob()
      const audioUrl = URL.createObjectURL(audioBlob)
      const audio = new Audio(audioUrl)

      isSpeaking.value = true

      await new Promise<void>((resolve, reject) => {
        audio.onended = () => {
          isSpeaking.value = false
          URL.revokeObjectURL(audioUrl)
          resolve()
        }

        audio.onerror = () => {
          isSpeaking.value = false
          URL.revokeObjectURL(audioUrl)
          reject(new Error('Audio playback failed'))
        }

        audio.play()
      })
    } catch (error: any) {
      if (error.name === 'AbortError') {
        isSpeaking.value = false
        return
      }
      throw error
    }
  }

  // Main speak function - tries Edge TTS first, falls back to native
  const speak = async (text: string, options: TTSOptions = {}): Promise<void> => {
    if (import.meta.server) return

    const cleanText = cleanTextForSpeech(text)
    if (!cleanText) return

    try {
      // Try Edge TTS first (via backend proxy)
      await speakWithEdgeTTS(cleanText, options)
      isUsingEdgeTTS.value = true
    } catch (error) {
      console.warn('Edge TTS failed, falling back to native TTS:', error)
      isUsingEdgeTTS.value = false
      // Fallback to native TTS
      await speakWithNativeTTS(cleanText, options)
    }
  }

  // Stop current speech
  const stop = () => {
    if (import.meta.server) return

    // Stop Edge TTS
    if (abortController) {
      abortController.abort()
      abortController = null
    }

    // Stop native TTS
    if (window.speechSynthesis) {
      window.speechSynthesis.cancel()
    }

    isSpeaking.value = false
  }

  // Toggle speech
  const toggle = async (text: string): Promise<boolean> => {
    if (isSpeaking.value) {
      stop()
      return false
    } else {
      await speak(text)
      return true
    }
  }

  // Set voice
  const setVoice = (voice: string) => {
    if (EDGE_TTS_VOICES[voice as keyof typeof EDGE_TTS_VOICES]) {
      currentVoice.value = voice
    }
  }

  // Get available voices
  const getAvailableVoices = () => {
    return Object.entries(EDGE_TTS_VOICES).map(([id, voice]) => ({
      id,
      ...voice,
    }))
  }

  // Initialize TTS voices (handle async voice loading)
  const initVoices = (): Promise<void> => {
    if (import.meta.server) return Promise.resolve()

    return new Promise((resolve) => {
      if (!('speechSynthesis' in window)) {
        isSupported.value = false
        resolve()
        return
      }

      // Some browsers load voices asynchronously
      const loadVoices = () => {
        const voices = window.speechSynthesis.getVoices()
        if (voices.length > 0) {
          resolve()
        } else {
          // Try again shortly
          setTimeout(loadVoices, 100)
        }
      }

      // Check if voices are already loaded
      const voices = window.speechSynthesis.getVoices()
      if (voices.length > 0) {
        resolve()
      } else {
        // Listen for voiceschanged event
        window.speechSynthesis.onvoiceschanged = () => {
          window.speechSynthesis.onvoiceschanged = null
          resolve()
        }
        // Fallback timeout
        setTimeout(resolve, 2000)
      }
    })
  }

  return {
    isSpeaking: readonly(isSpeaking),
    isSupported: readonly(isSupported),
    isUsingEdgeTTS: readonly(isUsingEdgeTTS),
    currentVoice: readonly(currentVoice),
    speak,
    stop,
    toggle,
    setVoice,
    getAvailableVoices,
    cleanTextForSpeech,
    initVoices,
  }
}
