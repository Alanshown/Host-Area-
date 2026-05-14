<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-bold text-gray-900 pb-2 border-b border-gray-100 flex items-center">
        <svg class="w-4 h-4 text-indigo-500 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
          <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
        </svg>
        推荐用户
      </h3>
    </div>

    <div v-if="loading && !users.length" class="space-y-3">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-pulse">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-gray-200"></div>
          <div class="flex-1">
            <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
            <div class="h-3 bg-gray-100 rounded w-32"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="!users.length && !loading" class="text-center py-6 text-gray-400 text-sm">
      暂无推荐用户
    </div>

    <div
      v-for="user in users"
      :key="user.id"
      class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all group"
    >
      <div class="flex items-center gap-3">
        <NuxtLink :to="`/user/${user.id}`" class="flex-shrink-0">
          <img
            v-if="user.avatar"
            :src="resolveMediaUrl(user.avatar)"
            class="w-10 h-10 rounded-full object-cover ring-2 ring-transparent group-hover:ring-indigo-200 transition-all"
          />
          <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-600 font-bold">
            {{ user.username?.[0]?.toUpperCase() || '?' }}
          </div>
        </NuxtLink>

        <div class="flex-1 min-w-0">
          <NuxtLink :to="`/user/${user.id}`" class="block">
            <h4 class="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
              {{ user.username }}
            </h4>
          </NuxtLink>
          <p v-if="user.bio" class="text-xs text-gray-500 truncate mt-0.5">
            {{ user.bio }}
          </p>
          <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              {{ user.followers_count || 0 }} 粉丝
            </span>
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              {{ user.posts_count || 0 }} 帖子
            </span>
          </div>
        </div>

        <button
          v-if="auth.isLoggedIn.value && auth.user.value?.id !== user.id"
          type="button"
          class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-all"
          :class="user.is_following
            ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            : 'bg-indigo-600 text-white hover:bg-indigo-700'"
          @click.stop="toggleFollow(user)"
        >
          {{ user.is_following ? '已关注' : '关注' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  userId: {
    type: Number,
    default: null,
  },
  limit: {
    type: Number,
    default: 5,
  },
})

const { apiBase, apiFetch, resolveMediaUrl } = useApi()
const auth = useAuth()
const users = ref([])
const loading = ref(false)

const fetchRecommendations = async () => {
  if (!auth.isLoggedIn.value) return

  loading.value = true
  try {
    const response = await apiFetch(`/recommendations?type=users&limit=${props.limit}`)
    users.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch user recommendations:', error)
  } finally {
    loading.value = false
  }
}

const toggleFollow = async (user) => {
  if (!auth.isLoggedIn.value) return

  try {
    const response = await apiFetch(`/users/${user.id}/follow`, { method: 'POST' })
    user.is_following = response.is_following
  } catch (error) {
    console.error('Failed to toggle follow:', error)
  }
}

onMounted(() => {
  fetchRecommendations()
})

watch(() => auth.isLoggedIn.value, (loggedIn) => {
  if (loggedIn) {
    fetchRecommendations()
  } else {
    users.value = []
  }
})
</script>
