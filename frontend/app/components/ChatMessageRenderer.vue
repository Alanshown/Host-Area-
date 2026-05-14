<template>
  <div class="hall-message-renderer" :class="{ 'is-bot': isBot }">
    <!-- User text with @mention highlighting -->
    <div v-if="!isBot" class="hall-message-text">
      <template v-for="(segment, idx) in formattedUserSegments" :key="idx">
        <span v-if="segment.type === 'mention'" :class="segment.class">{{ segment.text }}</span>
        <span v-else>{{ segment.text }}</span>
      </template>
    </div>

    <!-- Bot: mixed markdown + interactive widget segments -->
    <template v-else>
      <template v-for="(segment, idx) in botSegments" :key="idx">
        <!-- Plain markdown segment -->
        <div
          v-if="segment.type === 'markdown'"
          class="hall-message-markdown"
          v-html="segment.html"
          @click="handleMarkupClick"
        />

        <!-- Poll widget -->
        <div v-else-if="segment.type === 'poll'" class="hall-widget hall-widget--poll">
          <div class="hall-widget__header">
            <svg class="hall-widget__icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
            <span class="hall-widget__title">{{ segment.data.question }}</span>
          </div>
          <div class="hall-widget__options">
            <button
              v-for="(opt, oi) in segment.data.options"
              :key="oi"
              type="button"
              class="hall-widget__option"
              :class="{ 'is-voted': pollVotedOption(segment.stateIdx) === oi }"
              :disabled="pollVotedOption(segment.stateIdx) !== -1"
              @click="votePoll(segment.stateIdx, oi, segment.data.options.length)"
            >
              <span class="hall-widget__option-label">{{ opt }}</span>
              <span class="hall-widget__option-track">
                <span
                  class="hall-widget__option-fill"
                  :style="{ width: pollPercent(segment.stateIdx, oi, segment.data.options.length) + '%' }"
                />
              </span>
              <span class="hall-widget__option-pct">{{ pollPercent(segment.stateIdx, oi, segment.data.options.length) }}%</span>
            </button>
          </div>
          <div class="hall-widget__footer">
            <span v-if="pollVotedOption(segment.stateIdx) !== -1">已投票，感谢参与！</span>
            <span v-else>点击选项即可投票</span>
          </div>
        </div>

        <!-- Checklist widget -->
        <div v-else-if="segment.type === 'checklist'" class="hall-widget hall-widget--checklist">
          <div class="hall-widget__header">
            <svg class="hall-widget__icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <span class="hall-widget__title">{{ segment.data.title || '互动清单' }}</span>
          </div>
          <ul class="hall-widget__checklist">
            <li
              v-for="(item, ii) in segment.data.items"
              :key="ii"
              class="hall-widget__check-item"
            >
              <label>
                <input
                  type="checkbox"
                  :checked="checklistChecked(segment.stateIdx, ii)"
                  @change="toggleChecklist(segment.stateIdx, ii)"
                />
                <span :class="{ 'is-done': checklistChecked(segment.stateIdx, ii) }">{{ item }}</span>
              </label>
            </li>
          </ul>
          <div class="hall-widget__footer">
            {{ checklistDoneCount(segment.stateIdx, segment.data.items.length) }} / {{ segment.data.items.length }} 已完成
          </div>
        </div>

        <!-- Think / reasoning block (DeepSeek-R1 / QwQ 推理展示) -->
        <details v-else-if="segment.type === 'think'" class="hall-think">
          <summary class="hall-think__summary">
            <span class="hall-think__icon">⚙️</span>
            <span>推理过程</span>
            <svg class="hall-think__chevron" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
          </summary>
          <div class="hall-think__content hall-message-markdown" v-html="renderMarkdownPart(segment.content)" @click="handleMarkupClick" />
        </details>

        <!-- HTML Artifact — sandboxed live preview -->
        <div v-else-if="segment.type === 'artifact-html'" class="hall-artifact">
          <div class="hall-artifact__header">
            <div class="hall-code-block__dots">
              <span></span><span></span><span></span>
            </div>
            <span class="hall-artifact__title">
              <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" style="opacity:0.7"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4l6 6v8a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm7 0l4 4h-4V4z" clip-rule="evenodd"/></svg>
              index.html
            </span>
            <div class="hall-artifact__controls">
              <button
                type="button"
                class="hall-artifact__btn"
                :class="{ 'is-active': artifactMode(segment.stateIdx) === 'preview' }"
                @click="setArtifactMode(segment.stateIdx, 'preview')"
              >预览</button>
              <button
                type="button"
                class="hall-artifact__btn"
                :class="{ 'is-active': artifactMode(segment.stateIdx) === 'code' }"
                @click="setArtifactMode(segment.stateIdx, 'code')"
              >代码</button>
            </div>
          </div>
          <div v-if="artifactMode(segment.stateIdx) === 'preview'" class="hall-artifact__preview">
            <iframe
              :srcdoc="segment.content"
              sandbox="allow-scripts allow-popups allow-forms"
              class="hall-artifact__frame"
              referrerpolicy="no-referrer"
            />
          </div>
          <div v-else class="hall-artifact__code">
            <pre><code>{{ segment.content }}</code></pre>
          </div>
        </div>
      </template>
      <!-- Blinking cursor while bot is still streaming -->
      <span v-if="isStreaming" class="hall-stream-cursor">▋</span>
    </template>
  </div>
