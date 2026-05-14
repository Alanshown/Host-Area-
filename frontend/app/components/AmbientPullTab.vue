<template>
  <div class="ambient-chain-switch" :class="isDragging ? 'is-dragging' : ''" @pointerdown="onPointerDown">
    <div class="ambient-chain-switch__anchor"></div>
    <div class="ambient-chain-switch__rig" :style="{ transform: `translateY(${dragY}px) rotate(${swing}deg)` }">
      <div class="ambient-chain-switch__chain">
        <span v-for="n in 7" :key="n" class="ambient-chain-switch__bead"></span>
      </div>
      <button type="button" class="ambient-chain-switch__knob" @click.stop="toggleScene">
        <span></span>
      </button>
      <button v-if="currentPost?.id" type="button" class="ambient-chain-switch__ticker" @click.stop="goToPost(currentPost)">
        <span class="ambient-chain-switch__ticker-label">HOT</span>
        <span class="ambient-chain-switch__ticker-separator">/</span>
        <span class="ambient-chain-switch__ticker-text">{{ currentPost.title }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const { apiBase } = useApi()
const theme = useTheme()
const router = useRouter()

const dragY = ref(0)
const swing = ref(0)
const isDragging = ref(false)
const currentPostIndex = ref(0)
const posts = ref([])

let startY = 0
let startX = 0
let rotateTimer = null
let postTimer = null

const currentPost = computed(() => posts.value[currentPostIndex.value] || null)

const onPointerMove = (event) => {
  if (!isDragging.value) return

  const deltaY = Math.max(0, event.clientY - startY)
  const deltaX = event.clientX - startX

  dragY.value = Math.min(72, deltaY * 0.44)
  swing.value = Math.max(-12, Math.min(12, deltaX * 0.08))
}

const cleanupPointer = () => {
  document.removeEventListener('pointermove', onPointerMove)
  document.removeEventListener('pointerup', onPointerUp)
  document.removeEventListener('pointercancel', onPointerUp)
}

const onPointerUp = () => {
  const shouldToggle = dragY.value > 26
  isDragging.value = false
  dragY.value = 0

  if (rotateTimer) {
    clearTimeout(rotateTimer)
  }

  rotateTimer = window.setTimeout(() => {
    swing.value = 0
  }, 180)

  if (shouldToggle) {
    toggleScene()
  }

  cleanupPointer()
}

const onPointerDown = (event) => {
  isDragging.value = true
  startY = event.clientY
  startX = event.clientX
  document.addEventListener('pointermove', onPointerMove)
  document.addEventListener('pointerup', onPointerUp)
  document.addEventListener('pointercancel', onPointerUp)
}

const toggleScene = () => {
  theme.toggleTransparentMode()
}

const goToPost = (post) => {
  if (post?.id) {
    router.push(`/post/${post.id}`)
  }
}

const fetchHotPosts = async () => {
  try {
    const response = await $fetch(`${apiBase}/posts/hot`)
    posts.value = response?.data?.slice(0, 6) ?? []
  } catch {}
}

onMounted(() => {
  fetchHotPosts()
  postTimer = window.setInterval(() => {
    if (posts.value.length > 1) {
      currentPostIndex.value = (currentPostIndex.value + 1) % posts.value.length
    }
  }, 4200)
})

onBeforeUnmount(() => {
  cleanupPointer()
  if (rotateTimer) clearTimeout(rotateTimer)
  if (postTimer) clearInterval(postTimer)
})
</script>

<style scoped>
.ambient-chain-switch {
  position: relative;
  display: flex;
  width: 100%;
  min-height: 130px;
  justify-content: center;
  overflow: visible;
  user-select: none;
  touch-action: none;
}

.ambient-chain-switch__anchor {
  position: absolute;
  top: -10px;
  left: 50%;
  width: 18px;
  height: 18px;
  transform: translateX(-50%);
  border-radius: 999px;
  background: radial-gradient(circle at 35% 35%, rgba(224, 242, 254, 0.95), rgba(14, 116, 144, 0.82) 58%, rgba(8, 47, 73, 0.94));
  box-shadow: 0 8px 18px rgba(8, 47, 73, 0.28);
}

.ambient-chain-switch__rig {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  transform-origin: top center;
  transition: transform 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

.ambient-chain-switch.is-dragging .ambient-chain-switch__rig {
  transition: none;
}

.ambient-chain-switch__chain {
  display: grid;
  gap: 5px;
  margin-top: 6px;
}

.ambient-chain-switch__bead {
  width: 7px;
  height: 13px;
  border-radius: 999px;
  border: 1px solid rgba(186, 230, 253, 0.4);
  background: linear-gradient(180deg, rgba(191, 219, 254, 0.9), rgba(14, 165, 233, 0.42));
  box-shadow: 0 2px 6px rgba(2, 132, 199, 0.18);
}

.ambient-chain-switch__knob {
  position: relative;
  margin-top: 7px;
  width: 34px;
  height: 34px;
  border: 1px solid rgba(125, 211, 252, 0.5);
  border-radius: 999px;
  background: radial-gradient(circle at 35% 35%, rgba(224, 242, 254, 0.96), rgba(56, 189, 248, 0.72) 55%, rgba(8, 47, 73, 0.96));
  box-shadow: 0 16px 26px rgba(8, 47, 73, 0.26);
}

.ambient-chain-switch__knob span {
  position: absolute;
  inset: 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.ambient-chain-switch__ticker {
  display: inline-flex;
  max-width: min(240px, 100%);
  align-items: center;
  gap: 0.35rem;
  margin-top: 12px;
  padding: 0.72rem 0.95rem;
  border-radius: 999px;
  border: 1px solid rgba(186, 230, 253, 0.5);
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.92), rgba(224, 242, 254, 0.92));
  box-shadow: 0 18px 40px rgba(148, 163, 184, 0.18);
  color: #334155;
  text-align: left;
}

.ambient-chain-switch__ticker-label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.22em;
  color: #0f766e;
}

.ambient-chain-switch__ticker-separator {
  color: rgba(100, 116, 139, 0.6);
}

.ambient-chain-switch__ticker-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.9rem;
  font-weight: 600;
}

:global(.theme-dark) .ambient-chain-switch__anchor,
:global(.theme-dark) .ambient-chain-switch__knob {
  background: radial-gradient(circle at 35% 35%, rgba(224, 242, 254, 0.94), rgba(14, 165, 233, 0.74) 48%, rgba(3, 7, 18, 0.98));
}

:global(.theme-dark) .ambient-chain-switch__ticker {
  background: linear-gradient(135deg, rgba(8, 47, 73, 0.88), rgba(14, 116, 144, 0.38), rgba(15, 23, 42, 0.88));
  color: #e0f2fe;
  border-color: rgba(56, 189, 248, 0.34);
  box-shadow: 0 18px 44px rgba(2, 6, 23, 0.36);
}

:global(.theme-dark) .ambient-chain-switch__ticker-label {
  color: #7dd3fc;
}
</style>
