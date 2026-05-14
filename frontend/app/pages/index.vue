<template>
  <div class="grid grid-cols-1 md:grid-cols-12 gap-6 relative">
    <!-- 左侧侧边栏 -->
    <div class="col-span-12 md:col-span-3">
      <div class="sticky top-24 space-y-6">
        <div class="bg-white/70 backdrop-blur-xl border border-gray-200/80 rounded-2xl p-4 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
          <ul class="space-y-1">
            <li>
              <NuxtLink to="/" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-100/80 text-blue-600 font-medium">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>最新发现</span>
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/hot" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                <span>热门趋势</span>
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/category" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>全部分类</span>
              </NuxtLink>
            </li>
          </ul>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-full overflow-hidden rounded-[28px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_34%),linear-gradient(160deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0.92))] p-5 shadow-[0_14px_40px_rgba(15,23,42,0.08)] backdrop-blur-xl">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-sky-600/80">Signal Deck</p>
                <h3 class="mt-1 text-sm font-bold text-slate-900">热门标签</h3>
              </div>
              <span class="rounded-full border border-sky-200/80 bg-white/75 px-2.5 py-1 text-[0.68rem] font-semibold text-slate-500">前端静态精选</span>
            </div>

            <div class="flex flex-wrap gap-2.5">
              <span
                v-for="tag in hotTags"
                :key="tag.label"
                class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.08em] shadow-sm ring-1"
                :class="tag.tone"
              >
                <span class="text-[0.7rem] opacity-70">#</span>
                <span>{{ tag.label }}</span>
              </span>
            </div>

            <p class="mt-4 text-xs leading-6 text-slate-500">
              这里展示社区里最常被讨论的技术语义标签，用来营造内容氛围，不参与首页筛选逻辑。
            </p>
          </div>
          <AmbientPullTab class="-mt-1" />
        </div>
      </div>
    </div>

    <!-- 中间主内容区 -->
    <div class="col-span-12 md:col-span-6">
      <div class="bg-white/70 backdrop-blur-xl border border-gray-200/80 rounded-2xl p-3 mb-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex items-center justify-between">
        <div class="flex-1 flex items-center bg-gray-50 rounded-lg px-3 py-2 mr-4 border border-transparent focus-within:bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 transition-all">
          <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="searchQuery" type="text" placeholder="搜索您感兴趣的内容..." class="w-full bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-400" @input="debouncedRefresh">
          <button v-if="searchQuery" type="button" class="text-gray-400 transition hover:text-gray-600" @click="clearSearch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <NuxtLink to="/post/create" class="flex-shrink-0 flex items-center space-x-1.5 px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>发布</span>
        </NuxtLink>
      </div>

      <div class="flex items-center justify-between mb-4 px-1">
        <div class="flex space-x-5 text-sm font-medium">
          <button type="button" class="pb-1 transition-colors" :class="sortMode === 'hot' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-500 hover:text-gray-900'" @click="changeSort('hot')">推荐</button>
          <button type="button" class="pb-1 transition-colors" :class="sortMode === 'latest' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-500 hover:text-gray-900'" @click="changeSort('latest')">最新</button>
        </div>
        <button v-if="selectedCategoryId || searchQuery" type="button" class="text-xs text-gray-500 hover:text-gray-900 transition-colors" @click="resetFilters">清除筛选</button>
      </div>

      <div v-if="loadingFeed && !posts.length" class="space-y-4">
        <div v-for="i in 4" :key="i" class="bg-white/70 backdrop-blur-xl border border-gray-200/80 rounded-2xl p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
          <div class="h-3 bg-gray-100 rounded w-full mb-2"></div>
          <div class="h-3 bg-gray-100 rounded w-2/3"></div>
        </div>
      </div>

      <div v-else-if="!posts.length" class="rounded-xl border border-dashed border-gray-200 bg-white/70 px-6 py-12 text-center text-sm text-gray-400">
        当前筛选条件下没有帖子
      </div>

      <div v-else class="space-y-4">
        <NuxtLink v-for="post in posts" :key="post.id" :to="`/post/${post.id}`" class="block">
          <PostCard :post="post" :liked="likedSet.has(post.id)" :favorited="favoritedSet.has(post.id)" @like-toggled="onLikeToggled" @favorite-toggled="onFavoriteToggled" />
        </NuxtLink>
      </div>

      <div class="py-8 text-center space-y-3">
        <button
          v-if="hasMore"
          :disabled="loadingMore"
          class="px-6 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm inline-flex items-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
          @click="loadMore"
        >
          <span>{{ loadingMore ? '加载中...' : '加载更多' }}</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <p v-if="loadMoreError" class="text-sm text-red-500">{{ loadMoreError }}</p>
        <p v-else-if="!hasMore && posts.length" class="text-sm text-gray-400">已经到底了</p>
      </div>
    </div>

    <!-- 右侧小组件栏 -->
    <div class="col-span-12 md:col-span-3">
      <div class="sticky top-24 space-y-6">
        <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl p-5 shadow-sm text-white overflow-hidden relative">
          <div class="absolute -right-4 -top-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
          <h3 class="font-bold text-lg mb-2 relative z-10">Hostarea 社区</h3>
          <p class="text-indigo-50 text-sm leading-relaxed relative z-10">高质量的技术交流与分享空间。在这里，你可以分享灵感，解决困惑，结识志同道合的开发者。</p>
          <div class="mt-4 flex items-center justify-between text-xs text-indigo-100 border-t border-indigo-400/30 pt-3 relative z-10">
            <div><div class="font-bold text-white text-base">{{ stats.posts }}</div><div>帖子</div></div>
            <div><div class="font-bold text-white text-base">{{ stats.users }}</div><div>用户</div></div>
          </div>
        </div>

        <div class="bg-white/70 backdrop-blur-xl border border-gray-200/80 rounded-2xl p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
          <h3 class="font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center">
            <svg class="w-4 h-4 text-orange-500 mr-1.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/></svg>
            24小时热议
          </h3>
          <ul class="space-y-4">
            <li v-for="(item, i) in hotPosts" :key="i" class="flex gap-3 items-start group cursor-pointer">
              <span class="text-orange-400 font-bold mt-0.5 text-sm w-4 text-center">{{ i + 1 }}</span>
              <div>
                <p class="text-sm font-medium text-gray-800 group-hover:text-blue-600 transition-colors leading-snug line-clamp-2">{{ item.title }}</p>
                <div class="flex items-center text-xs text-gray-500 mt-1.5 space-x-3">
                  <span>{{ item.likes }} 赞</span>
                  <span>{{ item.comments_count ?? 0 }} 评论</span>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- Personalized Recommendations -->
        <RecommendedPosts :limit="5" />

        <!-- Recommended Users -->
        <RecommendedUsers :limit="5" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const config   = useRuntimeConfig()
