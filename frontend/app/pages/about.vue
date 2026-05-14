<template>
  <div class="max-w-2xl mx-auto">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-8 mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">关于 HostArea</h1>
      <p class="text-sm text-blue-600 font-medium mb-6">Host the Area · 社区论坛</p>
      <div class="prose prose-sm text-gray-600 space-y-4 leading-relaxed">
        <p>HostArea 是一个开放、自由的社区论坛平台，致力为用户提供一个友好、包容的讨论环境。无论是技术讨论、生活分享还是经验交流，这里都是你的家。</p>
        <p>我们相信知识应该被共享，每个声音都应该被尊重。因此，我们尽力保持论坛的帀防秩序、配备完善的分类体系、实时的评论互动，让交流变得更加高效。</p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
        <p class="text-2xl font-bold text-blue-600 mb-1">{{ stats.posts_count ?? '...' }}</p>
        <p class="text-xs text-gray-500">已发布帖子</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
        <p class="text-2xl font-bold text-green-600 mb-1">{{ stats.users_count ?? '...' }}</p>
        <p class="text-xs text-gray-500">注册用户</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
        <p class="text-2xl font-bold text-orange-600 mb-1">{{ stats.comments_count ?? '...' }}</p>
        <p class="text-xs text-gray-500">精彩评论</p>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
      <h2 class="font-bold text-gray-900 mb-3">技术栈</h2>
      <div class="flex flex-wrap gap-2">
        <span v-for="tech in techs" :key="tech" class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">{{ tech }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

const { data } = await useFetch(`${apiBase}/stats`)
const stats = computed(() => data.value ?? {})

const techs = ['Vue 3', 'Nuxt 4', 'Tailwind CSS', 'Laravel 8', 'PHP', 'MySQL', 'Sanctum API']
</script>
