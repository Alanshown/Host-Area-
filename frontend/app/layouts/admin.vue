<template>
  <div :class="['admin-workbench-shell', theme.theme.value === 'dark' ? 'theme-dark' : 'theme-light']">
    <EdgeLoadingFrame />
    <AmbientBackdrop />

    <div class="admin-workbench-frame">
      <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
          <p class="admin-sidebar__eyebrow">DEVORYN</p>
          <h1 class="admin-sidebar__title">Platform Ops</h1>
          <p class="admin-sidebar__subtitle">以数据驾驶社区内容、用户和风控处理。</p>
        </div>

        <nav class="admin-sidebar__nav" aria-label="管理导航">
          <NuxtLink to="/" class="admin-sidebar__link">
            <span class="admin-sidebar__icon">◁</span>
            <span>
              <strong>返回前台</strong>
              <small>回到原前台主页</small>
            </span>
          </NuxtLink>

          <NuxtLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="admin-sidebar__link"
            :class="{ 'is-active': isActive(item) }"
          >
            <span class="admin-sidebar__icon">{{ item.icon }}</span>
            <span>
              <strong>{{ item.label }}</strong>
              <small>{{ item.desc }}</small>
            </span>
          </NuxtLink>
        </nav>

        <button type="button" class="admin-sidebar__logout" @click="handleLogout">退出后台</button>
      </aside>

      <div class="admin-stage">
        <header class="admin-stage__topbar">
          <div>
            <p class="admin-stage__eyebrow">{{ currentNav?.label || 'Dashboard' }}</p>
            <h2 class="admin-stage__headline">{{ greeting }}</h2>
            <p class="admin-stage__subline">{{ currentNav?.desc || '管理全站数据与内容流转。' }}</p>
          </div>

          <div class="admin-stage__meta">
            <span class="admin-stage__dot"></span>
            <div class="admin-stage__userchip">
              <img v-if="avatarUrl" :src="avatarUrl" :alt="auth.user.value?.username || 'admin'" class="admin-stage__avatar" />
              <span v-else class="admin-stage__avatar admin-stage__avatar--fallback">{{ initial }}</span>
              <div>
                <strong>{{ auth.user.value?.username || 'Admin' }}</strong>
                <small>{{ auth.user.value?.role || 'operator' }}</small>
              </div>
            </div>
          </div>
        </header>

        <main class="admin-stage__content">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
const auth = useAuth()
const theme = useTheme()
const route = useRoute()
const { resolveMediaUrl } = useApi()

const navItems = [
  { to: '/admin', label: '总览', desc: '数据曲线与工作台', icon: '◫' },
  { to: '/admin/users', label: '用户', desc: '身份、封禁与活跃', icon: '◌' },
  { to: '/admin/posts', label: '帖子', desc: '内容审核与分类热度', icon: '△' },
  { to: '/admin/comments', label: '评论', desc: '对话清理与上下文', icon: '◎' },
  { to: '/admin/reports', label: '举报', desc: '风险事件处理流', icon: '▣' },
]

const currentNav = computed(() => navItems.find((item) => route.path === item.to || route.path.startsWith(`${item.to}/`)))
const greeting = computed(() => {
  const hour = new Date().getHours()
  const prefix = hour < 12 ? '上午' : hour < 18 ? '下午' : '晚上'
  return `${prefix}好，${auth.user.value?.username || '管理员'}`
})
const avatarUrl = computed(() => resolveMediaUrl(auth.user.value?.avatar))
const initial = computed(() => auth.user.value?.username?.[0]?.toUpperCase() || 'A')

const isActive = (item) => route.path === item.to || route.path.startsWith(`${item.to}/`)

const handleLogout = async () => {
  await auth.logout()
  navigateTo('/login')
}

onMounted(async () => {
  theme.initTheme()
  auth.initAuth()
  await auth.refreshMe()
})
</script>