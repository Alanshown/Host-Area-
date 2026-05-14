<template>
  <Transition name="puzzle-fade">
    <div v-if="open" class="puzzle-layer" @click.self="handleCancel">
      <section class="puzzle-dialog">
        <header class="puzzle-dialog__header">
          <div>
            <p class="puzzle-dialog__eyebrow">Security Check</p>
            <h2 class="puzzle-dialog__title">图片连线验证</h2>
            <p class="puzzle-dialog__subtitle">交换图片位置，使相同图片连成一条直线。</p>
          </div>
          <button type="button" class="puzzle-dialog__close" @click="handleCancel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </header>

        <div class="puzzle-dialog__board-wrap">

          <div class="puzzle-grid" role="grid" aria-label="拼图验证九宫格">
            <button
              v-for="(tile, index) in tiles"
              :key="tile.id"
              type="button"
              class="puzzle-tile"
              :class="{
                'is-selected': selectedIndex === index,
                'is-match': solvedLine.includes(index),
              }"
              @click="selectTile(index)"
            >
              <img :src="tile.image" :alt="tile.label" class="puzzle-tile__image" draggable="false" />
            </button>
          </div>
        </div>

        <footer class="puzzle-dialog__footer">
          <p class="puzzle-dialog__status" :class="{ 'is-visible': statusText }">{{ statusText || '\u00A0' }}</p>
          <div class="puzzle-dialog__actions">
            <button type="button" class="puzzle-dialog__ghost" @click="shuffleBoard">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
              换一批
            </button>
          </div>
        </footer>
      </section>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import img01 from '~~/assets/vertification/01.png'
import img02 from '~~/assets/vertification/02.png'
import img03 from '~~/assets/vertification/03.png'
import img04 from '~~/assets/vertification/04.png'
import img05 from '~~/assets/vertification/05.png'
import img06 from '~~/assets/vertification/06.png'
import img07 from '~~/assets/vertification/07.png'
import img08 from '~~/assets/vertification/08.png'
import img09 from '~~/assets/vertification/09.png'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:open', 'verified', 'cancel'])

const sourceImages = [
  { image: img01, label: '素材 01' },
  { image: img02, label: '素材 02' },
  { image: img03, label: '素材 03' },
  { image: img04, label: '素材 04' },
  { image: img05, label: '素材 05' },
  { image: img06, label: '素材 06' },
  { image: img07, label: '素材 07' },
  { image: img08, label: '素材 08' },
  { image: img09, label: '素材 09' },
]

const lineSets = [
  [0, 1, 2],
  [3, 4, 5],
  [6, 7, 8],
  [0, 3, 6],
  [1, 4, 7],
  [2, 5, 8],
  [0, 4, 8],
  [2, 4, 6],
]

const tiles = ref([])
const selectedIndex = ref(null)
const solvedLine = ref([])
const verifying = ref(false)
const attemptSeed = ref(0)
const statusText = ref('')
let autoVerifyTimer = null

const cloneTiles = (items) => items.map((item, index) => ({ ...item, id: `${item.group}-${index}-${Math.random().toString(36).slice(2, 7)}` }))

const pickRandomGroups = () => {
  const pool = [...sourceImages]
  for (let index = pool.length - 1; index > 0; index -= 1) {
    const swapIndex = Math.floor(Math.random() * (index + 1))
    ;[pool[index], pool[swapIndex]] = [pool[swapIndex], pool[index]]
  }

  return pool.slice(0, 3).flatMap((item, groupIndex) => ([
    { ...item, group: groupIndex },
    { ...item, group: groupIndex },
    { ...item, group: groupIndex },
  ]))
}

const findSolvedLine = (items) => {
  for (const line of lineSets) {
    const [first, second, third] = line
    const group = items[first]?.group
    if (group === undefined) continue
    if (items[second]?.group === group && items[third]?.group === group) {
      return line
    }
  }
  return []
}

const shuffleUntilUnsolved = (items) => {
  const pool = [...items]
  let tries = 0
  do {
    for (let index = pool.length - 1; index > 0; index -= 1) {
      const swapIndex = Math.floor(Math.random() * (index + 1))
      ;[pool[index], pool[swapIndex]] = [pool[swapIndex], pool[index]]
    }
    tries += 1
  } while (findSolvedLine(pool).length && tries < 40)

  return cloneTiles(pool)
}

const resetBoard = () => {
  attemptSeed.value += 1
  selectedIndex.value = null
  solvedLine.value = []
  statusText.value = ''
  if (autoVerifyTimer) {
    window.clearTimeout(autoVerifyTimer)
    autoVerifyTimer = null
  }
  tiles.value = shuffleUntilUnsolved(pickRandomGroups())
}

const shuffleBoard = () => {
  selectedIndex.value = null
  solvedLine.value = []
  statusText.value = '素材已更新，请继续交换。'
  if (autoVerifyTimer) {
    window.clearTimeout(autoVerifyTimer)
    autoVerifyTimer = null
  }
  tiles.value = shuffleUntilUnsolved(pickRandomGroups())
}

const selectTile = (index) => {
  statusText.value = ''

  if (selectedIndex.value === null) {
    selectedIndex.value = index
    return
  }

  if (selectedIndex.value === index) {
    selectedIndex.value = null
    return
  }

  const nextTiles = [...tiles.value]
  ;[nextTiles[selectedIndex.value], nextTiles[index]] = [nextTiles[index], nextTiles[selectedIndex.value]]
  tiles.value = cloneTiles(nextTiles)
  selectedIndex.value = null

  const line = findSolvedLine(tiles.value)
  if (line.length) {
    solvedLine.value = line
    statusText.value = '已连成一条线，正在自动验证。'
    if (autoVerifyTimer) {
      window.clearTimeout(autoVerifyTimer)
    }
    autoVerifyTimer = window.setTimeout(() => {
      verifyCurrentBoard()
    }, 220)
  }
}

