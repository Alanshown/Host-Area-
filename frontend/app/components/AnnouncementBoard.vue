<template>
  <Transition name="announcement-float" mode="out-in">
    <aside v-if="showBoard" :key="activeAnnouncement?.id || 'announcement-board'" class="announcement-board-shell">
      <div class="announcement-board">
        <div class="announcement-board__glow"></div>
        <div class="announcement-board__content">
          <div class="announcement-board__lead">
            <div class="announcement-board__signal">
              <span class="announcement-board__eyebrow">社区公告</span>
              <span class="announcement-board__pulse"></span>
              <span class="announcement-board__status">实时轮播中</span>
            </div>
            <div v-if="announcements.length > 1" class="announcement-board__dots" aria-label="公告切换进度">
              <span
                v-for="(_, index) in announcements"
                :key="index"
                class="announcement-board__dot"
                :class="{ 'announcement-board__dot--active': index === activeIndex }"
              ></span>
            </div>
          </div>

          <div class="announcement-board__main">
            <div class="announcement-board__copy">
              <h3 class="announcement-board__title">{{ activeAnnouncement.title }}</h3>
              <p class="announcement-board__body">{{ activeAnnouncement.body }}</p>
            </div>

            <a
              v-if="activeAnnouncement.link_url && activeAnnouncement.link_label"
              :href="activeAnnouncement.link_url"
              class="announcement-board__link"
              :target="isExternalLink ? '_blank' : undefined"
              :rel="isExternalLink ? 'noreferrer noopener' : undefined"
            >
              <span>{{ activeAnnouncement.link_label }}</span>
              <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<script setup>
const { apiBase } = useApi()
const route = useRoute()

const { data } = await useFetch(`${apiBase}/announcements/current`, {
  default: () => ({ data: [] }),
})

const announcements = computed(() => data.value?.data ?? [])
const activeIndex = ref(0)
let rotateTimer = null

const activeAnnouncement = computed(() => {
  if (!announcements.value.length) return null
  return announcements.value[activeIndex.value % announcements.value.length]
})

const isExternalLink = computed(() => /^https?:\/\//i.test(activeAnnouncement.value?.link_url || ''))
const isAtTop = ref(true)
const isHomePage = computed(() => route.path === '/')
const showBoard = computed(() => Boolean(activeAnnouncement.value) && isAtTop.value && isHomePage.value)

const syncVisibility = () => {
  if (!process.client) return
  isAtTop.value = window.scrollY < 28
}

const startRotation = () => {
  if (!process.client) return

  if (rotateTimer) {
    clearInterval(rotateTimer)
    rotateTimer = null
  }

  if (announcements.value.length <= 1) {
    activeIndex.value = 0
    return
  }

  rotateTimer = window.setInterval(() => {
    activeIndex.value = (activeIndex.value + 1) % announcements.value.length
  }, 5200)
}

watch(announcements, () => {
  activeIndex.value = 0
  startRotation()
}, { immediate: true })

onMounted(() => {
  syncVisibility()
  startRotation()
  window.addEventListener('scroll', syncVisibility, { passive: true })
})

onBeforeUnmount(() => {
  if (rotateTimer) {
    clearInterval(rotateTimer)
  }

  window.removeEventListener('scroll', syncVisibility)
})
</script>

<style scoped>
.announcement-board-shell {
  position: sticky;
  top: 4.7rem;
  z-index: 35;
  margin: 0 auto;
  width: min(100%, 68rem);
  padding: 0 1rem 0.95rem;
}

.announcement-board {
  position: relative;
  overflow: hidden;
  border-radius: 24px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background:
    radial-gradient(circle at 0% 0%, rgba(14, 165, 233, 0.16), transparent 30%),
    radial-gradient(circle at 100% 0%, rgba(250, 204, 21, 0.14), transparent 28%),
    linear-gradient(120deg, rgba(255, 255, 255, 0.94), rgba(248, 250, 252, 0.96) 52%, rgba(239, 246, 255, 0.98) 100%);
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
}

.announcement-board__glow {
  position: absolute;
  inset: auto -4% -55% auto;
  width: 320px;
  height: 220px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(56, 189, 248, 0.18), transparent 68%);
  filter: blur(20px);
}

.announcement-board__content {
  position: relative;
  padding: 1rem 1.2rem;
  color: #0f172a;
}

.announcement-board__lead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.announcement-board__main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.announcement-board__copy {
  min-width: 0;
}

.announcement-board__signal {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
}

.announcement-board__eyebrow {
  margin: 0;
  font-size: 0.68rem;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  color: #0284c7;
}

.announcement-board__pulse {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
  box-shadow: 0 0 0 6px rgba(56, 189, 248, 0.12);
}

.announcement-board__status {
  margin: 0;
  font-size: 0.72rem;
  color: #64748b;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.announcement-board__dots {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.announcement-board__dot {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.3);
}

.announcement-board__dot--active {
  width: 1.3rem;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
}

.announcement-board__title {
  margin: 0;
  font-size: clamp(1rem, 0.88rem + 0.45vw, 1.26rem);
  font-weight: 700;
  color: #0f172a;
}

.announcement-board__body {
  margin: 0.25rem 0 0;
  max-width: 52rem;
  line-height: 1.66;
  font-size: 0.88rem;
  color: #475569;
}

.announcement-board__link {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  min-width: 148px;
  padding: 0.82rem 0.95rem;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.14), rgba(59, 130, 246, 0.1));
  color: #0f172a;
  text-decoration: none;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid rgba(125, 211, 252, 0.24);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
  transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.announcement-board__link:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(191, 219, 254, 0.18));
  border-color: rgba(125, 211, 252, 0.42);
}

.announcement-float-enter-active,
.announcement-float-leave-active {
  transition: opacity 220ms ease, transform 260ms ease;
}

.announcement-float-enter-from,
.announcement-float-leave-to {
  opacity: 0;
  transform: translateY(-16px) scale(0.985);
}

@media (max-width: 768px) {
  .announcement-board-shell {
    top: 4.15rem;
  }

  .announcement-board__content {
    padding: 0.95rem 0.95rem 1rem;
  }

  .announcement-board__lead,
  .announcement-board__main {
    flex-direction: column;
    align-items: flex-start;
  }

  .announcement-board__signal {
    flex-wrap: wrap;
  }

  .announcement-board__link {
    width: 100%;
    min-width: 0;
  }
}
</style>