</template>

<script setup>
import { computed, reactive, nextTick, onMounted, onUpdated } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import mermaid from 'mermaid'

const props = defineProps({
  content: {
    type: String,
    default: ''
  },
  isBot: {
    type: Boolean,
    default: false
  },
  messageId: {
    type: [String, Number],
    default: null
  },
  isStreaming: {
    type: Boolean,
    default: false
  }
})

// Initialize mermaid
if (import.meta.client) {
  mermaid.initialize({ startOnLoad: false, theme: 'default' })
}

// User text mention parsing
const formattedUserSegments = computed(() => {
  if (!props.content) return []
  const segments = []
  const regex = /(@[^\s]+)/g
  let lastIndex = 0
  let match
  
  while ((match = regex.exec(props.content)) !== null) {
    if (match.index > lastIndex) {
      segments.push({ type: 'text', text: props.content.slice(lastIndex, match.index) })
    }
    const text = match[1]
    const isBotMention = text === '@siliconbot'
    const isAdminMention = text === '@admin' || text === '@system'
    const cssClass = isBotMention 
      ? 'hall-bubble__mention hall-bubble__mention--bot' 
      : isAdminMention
      ? 'hall-bubble__mention hall-bubble__mention--admin'
      : 'hall-bubble__mention'

    segments.push({ type: 'mention', text, class: cssClass })
    lastIndex = regex.lastIndex
  }
  
  if (lastIndex < props.content.length) {
    segments.push({ type: 'text', text: props.content.slice(lastIndex) })
  }
  return segments
})

// Bot Markdown configuration
const renderer = new marked.Renderer()

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

const resolveLinkPayload = (href, title, text) => {
  if (href && typeof href === 'object') {
    const token = href
    const tokenText = Array.isArray(token.tokens)
      ? marked.Parser.parseInline(token.tokens, { renderer })
      : (token.text ?? text ?? token.raw ?? '')

    return {
      href: token.href ?? '#',
      title: token.title ?? title ?? '',
      text: tokenText,
    }
  }

  return {
    href: href ?? '#',
    title: title ?? '',
    text: text ?? href ?? '',
  }
}

