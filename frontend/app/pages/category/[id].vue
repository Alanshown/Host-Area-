<template>
  <div class="space-y-6">
    <!-- 横幅 Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 text-white shadow-lg">
      <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
      <div class="relative px-8 py-12 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
          <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-semibold tracking-wider uppercase mb-4 shadow-sm border border-white/10">探索专区</span>
          <h1 class="text-3xl md:text-5xl font-black tracking-tight mb-2 flex items-center gap-3">
            {{ currentCategory?.name ?? '版块' }}
            <span v-if="pending" class="w-8 h-8 rounded-full border-4 border-white/20 border-t-white animate-spin"></span>
          </h1>
          <p class="text-blue-100 text-sm md:text-base max-w-xl leading-relaxed">
            {{ currentCategory?.description ?? '汇集最新热门讨论，发现更多有趣内容' }}
          </p>
        </div>
        
        <div class="flex items-center gap-4 bg-black/20 backdrop-blur-md px-5 py-3 rounded-xl border border-white/10 shadow-inner">
          <div class="text-center">
            <span class="block text-2xl font-bold">{{ posts.length }}</span>
            <span class="block text-[10px] text-blue-200 uppercase tracking-widest mt-1">帖子帖数</span>
          </div>
          <div class="w-px h-10 bg-white/20"></div>
          <div class="text-center">
            <span class="block text-2xl font-bold text-yellow-300">🔥</span>
            <span class="block text-[10px] text-blue-200 uppercase tracking-widest mt-1">活跃状态</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- 左侧分类列表 -->
      <div class="hidden lg:block col-span-1">
        <div class="sticky top-24 bg-white border border-gray-100 rounded-xl p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
          <h3 class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-4 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            其他板块
          </h3>
          <ul class="space-y-1.5">
            <li v-for="cat in allCategories" :key="cat.id">
              <NuxtLink
                :to="`/category/${cat.id}`"
                class="group flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                :class="cat.id == route.params.id ? 'bg-blue-50 text-blue-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
              >
                <span>{{ cat.name }}</span>
                <svg v-if="cat.id == route.params.id" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span v-else class="w-1.5 h-1.5 rounded-full bg-gray-200 group-hover:bg-gray-300 transition-colors"></span>
              </NuxtLink>
            </li>
          </ul>
        </div>
      </div>

      <!-- 中间帖子列表 -->
      <div class="col-span-1 lg:col-span-3">
        <!-- 筛选栏 -->
        <div class="flex items-center justify-between bg-white border border-gray-100 p-2 rounded-xl mb-6 shadow-sm">
          <div class="flex gap-2">
            <button class="px-4 py-1.5 text-sm font-medium bg-gray-900 text-white rounded-lg shadow-sm">最新发表</button>
            <button class="px-4 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">最多讨论</button>
            <button class="px-4 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">热度排序</button>
          </div>
          <NuxtLink to="/post/create" class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-bold bg-blue-50 text-blue-600 hover:bg-blue-100 hover:shadow-sm rounded-lg transition-all border border-blue-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>发新帖
          </NuxtLink>
        </div>

        <div v-if="pending" class="space-y-4">
          <div v-for="i in 4" :key="i" class="bg-white border border-gray-100 rounded-xl p-5 animate-pulse shadow-sm">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
            <div class="flex gap-4 mb-4"><div class="h-3 bg-gray-100 rounded w-20"></div><div class="h-3 bg-gray-100 rounded w-20"></div></div>
            <div class="h-2 bg-gray-50 rounded w-full mt-2"></div>
          </div>
        </div>

        <div v-else-if="posts.length === 0" class="text-center py-24 bg-white border border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center">
          <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
          </div>
          <h3 class="text-gray-900 font-bold text-lg mb-1">这里是一片荒野</h3>
          <p class="text-sm text-gray-500 mb-6">该分类下暂无任何帖子，抢个沙发吧！</p>
          <NuxtLink to="/post/create" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium shadow-sm shadow-blue-600/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
            点击发布第一帖
          </NuxtLink>
        </div>

        <div v-else class="space-y-4">
          <NuxtLink v-for="post in posts" :key="post.id" :to="`/post/${post.id}`" class="block transform transition-transform duration-200 hover:-translate-y-1">
            <PostCard :post="post" class="shadow-sm hover:shadow-md hover:border-blue-200" />
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const route   = useRoute()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

// 所有分类（侧边栏用）
const { data: catData } = await useFetch(`${apiBase}/categories`)
const allCategories = computed(() => catData.value?.data ?? [])
const currentCategory = computed(() =>
  allCategories.value.find(c => c.id == route.params.id)
)

// 当前分类帖子
const { data, pending } = await useFetch(
  () => `${apiBase}/posts?category_id=${route.params.id}&per_page=20`
)
const posts = computed(() => data.value?.data ?? [])
</script>
