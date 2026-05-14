<template>
  <div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold text-gray-900">我发布的帖子</h1>
      <NuxtLink to="/post/create" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">发布新帖</NuxtLink>
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

    <div v-else-if="posts.length === 0" class="text-center py-16 text-gray-400">
      <p class="mb-3 text-sm">还没有发布过任何帖子</p>
      <NuxtLink to="/post/create" class="text-blue-600 text-sm hover:underline">现在发布第一片</NuxtLink>
    </div>

    <div v-else class="space-y-4">
      <div v-for="post in posts" :key="post.id" class="relative group">
        <PostCard :post="post" />
        <div class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <NuxtLink :to="`/post/edit/${post.id}`" class="text-xs bg-white border border-gray-200 text-gray-600 px-2 py-1 rounded-lg hover:bg-gray-50 shadow-sm">编辑</NuxtLink>
          <button @click="deletePost(post.id)" class="text-xs bg-white border border-red-200 text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 shadow-sm">删除</button>
        </div>
      </div>
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

const userId = computed(() => auth.user.value?.id)

const { data, pending, refresh } = await useFetch(
  () => userId.value ? `${apiBase}/users/${userId.value}/posts` : null
)
const posts = computed(() => data.value?.data ?? [])

const deletePost = async (id) => {
  if (!confirm('确定删除这篇帖子？')) return
  try {
    await $fetch(`${apiBase}/posts/${id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
    })
    refresh()
  } catch (e) {
    alert('删除失败')
  }
}
</script>
