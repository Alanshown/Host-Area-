<template>
  <div class="space-y-8 pb-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">评论治理中心</h1>
        <p class="text-sm text-gray-500 mt-1">集中清理评论噪音，结合作者和帖子上下文，快速完成内容处置。</p>
      </div>
      <div class="flex items-center gap-3">
        <NuxtLink to="/admin" class="px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm transition-all">
          返回总览
        </NuxtLink>
        <button type="button" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 shadow-sm transition-all" @click="refreshAll">
          刷新评论池
        </button>
      </div>
    </div>

    <div v-if="message" class="rounded-xl px-4 py-3 text-sm font-medium" :class="deleteError ? 'bg-red-50 border border-red-200 text-red-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
      {{ message }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
      <article v-for="card in commentOverviewCards" :key="card.key" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:border-gray-300 transition-colors">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ card.title }}</p>
            <h3 class="text-3xl font-black font-mono text-gray-900 tracking-tight">{{ card.value }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400">
            <svg v-if="card.icon === 'comments'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z" /></svg>
            <svg v-else-if="card.icon === 'authors'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <svg v-else-if="card.icon === 'posts'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
          <span class="px-2 py-0.5 rounded text-xs font-bold font-mono" :class="card.badgeClass">{{ card.badge }}</span>
          <span class="text-xs text-gray-400">{{ card.hint }}</span>
        </div>
      </article>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <section class="xl:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-4">
          <div>
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">评论清理工作台</h2>
            <p class="text-sm text-gray-500 mt-1">直接查看评论内容、作者和原帖上下文，所有删除动作立即落库。</p>
          </div>
          <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-xs text-gray-500">当前页 {{ comments.length }} 条</span>
        </div>

        <div v-if="pendingComments" class="px-6 py-16 text-center text-sm text-gray-500">正在同步评论数据...</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                <th class="px-6 py-4 font-semibold">评论内容</th>
                <th class="px-6 py-4 font-semibold">作者</th>
                <th class="px-6 py-4 font-semibold">所属帖子</th>
                <th class="px-6 py-4 font-semibold">时间</th>
                <th class="px-6 py-4 font-semibold text-right">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in comments" :key="item.id" class="hover:bg-gray-50/60 transition-colors align-top">
                <td class="px-6 py-5 min-w-[320px]">
                  <div class="space-y-2">
                    <div class="text-xs text-gray-400 font-mono">#{{ item.id }}</div>
                    <p class="text-sm font-semibold text-gray-900 line-clamp-3">{{ item.content }}</p>
                  </div>
                </td>
                <td class="px-6 py-5 min-w-[150px] text-sm text-gray-600">
                  <div class="font-semibold text-gray-900">{{ item.user?.username || '未知用户' }}</div>
                </td>
                <td class="px-6 py-5 min-w-[220px] text-sm">
                  <NuxtLink :to="`/post/${item.post?.id}`" class="font-semibold text-blue-600 hover:text-blue-700 transition-colors line-clamp-2">
                    {{ item.post?.title || '帖子不可用' }}
                  </NuxtLink>
                </td>
                <td class="px-6 py-5 min-w-[160px] text-sm text-gray-500 font-mono">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="px-6 py-5">
                  <div class="flex justify-end gap-2">
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold hover:bg-rose-100 transition-colors" @click="deleteComment(item.id)">
                      删除评论
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!comments.length">
                <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">当前没有评论数据。</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">最近评论作者</h2>
          <div class="space-y-3">
            <article v-for="item in comments.slice(0, 5)" :key="`author-${item.id}`" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <p class="text-sm font-bold text-gray-900">{{ item.user?.username || '未知用户' }}</p>
              <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ item.content }}</p>
            </article>
            <p v-if="!comments.length" class="text-sm text-gray-500">暂无作者记录。</p>
          </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">治理提示</h2>
          <ul class="space-y-3 text-sm text-gray-600">
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-rose-400"></span><span>删除评论为立即生效动作，适合清理辱骂、广告和刷屏内容。</span></li>
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-blue-400"></span><span>删除前可通过原帖链接回到上下文判断是否为连带争议。</span></li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

definePageMeta({
  layout: 'admin',
})

const { apiBase, getAuthHeaders } = useApi()

const message = ref('')
const deleteError = ref(false)
const headers = computed(() => getAuthHeaders())

const { data: commentsData, pending: pendingComments, refresh: refreshComments } = await useFetch(`${apiBase}/admin/comments`, {
  server: false,
  headers,
  default: () => ({ data: [] }),
})

const { data: statsData, refresh: refreshStats } = await useFetch(`${apiBase}/admin/stats`, {
  server: false,
  headers,
  default: () => ({}),
})

const comments = computed(() => commentsData.value?.data ?? [])
const stats = computed(() => statsData.value ?? {})
const uniqueAuthors = computed(() => new Set(comments.value.map((item) => item.user?.username).filter(Boolean)).size)
const linkedPosts = computed(() => new Set(comments.value.map((item) => item.post?.id).filter(Boolean)).size)
const metricTrends = computed(() => stats.value.metric_trends ?? {})

const formatTrendLabel = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return '0.0%'
  return `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}%`
}

const commentOverviewCards = computed(() => [
  {
    key: 'comments',
    title: '全站评论总量',
    value: Number(stats.value.comments_count ?? 0),
    badge: formatTrendLabel(stats.value.content_activity_series?.comments?.slice(-1)?.[0] ?? 0),
    badgeClass: 'bg-cyan-50 text-cyan-700',
    hint: '最近 1 天评论增量',
    icon: 'comments',
  },
  {
    key: 'page-comments',
    title: '当前页评论数',
    value: comments.value.length,
    badge: `${uniqueAuthors.value} 作者`,
    badgeClass: 'bg-gray-100 text-gray-700',
    hint: '当前分页覆盖成员',
    icon: 'authors',
  },
  {
    key: 'linked-posts',
    title: '关联帖子数',
    value: linkedPosts.value,
    badge: `${comments.value.filter(item => item.post?.id).length} 条关联`,
    badgeClass: 'bg-blue-50 text-blue-700',
    hint: '当前分页上下文完整度',
    icon: 'posts',
  },
  {
    key: 'active-users',
    title: '近 7 日活跃成员',
    value: Number(stats.value.active_users_count ?? 0),
    badge: formatTrendLabel(metricTrends.value.active_users),
    badgeClass: Number(metricTrends.value.active_users ?? 0) >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700',
    hint: '较前 7 天活跃变化',
    icon: 'ready',
  },
])

const formatDate = (value) => {
  if (!value) return '未知时间'
  return new Date(value).toLocaleString('zh-CN')
}

const refreshAll = async () => {
  await Promise.all([refreshComments(), refreshStats()])
}

const deleteComment = async (id) => {
  if (!confirm('确定删除这条评论？')) return

  message.value = ''
  deleteError.value = false

  try {
    await $fetch(`${apiBase}/admin/comments/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    })
    await refreshAll()
    message.value = '评论已删除。'
  } catch (error) {
    deleteError.value = true
    message.value = extractApiError(error, '删除失败')
  }
}
</script>
