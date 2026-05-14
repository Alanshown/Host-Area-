<template>
  <div class="space-y-3">
    <div v-if="loading && !posts.length" class="space-y-3">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-full mb-1"></div>
        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
      </div>
    </div>

    <div v-else-if="!posts.length && !loading" class="text-center py-6 text-gray-400 text-sm">
      暂无推荐内容
    </div>

    <NuxtLink
      v-for="post in posts"
      :key="post.id"
      :to="`/post/${post.id}`"
      class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all group"
    >
      <div class="flex items-start gap-3">
        <img
          v-if="post.user?.avatar"
          :src="resolveMediaUrl(post.user.avatar)"
          class="w-8 h-8 rounded-full object-cover flex-shrink-0"
        />
        <div v-else class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">
          {{ post.user?.username?.[0]?.toUpperCase() || '?' }}
        </div>

        <div class="flex-1 min-w-0">
          <h4 class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 text-sm">
            {{ post.title }}
          </h4>
          <p class="text-xs text-gray-500 mt-1 line-clamp-1">
            {{ post.content || post.category_name || '技术讨论' }}
          </p>
          <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
            <span>{{ post.user?.username }}</span>
            <span class="flex items-center gap-1">
              <span>❤️</span> {{ post.likes || 0 }}
            </span>
            <span class="flex items-center gap-1">
              <span>💬</span> {{ post.comments_count || 0 }}
            </span>
          </div>
        </div>
      </div>
    </NuxtLink>
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
const posts = ref([])
const loading = ref(false)

const fetchRecommendations = async () => {
  if (!auth.isLoggedIn.value) return

  loading.value = true
  try {
    const response = await apiFetch(`/recommendations?type=posts&limit=${props.limit}`)
    posts.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch recommendations:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRecommendations()
})

watch(() => auth.isLoggedIn.value, (loggedIn) => {
  if (loggedIn) {
    fetchRecommendations()
  } else {
    posts.value = []
  }
})
</script>