renderer.code = (code, language) => {
  const rawCode = code.text || code
  const normalizedLanguage = String(language || 'css').toLowerCase()
  const fileNameMap = {
    javascript: 'script.js',
    js: 'script.js',
    typescript: 'index.ts',
    ts: 'index.ts',
    vue: 'component.vue',
    html: 'index.html',
    css: 'style.css',
    scss: 'style.scss',
    json: 'data.json',
    php: 'index.php',
    python: 'main.py',
    plaintext: 'snippet.txt',
    text: 'snippet.txt',
    svg: 'image.svg'
  }
  const windowTitle = fileNameMap[normalizedLanguage] || `snippet.${normalizedLanguage}`

  // Check if mermaid
  if (language === 'mermaid') {
    return `<div class="hall-mermaid-wrapper"><div class="mermaid">${DOMPurify.sanitize(rawCode)}</div></div>`
  }

  // SVG Preview
  let svgPreview = ''
  if (rawCode.trim().startsWith('<svg')) {
    svgPreview = `
      <div class="hall-svg-preview">
        <div class="hall-svg-preview__label">🎨 SVG 预览</div>
        <div class="hall-svg-preview__content">
          ${DOMPurify.sanitize(rawCode, { ADD_TAGS: ['svg', 'path', 'rect', 'circle', 'text', 'line', 'polygon', 'polyline', 'g', 'title', 'desc', 'image', 'use', 'defs'] })}
        </div>
      </div>
    `
  }

  // Regular code block
  const validLang = language || 'plaintext'
  const escapedCode = rawCode.replace(/</g, '&lt;').replace(/>/g, '&gt;')

  return `
    ${svgPreview}
    <div class="hall-code-block group">
      <div class="hall-code-block__header">
        <div class="hall-code-block__dots">
          <span></span><span></span><span></span>
        </div>
        <span class="hall-code-block__window-title">${windowTitle}</span>
        <button type="button" class="hall-code-block__copy" data-code="${encodeURIComponent(rawCode)}" aria-label="复制代码">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="hall-code-block__copy-icon"><path d="M8 4V16C8 17.1046 8.89543 18 10 18H20C21.1046 18 22 17.1046 22 16V4C22 2.89543 21.1046 2 20 2H10C8.89543 2 8 2.89543 8 4ZM10 4H20V16H10V4Z" fill="currentColor"/><path d="M4 8V20C4 21.1046 4.89543 22 6 22H16V20H6V8H4Z" fill="currentColor"/></svg>
        </button>
      </div>
      <pre><code class="language-${validLang}">${escapedCode}</code></pre>
    </div>
  `
}

renderer.link = (href, title, text) => {
  const payload = resolveLinkPayload(href, title, text)
  const plainText = String(payload.text ?? '').replace(/<[^>]*>/g, '')
  const isToolLink = plainText && (plainText.includes('search') || plainText.includes('来源'))
  const className = isToolLink ? 'hall-tool-link' : 'hall-markdown-link'
  const sanitizedHref = DOMPurify.sanitize(String(payload.href || '#'))
  const sanitizedTitle = payload.title ? ` title="${escapeHtml(payload.title)}"` : ''
  const linkText = payload.text || escapeHtml(payload.href || '#')
  return `<a href="${sanitizedHref}" target="_blank" rel="noopener noreferrer" class="${className}"${sanitizedTitle}>${linkText}</a>`
}

marked.use({ renderer })

// ── Widget / Artifact parsing ───────────────────────────────────────────────

// Matches :::widget:TYPE {json} ::: and :::artifact:TYPE content :::
const SEG_RE = /:::(widget|artifact):(\w+)\s+([\s\S]*?)\s*:::/g

const renderMarkdownPart = (text) => {
  const rawHtml = marked(text)
  return DOMPurify.sanitize(rawHtml, {
    ADD_ATTR: ['target', 'class', 'data-code'],
  })
}