const apiBase  = config.public.apiBase
const route = useRoute()
const router = useRouter()

const sortMode = ref(route.query.sort === 'latest' ? 'latest' : 'hot')
const searchQuery = ref(typeof route.query.search === 'string' ? route.query.search : '')
const selectedCategoryId = ref(typeof route.query.category_id === 'string' ? route.query.category_id : '')

const buildFeedParams = (page = 1) => {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('sort', sortMode.value)
  if (searchQuery.value.trim()) params.set('search', searchQuery.value.trim())
  if (selectedCategoryId.value) params.set('category_id', selectedCategoryId.value)
  return params.toString()
}

const postsRes = await $fetch(`${apiBase}/posts?${buildFeedParams(1)}`)
const { data: hotRes }                             = await useFetch(`${apiBase}/posts/hot`)
const { data: statsRes }                           = await useFetch(`${apiBase}/stats`)

const postList = ref(postsRes?.data ?? [])
const postMeta = ref({
  current_page: postsRes?.current_page ?? 1,
  last_page: postsRes?.last_page ?? 1,
})

const posts    = computed(() => postList.value)
const hotPosts = computed(() => hotRes.value?.data      ?? [])
const stats    = computed(() => ({
  posts: statsRes.value?.posts ?? 0,
  users: statsRes.value?.users ?? 0,
}))

