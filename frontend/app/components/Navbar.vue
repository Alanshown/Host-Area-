<template>
  <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="container mx-auto px-4 max-w-5xl h-16 flex items-center justify-between">
      <!-- Logo -->
      <div class="text-xl font-bold tracking-tight text-gray-900">
        <InteractiveBrand />
      </div>

      <!-- 主导航 -->
      <div class="flex items-center space-x-6">
        <NuxtLink to="/" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">首页</NuxtLink>
        <NuxtLink to="/hot" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">热门</NuxtLink>
        <NuxtLink to="/category" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">分类</NuxtLink>
        <NuxtLink to="/channel" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">聊天频道</NuxtLink>
        <NuxtLink v-if="auth.user.value?.role === 'admin'" to="/admin" class="text-sm font-medium text-cyan-600 hover:text-cyan-500 transition-colors">平台管理</NuxtLink>
      </div>

      <!-- 用户区 -->
      <div class="flex items-center space-x-3">
        <ThemeToggle />

        <!-- 已登录 -->
        <template v-if="auth.isLoggedIn.value">
          <NuxtLink
            to="/post/create"
            class="hidden sm:flex items-center gap-1 text-sm text-gray-600 hover:text-blue-600 transition-colors px-3 py-1.5 rounded-lg hover:bg-blue-50"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            发帖
          </NuxtLink>

          <!-- 用户下拉 -->
          <div class="relative" ref="dropdownRef">
            <button
              @click="open = !open"
              class="avatar-trigger flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div class="relative w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center flex-shrink-0 overflow-hidden">
                <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
                <span v-else>{{ (auth.user.value?.username ?? '?')[0].toUpperCase() }}</span>
                <span v-if="unreadCount > 0" class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white shadow-sm">
                  {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
              </div>
              <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[80px] truncate">{{ auth.user.value?.username }}</span>
              <svg class="w-3 h-3 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <Transition name="avatar-menu">
              <div
                v-if="open"
                class="avatar-menu-panel absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-2xl shadow-lg py-2 z-50 overflow-hidden"
              >
              <div class="px-4 pb-3 pt-1 border-b border-gray-100">
                <p class="text-[11px] uppercase tracking-[0.28em] text-gray-400">Profile Hub</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 truncate">{{ auth.user.value?.username }}</p>
              </div>
              <NuxtLink :to="`/user/${auth.user.value?.id}`" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                个人主页
              </NuxtLink>
              <NuxtLink to="/user/posts" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 1">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                我的帖子
              </NuxtLink>
              <NuxtLink to="/user/following" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-1a4 4 0 00-5.356-3.769M9 20H4v-1a4 4 0 015.356-3.769M9 20h6M12 12a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6 3 3 0 000 6zM6 12a3 3 0 100-6 3 3 0 000 6z"/></svg>
                关注动态
              </NuxtLink>
              <NuxtLink to="/user/favorites" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 3">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                我的收藏
              </NuxtLink>
              <NuxtLink to="/user/comments" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 4">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                我的评论
              </NuxtLink>
              <NuxtLink to="/user/notifications" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                通知中心
                <span v-if="unreadCount > 0" class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-semibold text-white">
                  {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
              </NuxtLink>
              <NuxtLink to="/user/reports" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 6">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2A9 9 0 1112 3a9 9 0 019 9z"/></svg>
                我的举报
              </NuxtLink>
              <NuxtLink to="/user/settings" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 7">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                个人设置
              </NuxtLink>
              <div v-if="auth.user.value?.role === 'admin'" class="border-t border-gray-100 mt-1 pt-1">
                <NuxtLink to="/admin" @click="open = false" class="avatar-menu-item flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50" style="--item-index: 8">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  管理后台
                </NuxtLink>
              </div>
              <div class="border-t border-gray-100 mt-1 pt-1">
                <button @click="logout" class="avatar-menu-item w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" style="--item-index: 9">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                  退出登录
                </button>
              </div>
            </div>
            </Transition>
          </div>
        </template>

        <!-- 未登录 -->
        <template v-else>
          <NuxtLink to="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">登录</NuxtLink>
          <NuxtLink to="/register" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors shadow-sm">注册</NuxtLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
const auth = useAuth()
const { resolveMediaUrl } = useApi()
const { summary, loadNotifications, reset } = useNotifications()
const open = ref(false)
const dropdownRef = ref(null)
const avatarUrl = computed(() => resolveMediaUrl(auth.user.value?.avatar))
const unreadCount = computed(() => summary.value.unread ?? 0)
let handleOutsideClick = null

const logout = async () => {
  open.value = false
  await auth.logout()
  navigateTo('/')
}

// 点击外部关闭下拉
onMounted(() => {
  handleOutsideClick = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
      open.value = false
    }
  }

  document.addEventListener('click', handleOutsideClick)

  if (auth.isLoggedIn.value) {
    loadNotifications().catch(() => {})
  }
})

watch(() => auth.isLoggedIn.value, (loggedIn) => {
  if (loggedIn) {
    loadNotifications().catch(() => {})
    return
  }

  reset()
})

onBeforeUnmount(() => {
  if (handleOutsideClick) {
    document.removeEventListener('click', handleOutsideClick)
  }
})
</script>