const botSegments = computed(() => {
  if (!props.content) return []

  let clean = props.content
    .replace(/^@siliconbot\s*/i, '')
    .replace(/^siliconbot\uff1a\s*/i, '')

  const segments = []

  // Extract leading <think>...</think> block (DeepSeek-R1 / QwQ reasoning)
  const thinkMatch = clean.match(/^<think>([\s\S]*?)<\/think>\s*/i)
  if (thinkMatch) {
    const thinkContent = thinkMatch[1].trim()
    if (thinkContent) segments.push({ type: 'think', content: thinkContent })
    clean = clean.slice(thinkMatch[0].length)
  }

  let widgetCount = 0
  let artifactCount = 0
  let lastIndex = 0
  SEG_RE.lastIndex = 0
  let match

  while ((match = SEG_RE.exec(clean)) !== null) {
    if (match.index > lastIndex) {
      const part = clean.slice(lastIndex, match.index)
      if (part.trim()) segments.push({ type: 'markdown', html: renderMarkdownPart(part) })
    }

    const [, kind, subtype, body] = match
    if (kind === 'widget') {
      const wt = subtype.toLowerCase()
      let data = null
      try { data = JSON.parse(body) } catch {}
      if (data && (wt === 'poll' || wt === 'checklist')) {
        segments.push({ type: wt, data, stateIdx: widgetCount++ })
      }
    } else if (kind === 'artifact' && subtype.toLowerCase() === 'html') {
      segments.push({ type: 'artifact-html', content: body, stateIdx: artifactCount++ })
    }

    lastIndex = match.index + match[0].length
  }

  // Trailing markdown
  if (lastIndex < clean.length) {
    const part = clean.slice(lastIndex)
    if (part.trim()) segments.push({ type: 'markdown', html: renderMarkdownPart(part) })
  }

  if (!segments.some(s => s.type !== 'think') && clean.trim()) {
    segments.push({ type: 'markdown', html: renderMarkdownPart(clean) })
  }

  return segments
})

// ── Widget state ─────────────────────────────────────────────────────────────

const pollState    = reactive({})   // key -> { votes: number[], myVote: number }
const checkState   = reactive({})   // key -> Set<number>

const widgetKey = (idx) => `${props.messageId ?? 'x'}-${idx}`

const pollVotedOption = (idx) => pollState[widgetKey(idx)]?.myVote ?? -1

const pollPercent = (idx, optIdx, total) => {
  const s = pollState[widgetKey(idx)]
  if (!s) return 0
  const totalVotes = s.votes.reduce((a, b) => a + b, 0)
  if (!totalVotes) return 0
  return Math.round((s.votes[optIdx] / totalVotes) * 100)
}

const votePoll = (idx, optIdx, totalOptions) => {
  const key = widgetKey(idx)
  if (!pollState[key]) pollState[key] = { votes: Array(totalOptions).fill(0), myVote: -1 }
  const s = pollState[key]
  if (s.myVote !== -1) return
  s.votes[optIdx]++
  s.myVote = optIdx
}

const checklistChecked  = (idx, itemIdx) => checkState[widgetKey(idx)]?.has(itemIdx) ?? false

const checklistDoneCount = (idx, total) => {
  const s = checkState[widgetKey(idx)]
  return s ? s.size : 0
}

const toggleChecklist = (idx, itemIdx) => {
  const key = widgetKey(idx)
  if (!checkState[key]) checkState[key] = reactive(new Set())
  if (checkState[key].has(itemIdx)) {
    checkState[key].delete(itemIdx)
  } else {
    checkState[key].add(itemIdx)
  }
}

// ── Artifact state ────────────────────────────────────────────────────────────

const artifactState = reactive({})  // key -> 'preview' | 'code'
const artifactMode    = (idx) => artifactState[widgetKey(idx)] ?? 'preview'
const setArtifactMode = (idx, mode) => { artifactState[widgetKey(idx)] = mode }

// ── Legacy computed for non-widget path (kept for safety) ────────────────────
const renderedMarkdown = computed(() => {
  if (!props.content || props.isBot) return ''
  return renderMarkdownPart(props.content)
})

const handleMarkupClick = (e) => {
  // Handle copy button
  const copyButton = e.target.closest?.('.hall-code-block__copy')
  if (copyButton) {
    const code = decodeURIComponent(copyButton.getAttribute('data-code'))
    navigator.clipboard.writeText(code).then(() => {
      copyButton.classList.add('is-copied')
      setTimeout(() => { copyButton.classList.remove('is-copied') }, 1800)
    })
  }
}

const renderMermaid = async () => {
  if (!import.meta.client) return
  await nextTick()
  const elements = document.querySelectorAll('.hall-mermaid-wrapper .mermaid')
  if (elements.length) {
    try {
      await mermaid.run({ nodes: elements })
    } catch (e) {
      console.warn('Mermaid rendering failed', e)
    }
  }
}

onMounted(renderMermaid)
onUpdated(renderMermaid)
</script>

<style>
/* Scoped via component classes, injecting globally inside .hall-message-renderer */
.hall-message-renderer {
  white-space: pre-wrap;
  word-break: break-word;
}

