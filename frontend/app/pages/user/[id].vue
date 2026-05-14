<template>
  <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="mb-4 flex items-center justify-between gap-3">
      <button type="button" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/80 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-white hover:text-gray-900" @click="goBack">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        返回上一页
      </button>
      <p v-if="!isSelf" class="text-xs text-gray-400">当前页面仅开放浏览与关注操作</p>
    </div>

    <div class="mb-8 overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl border border-gray-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
      <div class="relative h-56 md:h-[22rem]">
        <div v-if="bannerImages.length" class="absolute inset-0">
          <div v-for="(banner, index) in bannerImages" :key="banner.path + index" class="absolute inset-0 overflow-hidden transition-opacity duration-[1600ms]" :class="index === activeBanner ? 'opacity-100' : 'opacity-0'">
            <img :src="resolveMediaUrl(banner.path)" alt="profile banner" class="h-full w-full object-cover" :style="getBannerDisplayStyle(banner)" />
          </div>
        </div>
        <div v-else class="h-full w-full bg-gradient-to-r from-blue-400 via-sky-500 to-indigo-500"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/35 via-black/10 to-transparent"></div>
      </div>

      <div class="relative px-6 pb-6">
        <div class="mb-4 flex flex-col gap-4 sm:-mt-16 sm:flex-row sm:items-end sm:justify-between">
          <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-end sm:gap-5">
            <div class="h-24 w-24 overflow-hidden rounded-full border-4 border-white bg-white shadow-md sm:h-32 sm:w-32">
              <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
              <div v-else class="flex h-full w-full items-center justify-center bg-blue-100 text-3xl font-bold text-blue-500">{{ avatarLetter }}</div>
            </div>

            <div class="pb-2 text-center sm:text-left">
              <h1 class="profile-identity__name text-2xl font-bold text-gray-900 font-sans inline-flex items-center gap-2">
                <span>{{ user.username }}</span>
                <span class="profile-identity__badge" aria-label="已认证用户" title="已认证用户">
                  <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                    <path d="M12 2.5l2.44 1.54 2.88-.22 1.54 2.44 2.66 1.13-.22 2.88L21.5 12l-1.54 2.44.22 2.88-2.44 1.54-1.13 2.66-2.88-.22L12 21.5l-2.44-1.54-2.88.22-1.54-2.44-2.66-1.13.22-2.88L2.5 12l1.54-2.44-.22-2.88 2.44-1.54 1.13-2.66 2.88.22L12 2.5z" fill="currentColor"/>
                    <path d="M8.5 12.2l2.2 2.2 4.8-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              </h1>
              <p class="mt-1 text-sm text-gray-500">@{{ user.username }} · 加入于 {{ joinedAt }}</p>
            </div>
          </div>

          <div class="mt-2 flex justify-center gap-3 sm:justify-start">
            <NuxtLink v-if="isSelf" to="/user/settings" class="rounded-full border border-gray-200 bg-gray-100 px-6 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200">编辑资料</NuxtLink>
            <NuxtLink v-if="isSelf" to="/post/create" class="rounded-full bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">发布新帖</NuxtLink>
            <button v-if="!isSelf" type="button" :disabled="followLoading" class="rounded-full px-6 py-2 text-sm font-medium transition-colors" :class="isFollowing ? 'border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100' : 'bg-gray-900 text-white hover:bg-gray-800'" @click="toggleFollow">
              {{ followLoading ? '处理中...' : isFollowing ? '已关注' : '关注 TA' }}
            </button>
          </div>
        </div>

        <p class="max-w-3xl text-center leading-relaxed text-gray-700 sm:text-left">{{ user.bio || '这个人很神秘，什么都没有留下。' }}</p>

        <p v-if="followMessage" class="mt-3 text-center text-sm sm:text-left" :class="followError ? 'text-rose-500' : 'text-emerald-600'">{{ followMessage }}</p>

        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-50 pt-6 sm:grid-cols-5">
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ user.posts_count ?? 0 }}</span>
            <span class="text-xs text-gray-500">帖子</span>
          </div>
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ user.comments_count ?? 0 }}</span>
            <span class="text-xs text-gray-500">评论</span>
          </div>
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ user.favorites_count ?? 0 }}</span>
            <span class="text-xs text-gray-500">收藏</span>
          </div>
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ user.likes_received ?? 0 }}</span>
            <span class="text-xs text-gray-500">获赞</span>
          </div>
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ followersCount }}</span>
            <span class="text-xs text-gray-500">粉丝</span>
          </div>
          <div class="text-center">
            <span class="block text-xl font-bold text-gray-900">{{ followingCount }}</span>
            <span class="text-xs text-gray-500">关注</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
      <div class="lg:col-span-3">
        <div class="mb-6 flex overflow-x-auto rounded-xl bg-white/70 backdrop-blur-xl border border-gray-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
          <button v-for="tab in visibleTabs" :key="tab.key" class="whitespace-nowrap border-b-2 px-6 py-4 text-sm font-medium transition-colors" :class="activeTab === tab.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" @click="setTab(tab.key)">{{ tab.label }}</button>
        </div>

        <div v-if="activeTab === 'posts'" class="space-y-4">
          <NuxtLink v-for="post in posts" :key="post.id" :to="`/post/${post.id}`" class="block">
            <PostCard :post="post" />
          </NuxtLink>
          <div v-if="posts.length === 0" class="rounded-xl border-2 border-dashed border-gray-200/80 bg-white/40 py-12 rounded-2xl backdrop-blur-md text-center text-sm text-gray-500">暂无帖子</div>
        </div>

        <div v-else-if="activeTab === 'comments'" class="space-y-4">
          <div v-for="comment in comments" :key="comment.id" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-4 text-xs text-gray-500">
              <NuxtLink :to="`/post/${comment.post?.id}`" class="font-medium text-blue-600 hover:underline">{{ comment.post?.title || '原帖已删除' }}</NuxtLink>
              <span>{{ timeAgo(comment.created_at) }}</span>
            </div>
            <p class="text-sm leading-7 text-gray-700">{{ comment.content }}</p>
          </div>
          <div v-if="comments.length === 0" class="rounded-xl border-2 border-dashed border-gray-200/80 bg-white/40 py-12 rounded-2xl backdrop-blur-md text-center text-sm text-gray-500">暂无评论</div>
        </div>

        <div v-else class="space-y-4">
          <NuxtLink v-for="favorite in favorites" :key="favorite.id" :to="`/post/${favorite.post?.id}`" class="block">
            <PostCard :post="favorite.post" />
          </NuxtLink>
          <div v-if="favorites.length === 0" class="rounded-xl border-2 border-dashed border-gray-200/80 bg-white/40 py-12 rounded-2xl backdrop-blur-md text-center text-sm text-gray-500">暂无收藏</div>
        </div>
      </div>

      <div class="hidden lg:block lg:col-span-1">
        <div class="mb-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <h3 class="mb-4 border-b border-gray-50 pb-2 font-bold text-gray-900">社区荣誉</h3>
          <ul class="space-y-3">
            <li v-for="honor in user.honors || []" :key="honor.label" class="flex items-center gap-3 text-sm">
              <span class="flex h-8 w-8 items-center justify-center rounded-full" :class="honorClass(honor.tone)">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </span>
              <span class="text-gray-700">{{ honor.label }}</span>
            </li>
          </ul>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <h3 class="mb-4 border-b border-gray-50 pb-2 font-bold text-gray-900">最近访问</h3>
          <div v-if="recentVisitors.length" class="flex flex-wrap gap-2">
            <NuxtLink v-for="visitor in recentVisitors" :key="visitor.id" :to="`/user/${visitor.id}`" class="group relative">
              <div class="h-10 w-10 overflow-hidden rounded-full bg-gray-200 ring-2 ring-white transition-transform group-hover:scale-105">
                <img v-if="resolveMediaUrl(visitor.avatar)" :src="resolveMediaUrl(visitor.avatar)" alt="visitor avatar" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-xs font-bold text-gray-500">{{ visitor.username?.[0]?.toUpperCase() }}</div>
              </div>
            </NuxtLink>
          </div>
          <p v-else class="text-sm text-gray-500">还没有人留下访问记录。</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { getBannerDisplayStyle, normalizeBannerItem, timeAgo } from '~/composables/useApi.js'

