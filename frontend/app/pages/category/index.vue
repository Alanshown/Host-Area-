<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">全部分类</h1>

    <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div v-for="i in 6" :key="i" class="bg-white border border-gray-200 rounded-xl p-5 animate-pulse">
        <div class="h-5 bg-gray-200 rounded w-1/3 mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-2/3"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <NuxtLink
        v-for="(cat, i) in categories"
        :key="cat.id"
        :to="`/category/${cat.id}`"
        class="group relative overflow-hidden bg-white border border-gray-100 rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] hover:border-transparent"
        :style="`background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.8) 100%);`"
      >
        <!-- 背景色装饰块 -->
        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full opacity-20 blur-2xl group-hover:scale-150 transition-transform duration-700"
             :class="['bg-blue-400', 'bg-purple-400', 'bg-orange-400', 'bg-teal-400', 'bg-pink-400', 'bg-green-400'][i % 6]">
        </div>
        
        <div class="relative z-10 flex flex-col h-full">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm"
                 :class="['bg-blue-50 text-blue-500', 'bg-purple-50 text-purple-500', 'bg-orange-50 text-orange-500', 'bg-teal-50 text-teal-500', 'bg-pink-50 text-pink-500', 'bg-green-50 text-green-500'][i % 6]">
              <svg v-if="i%6==0" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path></svg>
              <svg v-if="i%6==1" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
              <svg v-if="i%6==2" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
              <!-- 容错渲染默认图标 -->
              <svg v-if="i%6>2" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            
            <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center opacity-0 transform translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>
          
          <h2 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors mb-2 tracking-tight">{{ cat.name }}</h2>
          <p class="text-sm text-gray-500 leading-relaxed mb-6 flex-1 line-clamp-2">{{ cat.description }}</p>
          
          <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400 font-medium tracking-wide">
            <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg> 活跃讨论区</span>
            <span class="bg-gray-50 text-gray-600 px-2.5 py-1 rounded-md">进入探索 →</span>
          </div>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

const { data, pending } = await useFetch(`${apiBase}/categories`)
const categories = computed(() => data.value?.data ?? [])
</script>