.hall-message-text {
  color: inherit;
}

.hall-message-markdown {
  color: inherit;
}

/* Markdown overrides */
.hall-message-markdown p {
  margin-bottom: 0.75em;
}
.hall-message-markdown p:last-child {
  margin-bottom: 0;
}
.hall-message-markdown ul, .hall-message-markdown ol {
  padding-left: 1.5em;
  margin-bottom: 0.75em;
}
.hall-message-markdown ul { list-style: disc; }
.hall-message-markdown ol { list-style: decimal; }
.hall-message-markdown li { margin-bottom: 0.25em; }

.hall-message-markdown h1, .hall-message-markdown h2, .hall-message-markdown h3 {
  margin-top: 1.2em;
  margin-bottom: 0.5em;
  font-weight: 600;
  line-height: 1.25;
}
.hall-message-markdown h1 { font-size: 1.25em; }
.hall-message-markdown h2 { font-size: 1.15em; }
.hall-message-markdown h3 { font-size: 1.05em; }

.hall-message-markdown pre {
  margin: 0;
}

.hall-message-markdown code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  background: rgba(148, 163, 184, 0.15);
  padding: 0.2em 0.4em;
  border-radius: 4px;
  font-size: 0.85em;
}

/* Code block wrapper (Bot syntax) */
.hall-code-block {
  margin: 1.25em 0;
  background: #56506d;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 18px 36px rgba(31, 24, 53, 0.32);
  color: #ffffff;
  border: 1px solid rgba(114, 105, 146, 0.52);
}

:global(.theme-dark) .hall-code-block {
  background: #4d4864;
  border-color: rgba(124, 115, 161, 0.46);
}

.hall-code-block__header {
  display: flex;
  align-items: center;
  padding: 0.85rem 1rem 0.8rem;
  background: #2b2843;
  font-size: 0.8rem;
  position: relative;
}

:global(.theme-dark) .hall-code-block__header {
  background: #24213a;
}

.hall-code-block__dots {
  display: flex;
  gap: 6px;
  margin-right: 0.9rem;
  flex-shrink: 0;
}

.hall-code-block__dots span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.hall-code-block__dots span:nth-child(1) { background: #ff5f56; }
.hall-code-block__dots span:nth-child(2) { background: #ffbd2e; }
.hall-code-block__dots span:nth-child(3) { background: #27c93f; }

.hall-code-block__window-title {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  max-width: calc(100% - 7rem);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(255, 255, 255, 0.92);
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 0.01em;
}

.hall-code-block__copy {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  margin-left: auto;
  width: 2rem;
  height: 2rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.7);
  padding: 0;
  border-radius: 0.6rem;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.hall-code-block__copy-icon {
  width: 14px;
  height: 14px;
}

.hall-code-block__copy:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
}

.hall-code-block__copy.is-copied {
  background: rgba(34, 197, 94, 0.16);
  border-color: rgba(34, 197, 94, 0.42);
  color: #86efac;
}

.hall-code-block pre {
  padding: 1rem 1.1rem 1.15rem;
  overflow-x: auto;
  margin: 0;
  background: transparent;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.35) transparent;
}

.hall-code-block pre::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.hall-code-block pre::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.28);
  border-radius: 999px;
}

.hall-code-block pre::-webkit-scrollbar-track {
  background: transparent;
}

.hall-code-block pre code {
  background: transparent;
  padding: 0;
  font-size: 0.98rem;
  line-height: 1.55;
  color: #ffffff;
}

