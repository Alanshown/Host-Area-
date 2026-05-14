<template>
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- 左侧 -->
    <div class="hidden lg:block col-span-1">
      <div class="sticky top-24 bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 mb-3">热门说明</h3>
        <p class="text-xs text-gray-500 leading-relaxed">根据过去 24 小时的点赞量、评论数和浏览量综合排名，每小时更新一次。</p>
      </div>
    </div>

    <!-- 中间 -->
    <div class="col-span-1 lg:col-span-2">
      <div class="flex items-center justify-between mb-5 px-1">
        <h1 class="text-xl font-bold text-gray-900 flex items-center">
          <svg class="w-5 h-5 text-orange-500 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/></svg>
          热门趋势
        </h1>
      </div>

      <div v-if="pending" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white border border-gray-200 rounded-xl p-5 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
          <div class="h-3 bg-gray-100 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else class="space-y-4">
        <NuxtLink v-for="(post, i) in posts" :key="post.id" :to="`/post/${post.id}`" class="block">
          <div 
            class="bg-white border rounded-xl p-5 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            :class="[
              i === 0 ? 'border-yellow-400 bg-gradient-to-r from-yellow-50/50 to-white' : 
              i === 1 ? 'border-gray-300 bg-gradient-to-r from-gray-50/50 to-white' : 
              i === 2 ? 'border-orange-300 bg-gradient-to-r from-orange-50/50 to-white' : 
              'border-gray-200 hover:border-orange-400'
            ]"
          >
            <div class="flex items-start gap-4">
              <!-- 排行标识 (前三名特殊处理) -->
              <div class="flex flex-col items-center justify-center min-w-[2.5rem]">
                <span v-if="i === 0" class="text-3xl drop-shadow-md">👑</span>
                <span v-else-if="i === 1" class="text-3xl drop-shadow-md">🥈</span>
                <span v-else-if="i === 2" class="text-3xl drop-shadow-md">🥉</span>
                <span v-else class="text-2xl font-black text-gray-300 italic">{{ i + 1 }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xs px-2.5 py-0.5 bg-blue-50 text-blue-600 rounded-full font-medium">{{ post.category?.name }}</span>
                  <span v-if="i < 3" class="text-[10px] px-2 py-0.5 bg-red-100 text-red-600 border border-red-200 rounded-md font-bold flex items-center gap-1">
                    <span class="animate-pulse">🔥</span> 爆火
                  </span>
                  <span v-else-if="i < 8" class="text-[10px] px-2 py-0.5 bg-orange-50 text-orange-500 border border-orange-200 rounded-md font-bold">
                    🚀 飙升
                  </span>
                </div>
                
                <h2 class="text-lg font-bold text-gray-900 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-orange-500 group-hover:to-pink-500 transition-all line-clamp-2">
                  {{ post.title }}
                </h2>
                
                <div class="flex items-center text-xs text-gray-500 mt-3 space-x-4">
                  <span class="flex items-center gap-1">
                    <img v-if="post.user?.avatar" :src="post.user.avatar" class="w-4 h-4 rounded-full border border-gray-200" />
                    <span v-else class="w-4 h-4 rounded-full bg-gray-200 border border-gray-300"></span>
                    {{ post.user?.username }}
                  </span>
                  <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" /></svg>{{ post.likes }}</span>
                  <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>{{ post.comments_count }}</span>
                  <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>{{ post.views }}</span>
                </div>
                
                <!-- 热度条 -->
                <div class="mt-3 flex items-center gap-2">
                  <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div 
                      class="h-full rounded-full transition-all duration-1000"
                      :class="[
                        i === 0 ? 'bg-gradient-to-r from-red-500 to-yellow-400 w-full' :
                        i === 1 ? 'bg-gradient-to-r from-orange-400 to-yellow-300 w-[90%]' :
                        i === 2 ? 'bg-gradient-to-r from-orange-300 to-yellow-200 w-[80%]' :
                        'bg-orange-200'
                      ]"
                      :style="i > 2 ? `width: ${Math.max(10, 70 - (i - 2) * 5)}%` : ''"
                    ></div>
                  </div>
                  <span class="text-[10px] text-orange-500 font-medium whitespace-nowrap">
                    {{ Math.max(1000, 9999 - (i * 450) + post.likes * 10 + post.views) }} 热度
                  </span>
                </div>
              </div>
            </div>
          </div>
        </NuxtLink>
      </div>
    </div>

    <!-- 右侧 -->
    <div class="hidden lg:block col-span-1">
      <div class="sticky top-24 bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <h3 class="font-bold text-gray-900 mb-3 text-sm">快速导航</h3>
        <ul class="space-y-2 text-sm">
          <li><NuxtLink to="/" class="text-gray-600 hover:text-blue-600 transition-colors">← 返回首页</NuxtLink></li>
          <li><NuxtLink to="/category" class="text-gray-600 hover:text-blue-600 transition-colors">全部分类</NuxtLink></li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

const { data, pending } = await useFetch(`${apiBase}/posts?sort=hot&per_page=20`)
const posts = computed(() => data.value?.data ?? [])
</script>
