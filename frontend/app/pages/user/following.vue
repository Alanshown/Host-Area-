<template>
  <div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6 gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">关注动态</h1>
        <p class="mt-1 text-sm text-gray-500">看看你关注的人最近发布了什么内容。</p>
      </div>
      <button type="button" class="text-sm border border-gray-200 bg-white px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors" @click="loadFeed">
        刷新
      </button>
    </div>

    <div v-if="!auth.isLoggedIn.value" class="text-center py-16 text-gray-400">
      <p class="mb-3">请先登录</p>
      <NuxtLink to="/login" class="text-blue-600 hover:underline">前往登录</NuxtLink>
    </div>

    <div v-else-if="pending" class="space-y-4">
      <div v-for="i in 4" :key="i" class="bg-white border border-gray-200 rounded-xl p-5 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
      </div>
    </div>

    <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">
      {{ error }}
    </div>

    <div v-else-if="posts.length === 0" class="text-center py-16 text-gray-400">
      <p class="mb-3 text-sm">你关注的人最近还没有公开内容</p>
      <p class="text-xs text-gray-400">先去别人的主页点一下“关注 TA”，这里才会慢慢有内容。</p>
    </div>

    <div v-else class="space-y-4">
      <NuxtLink v-for="post in posts" :key="post.id" :to="`/post/${post.id}`" class="block">
        <PostCard :post="post" />
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const auth = useAuth()
const { apiFetch } = useApi()

const posts = ref([])
const pending = ref(false)
const error = ref('')

const loadFeed = async () => {
  if (!auth.isLoggedIn.value) return

  pending.value = true
  error.value = ''

  try {
    const response = await apiFetch('/user/following-feed')
    posts.value = response.data ?? []
  } catch (err) {
    error.value = extractApiError(err, '关注动态加载失败，请稍后重试')
    posts.value = []
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  auth.initAuth()
  await loadFeed()
})
</script>