const hotTags = [
  { label: 'Vue 3', tone: 'border-sky-200/80 bg-sky-50/90 text-sky-700 ring-sky-100' },
  { label: 'Nuxt 4', tone: 'border-emerald-200/80 bg-emerald-50/90 text-emerald-700 ring-emerald-100' },
  { label: 'Laravel', tone: 'border-rose-200/80 bg-rose-50/90 text-rose-700 ring-rose-100' },
  { label: 'Tailwind CSS', tone: 'border-cyan-200/80 bg-cyan-50/90 text-cyan-700 ring-cyan-100' },
  { label: 'TypeScript', tone: 'border-indigo-200/80 bg-indigo-50/90 text-indigo-700 ring-indigo-100' },
  { label: 'MySQL', tone: 'border-amber-200/80 bg-amber-50/90 text-amber-700 ring-amber-100' },
  { label: 'Docker', tone: 'border-blue-200/80 bg-blue-50/90 text-blue-700 ring-blue-100' },
  { label: 'AI Agent', tone: 'border-fuchsia-200/80 bg-fuchsia-50/90 text-fuchsia-700 ring-fuchsia-100' },
]

const { apiFetch, resolveMediaUrl } = useApi()
const auth = useAuth()
const likedSet     = ref(new Set())
const favoritedSet = ref(new Set())
const loadingMore = ref(false)
const loadMoreError = ref('')
const loadingFeed = ref(false)
const hasMore = computed(() => postMeta.value.current_page < postMeta.value.last_page)

let refreshTimer = null

const fetchInteractions = async () => {
  if (!auth.isLoggedIn.value) return
  const ids = posts.value.map(p => p.id).join(',')
  if (!ids) return
  try {
    const [likeRes, favRes] = await Promise.all([
      apiFetch(`/likes/batch-check?ids=${ids}`),
      apiFetch(`/favorites/batch-check?ids=${ids}`),
    ])
    likedSet.value = new Set(likeRes.data ?? [])
    favoritedSet.value = new Set(favRes.data ?? [])
  } catch {}
}

onMounted(async () => {
  auth.initAuth()
  await fetchInteractions()
})

watch(posts, () => { fetchInteractions() })

const syncRouteQuery = async () => {
  const query = {}
  if (sortMode.value !== 'hot') query.sort = sortMode.value
  if (searchQuery.value.trim()) query.search = searchQuery.value.trim()
  if (selectedCategoryId.value) query.category_id = selectedCategoryId.value
  await router.replace({ query })
}

const refreshFeed = async () => {
  loadingFeed.value = true
  loadMoreError.value = ''

  try {
    const response = await $fetch(`${apiBase}/posts?${buildFeedParams(1)}`)
    postList.value = response.data ?? []
    postMeta.value = {
      current_page: response.current_page ?? 1,
      last_page: response.last_page ?? 1,
    }
    await syncRouteQuery()
    await fetchInteractions()
  } catch (error) {
    loadMoreError.value = extractApiError(error, '加载帖子失败，请稍后重试')
    postList.value = []
  } finally {
    loadingFeed.value = false
  }
}

const debouncedRefresh = () => {
  clearTimeout(refreshTimer)
  refreshTimer = setTimeout(() => {
    refreshFeed()
  }, 350)
}

const changeSort = (sort) => {
  if (sortMode.value === sort) return
  sortMode.value = sort
  refreshFeed()
}

const clearSearch = () => {
  searchQuery.value = ''
  refreshFeed()
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategoryId.value = ''
  sortMode.value = 'hot'
  refreshFeed()
}

const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return

  loadingMore.value = true
  loadMoreError.value = ''

  try {
    const nextPage = postMeta.value.current_page + 1
    const response = await $fetch(`${apiBase}/posts?${buildFeedParams(nextPage)}`)
    const nextItems = response.data ?? []
    const existingIds = new Set(postList.value.map((item) => item.id))

    postList.value = [
      ...postList.value,
      ...nextItems.filter((item) => !existingIds.has(item.id)),
    ]
    postMeta.value = {
      current_page: response.current_page ?? nextPage,
      last_page: response.last_page ?? nextPage,
    }
    await fetchInteractions()
  } catch (error) {
    loadMoreError.value = extractApiError(error, '加载失败，请稍后重试')
  } finally {
    loadingMore.value = false
  }
}

const onLikeToggled = ({ postId, liked, likes }) => {
  if (liked) likedSet.value.add(postId); else likedSet.value.delete(postId)
  const p = posts.value.find(x => x.id === postId)
  if (p) p.likes = likes
}
const onFavoriteToggled = ({ postId, favorited }) => {
  if (favorited) favoritedSet.value.add(postId); else favoritedSet.value.delete(postId)
}

onBeforeUnmount(() => {
  clearTimeout(refreshTimer)
})
</script>