/* SVG Preview */
.hall-svg-preview {
  margin: 0.85em 0 1em;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  max-width: min(320px, 100%);
}
:global(.theme-dark) .hall-svg-preview {
  background: #1e293b;
  border-color: #334155;
}
.hall-svg-preview__label {
  padding: 0.4rem 0.75rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  border-bottom: 1px solid #cbd5e1;
}
:global(.theme-dark) .hall-svg-preview__label {
  background: #0f172a;
  color: #cbd5e1;
  border-bottom-color: #334155;
}
.hall-svg-preview__content {
  padding: 0.75rem;
  display: flex;
  justify-content: center;
  align-items: center;
  background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%);
  background-size: 14px 14px;
  background-position: 0 0, 0 7px, 7px -7px, -7px 0px;
  min-height: 72px;
}
:global(.theme-dark) .hall-svg-preview__content {
  background-image: linear-gradient(45deg, #334155 25%, transparent 25%), linear-gradient(-45deg, #334155 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #334155 75%), linear-gradient(-45deg, transparent 75%, #334155 75%);
}
.hall-svg-preview__content svg {
  max-width: 220px;
  max-height: 140px;
  height: auto;
}

/* Mermaid Blocks */
.hall-mermaid-wrapper {
  margin: 1em 0;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  overflow-x: auto;
  border: 1px solid #e2e8f0;
}

:global(.theme-dark) .hall-mermaid-wrapper {
  background: #1e293b;
  border-color: rgba(56, 189, 248, 0.15);
}

/* Markdown Links (Tool/Search visualization) */
.hall-markdown-link {
  color: #0ea5e9;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.hall-tool-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(14, 165, 233, 0.1);
  color: #0284c7;
  padding: 0.2em 0.6em;
  border-radius: 999px;
  font-size: 0.85em;
  text-decoration: none;
  font-weight: 500;
  margin: 0.2em 0;
  border: 1px solid rgba(14, 165, 233, 0.2);
}

:global(.theme-dark) .hall-tool-link {
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  border-color: rgba(56, 189, 248, 0.2);
}

.hall-tool-link:hover {
  background: rgba(14, 165, 233, 0.2);
}

/* ── Widget Base ───────────────────────────────────────────────── */
.hall-widget {
  margin: 0.75em 0;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  overflow: hidden;
  max-width: 340px;
  font-size: 0.9rem;
}
:global(.theme-dark) .hall-widget {
  background: #1e293b;
  border-color: #334155;
}

.hall-widget__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 0.9rem 0.5rem;
  font-weight: 600;
  color: #0f172a;
  font-size: 0.88rem;
}
:global(.theme-dark) .hall-widget__header {
  color: #e2e8f0;
}
.hall-widget__icon {
  flex-shrink: 0;
  opacity: 0.75;
}
.hall-widget__title {
  flex: 1;
  line-height: 1.35;
}
.hall-widget__footer {
  padding: 0.4rem 0.9rem 0.55rem;
  font-size: 0.78rem;
  color: #64748b;
}
:global(.theme-dark) .hall-widget__footer {
  color: #94a3b8;
}

/* ── Poll widget ────────────────────────────────────────────────── */
.hall-widget--poll .hall-widget__header {
  color: #6366f1;
}
:global(.theme-dark) .hall-widget--poll .hall-widget__header {
  color: #a5b4fc;
}

.hall-widget__options {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0 0.9rem 0.5rem;
}

.hall-widget__option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.45rem 0.6rem;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s, border-color 0.15s;
  width: 100%;
}
:global(.theme-dark) .hall-widget__option {
  background: #0f172a;
  border-color: #334155;
  color: #e2e8f0;
}
.hall-widget__option:not(:disabled):hover {
  background: #f1f5f9;
  border-color: #a5b4fc;
}
:global(.theme-dark) .hall-widget__option:not(:disabled):hover {
  background: #1e293b;
  border-color: #6366f1;
}
.hall-widget__option.is-voted {
  background: #eef2ff;
  border-color: #6366f1;
  color: #4338ca;
}
:global(.theme-dark) .hall-widget__option.is-voted {
  background: rgba(99, 102, 241, 0.15);
  border-color: #6366f1;
  color: #a5b4fc;
}
.hall-widget__option:disabled {
  cursor: default;
}

