<template>
  <div class="max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">我的收藏</h1>

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

    <div v-else-if="favorites.length === 0" class="text-center py-16 text-gray-400">
      <p class="text-sm">还没有收藏任何帖子</p>
    </div>

    <div v-else class="space-y-4">
      <NuxtLink v-for="fav in favorites" :key="fav.id" :to="`/post/${fav.post.id}`" class="block">
        <PostCard :post="fav.post" />
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
const auth    = useAuth()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

if (process.client) {
  auth.initAuth()
}

const { data, pending } = await useFetch(
  () => auth.isLoggedIn.value ? `${apiBase}/user/favorites` : null,
  {
    headers: computed(() =>
      auth.isLoggedIn.value
        ? { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
        : {}
    )
  }
)
const favorites = computed(() => data.value?.data ?? [])
</script>