const verifyCurrentBoard = async () => {
  if (verifying.value) return

  const line = findSolvedLine(tiles.value)
  if (!line.length) {
    statusText.value = '还没有形成连线，请继续调整。'
    return
  }

  verifying.value = true
  solvedLine.value = line

  try {
    statusText.value = '验证成功，正在继续...'
    await new Promise((resolve) => setTimeout(resolve, 280))
    emit('verified')
    emit('update:open', false)
  } finally {
    verifying.value = false
    autoVerifyTimer = null
  }
}

const handleCancel = () => {
  emit('cancel')
  emit('update:open', false)
}

watch(() => props.open, (value) => {
  if (value) {
    resetBoard()
  } else {
    if (autoVerifyTimer) {
      window.clearTimeout(autoVerifyTimer)
      autoVerifyTimer = null
    }
    selectedIndex.value = null
    solvedLine.value = []
    statusText.value = ''
  }
}, { immediate: true })
</script>

<style scoped>
.puzzle-layer {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: grid;
  place-items: center;
  padding: 1.25rem;
  background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.puzzle-dialog {
  width: min(100%, 28rem);
  max-height: min(92vh, 44rem);
  border-radius: 28px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background:
    radial-gradient(circle at top right, rgba(56, 189, 248, 0.12), transparent 34%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.98));
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22);
  overflow: hidden;
}

:global(.theme-dark) .puzzle-dialog {
  background:
    radial-gradient(circle at top right, rgba(56, 189, 248, 0.14), transparent 34%),
    linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(2, 6, 23, 0.98));
  border-color: rgba(56, 189, 248, 0.18);
}

.puzzle-dialog__header,
.puzzle-dialog__footer {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem 1.25rem;
}

.puzzle-dialog__header {
  border-bottom: 1px solid rgba(148, 163, 184, 0.14);
}

.puzzle-dialog__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.72rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #0ea5e9;
}

.puzzle-dialog__title {
  margin: 0;
  font-size: 1.35rem;
  color: #0f172a;
}

.puzzle-dialog__subtitle {
  margin: 0.55rem 0 0;
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.65;
}

:global(.theme-dark) .puzzle-dialog__title {
  color: #f8fafc;
}

:global(.theme-dark) .puzzle-dialog__subtitle {
  color: #94a3b8;
}

.puzzle-dialog__close {
  border: 0;
  background: rgba(148, 163, 184, 0.1);
  color: #64748b;
  width: 2rem;
  height: 2rem;
  border-radius: 10px;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
  flex-shrink: 0;
}

.puzzle-dialog__close:hover {
  background: rgba(148, 163, 184, 0.22);
  color: #0f172a;
}

:global(.theme-dark) .puzzle-dialog__close {
  color: #94a3b8;
}

:global(.theme-dark) .puzzle-dialog__close:hover {
  background: rgba(148, 163, 184, 0.18);
  color: #f1f5f9;
}

.puzzle-dialog__ghost,
.puzzle-dialog__primary {
  border: 0;
  border-radius: 999px;
  padding: 0.72rem 1.05rem;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
}

.puzzle-dialog__ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(148, 163, 184, 0.12);
  color: #334155;
}

.puzzle-dialog__primary {
  background: linear-gradient(135deg, #0ea5e9, #2563eb);
  color: #eff6ff;
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
}

.puzzle-dialog__primary:disabled {
  opacity: 0.65;
  cursor: wait;
}

.puzzle-dialog__board-wrap {
  padding: 1rem 1.25rem 1.05rem;
}

:global(.theme-dark) .puzzle-dialog__status {
  color: #94a3b8;
}

.puzzle-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.puzzle-tile {
  position: relative;
  padding: 0;
  aspect-ratio: 1;
  border-radius: 20px;
  overflow: hidden;
  border: 2px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.76);
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.puzzle-tile:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 26px rgba(15, 23, 42, 0.12);
}

.puzzle-tile.is-selected {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.18);
}

.puzzle-tile.is-match {
  border-color: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
}

.puzzle-tile__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  user-select: none;
  pointer-events: none;
}

.puzzle-dialog__footer {
  border-top: 1px solid rgba(148, 163, 184, 0.14);
  align-items: center;
}

.puzzle-dialog__status {
  margin: 0;
  color: #64748b;
  min-height: 1.5rem;
  font-size: 0.86rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.puzzle-dialog__status.is-visible {
  opacity: 1;
}

.puzzle-dialog__actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.puzzle-fade-enter-active,
.puzzle-fade-leave-active {
  transition: opacity 0.22s ease;
}

.puzzle-fade-enter-from,
.puzzle-fade-leave-to {
  opacity: 0;
}

@media (max-width: 640px) {
  .puzzle-layer {
    padding: 0.9rem;
  }

  .puzzle-dialog {
    width: min(100%, 22.5rem);
    border-radius: 22px;
  }

  .puzzle-dialog__header,
  .puzzle-dialog__footer,
  .puzzle-dialog__board-wrap {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .puzzle-dialog__title {
    font-size: 1.12rem;
  }

  .puzzle-dialog__subtitle,
  .puzzle-dialog__hint,
  .puzzle-dialog__status {
    font-size: 0.8rem;
  }

  .puzzle-grid {
    gap: 0.55rem;
  }
}

@media (max-width: 640px) {
  .puzzle-layer {
    padding: 1rem;
  }

  .puzzle-dialog__header,
  .puzzle-dialog__footer,
  .puzzle-dialog__hint {
    flex-direction: column;
    align-items: stretch;
  }

  .puzzle-dialog__actions {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
}
</style>