const route = useRoute()
const router = useRouter()
const auth = useAuth()
const { apiBase, apiFetch, resolveMediaUrl } = useApi()
const id = route.params.id

const { data: userRes, refresh: refreshUser } = await useFetch(`${apiBase}/users/${id}`)
const { data: postsRes } = await useFetch(`${apiBase}/users/${id}/posts`)
const { data: commentsRes } = await useFetch(`${apiBase}/users/${id}/comments`)
const user = computed(() => userRes.value?.data ?? {})
const posts = computed(() => postsRes.value?.data ?? [])
const comments = computed(() => commentsRes.value?.data ?? [])
const favorites = ref([])

const activeBanner = ref(0)
const followLoading = ref(false)
const followMessage = ref('')
const followError = ref(false)
const followStats = reactive({
  isFollowing: false,
  followersCount: 0,
  followingCount: 0,
})
let bannerTimer = null

const activeTab = computed(() => route.query.tab || 'posts')
const isSelf = computed(() => auth.user.value?.id === Number(id))
const avatarLetter = computed(() => user.value.username?.[0]?.toUpperCase() ?? '?')
const avatarUrl = computed(() => resolveMediaUrl(user.value.avatar))
const joinedAt = computed(() => user.value.created_at ? user.value.created_at.slice(0, 10) : '')
const recentVisitors = computed(() => user.value.recent_visitors ?? [])
const bannerImages = computed(() => (user.value.profile_banners ?? []).map((item) => normalizeBannerItem(item)).filter((item) => item?.path))
const isFollowing = computed(() => followStats.isFollowing)
const followersCount = computed(() => (followStats.followersCount || followStats.followersCount === 0) ? followStats.followersCount : (user.value.followers_count ?? 0))
const followingCount = computed(() => (followStats.followingCount || followStats.followingCount === 0) ? followStats.followingCount : (user.value.following_count ?? 0))

