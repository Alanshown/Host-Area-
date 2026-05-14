<template>
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold text-gray-900">我的评论</h1>
      <NuxtLink to="/user/settings" class="text-sm text-gray-500 hover:text-gray-700">管理资料</NuxtLink>
    </div>

    <div v-if="!auth.isLoggedIn.value" class="text-center py-16 text-gray-400">
      <p class="mb-3">请先登录</p>
      <NuxtLink to="/login" class="text-blue-600 hover:underline">前往登录</NuxtLink>
    </div>

    <div v-else-if="pending" class="space-y-4">
      <div v-for="i in 4" :key="i" class="rounded-xl border border-gray-200 bg-white p-5 animate-pulse">
        <div class="mb-3 h-4 w-1/2 rounded bg-gray-200"></div>
        <div class="h-3 w-2/3 rounded bg-gray-100"></div>
      </div>
    </div>

    <div v-else-if="comments.length === 0" class="text-center py-16 text-gray-400">
      <p class="text-sm">还没有发表任何评论</p>
    </div>

    <div v-else class="space-y-4">
      <div v-for="comment in comments" :key="comment.id" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-4 text-xs text-gray-500">
          <NuxtLink :to="`/post/${comment.post?.id}`" class="font-medium text-blue-600 hover:underline">
            {{ comment.post?.title || '原帖已删除' }}
          </NuxtLink>
          <span>{{ timeAgo(comment.created_at) }}</span>
        </div>
        <p class="mb-3 text-sm leading-7 text-gray-700">{{ comment.content }}</p>
        <div class="flex justify-end">
          <NuxtLink :to="`/post/${comment.post?.id}`" class="text-sm text-gray-500 hover:text-blue-600">查看原帖</NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { timeAgo } from '~/composables/useApi.js'

const auth = useAuth()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

if (process.client) {
  auth.initAuth()
}

const userId = computed(() => auth.user.value?.id)

const { data, pending } = await useFetch(
  () => userId.value ? `${apiBase}/users/${userId.value}/comments` : null
)

const comments = computed(() => data.value?.data ?? [])
</script>