.hall-widget__option-label {
  flex: 1;
  min-width: 0;
  font-size: 0.85rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hall-widget__option-track {
  flex-shrink: 0;
  width: 80px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}
:global(.theme-dark) .hall-widget__option-track {
  background: #334155;
}
.hall-widget__option-fill {
  display: block;
  height: 100%;
  background: #6366f1;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.hall-widget__option-pct {
  flex-shrink: 0;
  width: 2.5rem;
  text-align: right;
  font-size: 0.78rem;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}
:global(.theme-dark) .hall-widget__option-pct {
  color: #94a3b8;
}

/* ── Checklist widget ───────────────────────────────────────────── */
.hall-widget--checklist .hall-widget__header {
  color: #059669;
}
:global(.theme-dark) .hall-widget--checklist .hall-widget__header {
  color: #34d399;
}

.hall-widget__checklist {
  list-style: none;
  padding: 0 0.9rem 0.4rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.hall-widget__check-item label {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  padding: 0.25rem 0;
  font-size: 0.86rem;
  color: #0f172a;
}
:global(.theme-dark) .hall-widget__check-item label {
  color: #e2e8f0;
}
.hall-widget__check-item input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #059669;
  flex-shrink: 0;
  cursor: pointer;
}
.hall-widget__check-item .is-done {
  text-decoration: line-through;
  color: #94a3b8;
}

/* ── Streaming cursor ────────────────────────────────────────────────────────── */
@keyframes hall-blink {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0; }
}
.hall-stream-cursor {
  display: inline-block;
  font-size: 0.9em;
  line-height: 1;
  vertical-align: middle;
  margin-left: 1px;
  animation: hall-blink 0.9s ease infinite;
  color: currentColor;
}

/* ── Think / Reasoning block ─────────────────────────────────────────────────── */
.hall-think {
  margin: 0.5em 0;
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 10px;
  overflow: hidden;
  background: rgba(148, 163, 184, 0.05);
}
:global(.theme-dark) .hall-think {
  border-color: rgba(148, 163, 184, 0.18);
  background: rgba(148, 163, 184, 0.04);
}
.hall-think__summary {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.8rem;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 600;
  color: #64748b;
  user-select: none;
  list-style: none;
}
.hall-think__summary::-webkit-details-marker { display: none; }
.hall-think__summary:hover { background: rgba(148, 163, 184, 0.08); }
:global(.theme-dark) .hall-think__summary { color: #94a3b8; }
.hall-think__chevron {
  margin-left: auto;
  transition: transform 0.2s;
  flex-shrink: 0;
}
details.hall-think[open] .hall-think__chevron { transform: rotate(180deg); }
.hall-think__content {
  padding: 0.6rem 0.9rem 0.7rem;
  font-size: 0.82rem;
  color: #64748b;
  border-top: 1px solid rgba(148, 163, 184, 0.2);
}
:global(.theme-dark) .hall-think__content { color: #94a3b8; }
.hall-think__content p:last-child { margin-bottom: 0; }

/* ── HTML Artifact ───────────────────────────────────────────────────────────── */
.hall-artifact {
  margin: 1em 0;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(114, 105, 146, 0.4);
  background: #56506d;
  box-shadow: 0 8px 24px rgba(31, 24, 53, 0.2);
}
:global(.theme-dark) .hall-artifact {
  background: #4d4864;
  border-color: rgba(124, 115, 161, 0.46);
}
.hall-artifact__header {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #2b2843;
  gap: 0.65rem;
}
:global(.theme-dark) .hall-artifact__header { background: #24213a; }
.hall-artifact__title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: rgba(255, 255, 255, 0.88);
  font-size: 0.82rem;
  font-weight: 700;
  flex: 1;
  justify-content: center;
}
.hall-artifact__controls {
  display: flex;
  gap: 0.3rem;
  margin-left: auto;
}
.hall-artifact__btn {
  padding: 0.22rem 0.65rem;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: transparent;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.hall-artifact__btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}
.hall-artifact__btn.is-active {
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.32);
}
.hall-artifact__preview {
  background: #fff;
  overflow: hidden;
}
.hall-artifact__frame {
  width: 100%;
  height: 320px;
  border: none;
  display: block;
  background: #fff;
}
.hall-artifact__code {
  padding: 0.85rem 1rem;
}
.hall-artifact__code pre {
  margin: 0;
  overflow-x: auto;
  color: #fff;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.88rem;
  line-height: 1.55;
  white-space: pre-wrap;
  word-break: break-all;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}
.hall-artifact__code code { background: transparent; padding: 0; color: inherit; font-size: inherit; }
</style>