const visibleTabs = computed(() => {
  const tabs = [
    { key: 'posts', label: '发布的帖子' },
    { key: 'comments', label: '用户的评论' },
  ]

  if (isSelf.value) {
    tabs.push({ key: 'favorites', label: '收藏夹' })
  }

  return tabs
})

const setTab = async (tab) => {
  await navigateTo({ query: { ...route.query, tab } }, { replace: true })
}

const goBack = async () => {
  if (window.history.length > 1) {
    router.back()
    return
  }

  await navigateTo('/')
}

const honorClass = (tone) => {
  const map = {
    amber: 'bg-yellow-100 text-yellow-600',
    rose: 'bg-rose-100 text-rose-600',
    indigo: 'bg-indigo-100 text-indigo-600',
    emerald: 'bg-emerald-100 text-emerald-600',
    sky: 'bg-sky-100 text-sky-600',
  }

  return map[tone] || map.sky
}

const startBannerLoop = () => {
  if (bannerTimer) {
    clearInterval(bannerTimer)
  }

  if (bannerImages.value.length <= 1) {
    activeBanner.value = 0
    return
  }

  bannerTimer = window.setInterval(() => {
    activeBanner.value = (activeBanner.value + 1) % bannerImages.value.length
  }, 4200)
}

const syncFollowStatsFromUser = () => {
  followStats.followersCount = user.value.followers_count ?? 0
  followStats.followingCount = user.value.following_count ?? 0
}

const fetchFollowStatus = async () => {
  if (!auth.isLoggedIn.value || isSelf.value) {
    followStats.isFollowing = false
    syncFollowStatsFromUser()
    return
  }

  try {
    const response = await apiFetch(`/users/${id}/follow-status`)
    followStats.isFollowing = !!response.data?.is_following
    followStats.followersCount = response.data?.followers_count ?? user.value.followers_count ?? 0
    followStats.followingCount = response.data?.following_count ?? user.value.following_count ?? 0
  } catch {
    syncFollowStatsFromUser()
  }
}

const loadFavorites = async () => {
  if (!auth.isLoggedIn.value || auth.user.value?.id !== Number(id)) {
    favorites.value = []
    return
  }

  try {
    const response = await apiFetch(`/users/${id}/favorites`)
    favorites.value = response.data ?? []
  } catch {
    favorites.value = []
  }
}

const toggleFollow = async () => {
  if (!auth.isLoggedIn.value) {
    await navigateTo('/login')
    return
  }

  if (isSelf.value || followLoading.value) {
    return
  }

  followLoading.value = true
  followMessage.value = ''
  followError.value = false

  try {
    const response = await apiFetch(`/users/${id}/follow`, { method: 'POST' })
    followStats.isFollowing = !!response.data?.is_following
    followStats.followersCount = response.data?.followers_count ?? followStats.followersCount
    followStats.followingCount = response.data?.following_count ?? followStats.followingCount
    followMessage.value = response.message || (followStats.isFollowing ? '关注成功' : '已取消关注')
  } catch (error) {
    followError.value = true
    followMessage.value = error?.data?.message || '关注操作失败，请稍后重试'
  } finally {
    followLoading.value = false
  }
}

watch(user, () => {
  syncFollowStatsFromUser()
}, { immediate: true })

watch(bannerImages, () => {
  activeBanner.value = 0
  if (process.client) {
    startBannerLoop()
  }
})

onMounted(async () => {
  auth.initAuth()

  if (auth.isLoggedIn.value && !isSelf.value) {
    try {
      await apiFetch(`/users/${id}/visit`, { method: 'POST' })
      await refreshUser()
    } catch {}
  }

  startBannerLoop()
  await fetchFollowStatus()
  await loadFavorites()
})

onBeforeUnmount(() => {
  if (bannerTimer) {
    clearInterval(bannerTimer)
  }
})
</script>

<style scoped>
.profile-identity__name {
  letter-spacing: 0;
}

.profile-identity__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  border-radius: 999px;
  color: #1d9bf0;
  filter: drop-shadow(0 4px 10px rgba(29, 155, 240, 0.32));
}
</style>
