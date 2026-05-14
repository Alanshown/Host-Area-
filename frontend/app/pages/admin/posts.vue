<template>
  <div class="space-y-8 pb-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">帖子治理中心</h1>
        <p class="text-sm text-gray-500 mt-1">基于真实帖子数据执行审核、拦截与删除操作，维持内容流质量。</p>
      </div>
      <div class="flex items-center gap-3">
        <NuxtLink to="/admin" class="px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm transition-all">
          返回总览
        </NuxtLink>
        <button type="button" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 shadow-sm transition-all" @click="refreshAll">
          刷新帖子池
        </button>
      </div>
    </div>

    <div v-if="message" class="rounded-xl px-4 py-3 text-sm font-medium" :class="messageError ? 'bg-red-50 border border-red-200 text-red-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
      {{ message }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
      <article v-for="card in postOverviewCards" :key="card.key" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:border-gray-300 transition-colors">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ card.title }}</p>
            <h3 class="text-3xl font-black font-mono text-gray-900 tracking-tight">{{ card.value }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400">
            <svg v-if="card.icon === 'posts'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            <svg v-else-if="card.icon === 'pending'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <svg v-else-if="card.icon === 'approved'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
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
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">帖子审核工作台</h2>
            <p class="text-sm text-gray-500 mt-1">当前列表来自真实帖子分页数据，所有操作实时写入数据库。</p>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <span class="px-2.5 py-1 rounded-lg bg-gray-100">当前页 {{ posts.length }} 条</span>
            <span class="px-2.5 py-1 rounded-lg bg-gray-100">累计评论 {{ pageCommentCount }}</span>
          </div>
        </div>

        <div v-if="pendingPosts" class="px-6 py-16 text-center text-sm text-gray-500">正在同步帖子数据...</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                <th class="px-6 py-4 font-semibold">帖子</th>
                <th class="px-6 py-4 font-semibold">作者 / 分类</th>
                <th class="px-6 py-4 font-semibold">互动</th>
                <th class="px-6 py-4 font-semibold">状态</th>
                <th class="px-6 py-4 font-semibold text-right">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in posts" :key="item.id" class="hover:bg-gray-50/60 transition-colors align-top">
                <td class="px-6 py-5 min-w-[320px]">
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs text-gray-400 font-mono">
                      <span>#{{ item.id }}</span>
                      <span>{{ formatDate(item.created_at) }}</span>
                    </div>
                    <NuxtLink :to="`/post/${item.id}`" class="block text-sm font-bold text-gray-900 hover:text-blue-600 transition-colors line-clamp-2">
                      {{ item.title }}
                    </NuxtLink>
                    <p class="text-xs text-gray-500 line-clamp-2">{{ item.content || '该帖子暂无正文摘要。' }}</p>
                  </div>
                </td>
                <td class="px-6 py-5 text-sm text-gray-600 min-w-[180px]">
                  <div class="font-semibold text-gray-900">{{ item.user?.username || '未知作者' }}</div>
                  <div class="mt-1 text-xs text-gray-500">{{ item.category?.name || '未分类' }}</div>
                </td>
                <td class="px-6 py-5 text-sm text-gray-600 min-w-[150px]">
                  <div class="font-mono">{{ item.comments_count || 0 }} 评论</div>
                  <div class="font-mono mt-1">{{ item.views || 0 }} 浏览 · {{ item.likes || 0 }} 赞</div>
                </td>
                <td class="px-6 py-5 min-w-[130px]">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold" :class="pillClass(item.moderation_status)">
                    {{ statusLabel(item.moderation_status) }}
                  </span>
                </td>
                <td class="px-6 py-5">
                  <div class="flex flex-wrap justify-end gap-2">
                    <button v-if="item.moderation_status !== 'approved'" type="button" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition-colors" @click="moderatePost(item.id, 'approved')">通过</button>
                    <button v-if="item.moderation_status !== 'rejected'" type="button" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors" @click="moderatePost(item.id, 'rejected')">驳回</button>
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold hover:bg-rose-100 transition-colors" @click="deletePost(item.id)">删除</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!posts.length">
                <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">当前没有可处理的帖子数据。</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">实时审核队列</h2>
          <div class="space-y-3">
            <article v-for="item in posts.slice(0, 5)" :key="`queue-${item.id}`" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-bold text-gray-900 line-clamp-2">{{ item.title }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ item.user?.username || '未知作者' }} · {{ item.category?.name || '未分类' }}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap" :class="pillClass(item.moderation_status)">
                  {{ statusLabel(item.moderation_status) }}
                </span>
              </div>
            </article>
            <p v-if="!posts.length" class="text-sm text-gray-500">当前队列为空。</p>
          </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">审核说明</h2>
          <ul class="space-y-3 text-sm text-gray-600">
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-amber-400"></span><span>待审核数据来自 posts 表，状态修改会直接更新 moderation_status。</span></li>
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-emerald-400"></span><span>点击通过或驳回后会即时刷新列表与顶部卡片。</span></li>
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-rose-400"></span><span>删除为数据库硬删除，建议仅用于违规内容或脏数据清理。</span></li>
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
const messageError = ref(false)
const headers = computed(() => getAuthHeaders())

const { data: postsData, pending: pendingPosts, refresh: refreshPosts } = await useFetch(`${apiBase}/admin/posts`, {
  server: false,
  headers,
  default: () => ({ data: [] }),
})

const { data: statsData, refresh: refreshStats } = await useFetch(`${apiBase}/admin/stats`, {
  server: false,
  headers,
  default: () => ({}),
})

const posts = computed(() => postsData.value?.data ?? [])
const stats = computed(() => statsData.value ?? {})
const moderationPosts = computed(() => stats.value.moderation_breakdown?.posts ?? [])
const metricTrends = computed(() => stats.value.metric_trends ?? {})

const approvedTotal = computed(() => moderationPosts.value.find(item => item.key === 'approved')?.value ?? 0)
const rejectedTotal = computed(() => moderationPosts.value.find(item => item.key === 'rejected')?.value ?? 0)
const pageCommentCount = computed(() => posts.value.reduce((sum, item) => sum + Number(item.comments_count || 0), 0))

const formatTrendLabel = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return '0.0%'
  return `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}%`
}

const trendBadgeClass = (tone) => ({
  up: 'bg-emerald-50 text-emerald-700',
  down: 'bg-rose-50 text-rose-700',
  warning: 'bg-orange-50 text-orange-700',
  neutral: 'bg-gray-100 text-gray-600',
}[tone] ?? 'bg-gray-100 text-gray-600')

const trendTone = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return 'neutral'
  return numeric > 0 ? 'up' : 'down'
}

const postOverviewCards = computed(() => [
  {
    key: 'total',
    title: '全站帖子总量',
    value: Number(stats.value.posts_count ?? 0),
    badge: formatTrendLabel(metricTrends.value.posts),
    badgeClass: trendBadgeClass(trendTone(metricTrends.value.posts)),
    hint: '较上 7 天新增帖子',
    icon: 'posts',
  },
  {
    key: 'pending',
    title: '待审核帖子',
    value: Number(stats.value.pending_posts_count ?? 0),
    badge: `${posts.value.filter(item => item.moderation_status === 'pending').length} / 页`,
    badgeClass: 'bg-amber-50 text-amber-700',
    hint: '当前分页待处理密度',
    icon: 'pending',
  },
  {
    key: 'approved',
    title: '已通过内容',
    value: Number(approvedTotal.value),
    badge: `${posts.value.filter(item => item.moderation_status === 'approved').length} / 页`,
    badgeClass: 'bg-emerald-50 text-emerald-700',
    hint: '当前全站已通过帖子',
    icon: 'approved',
  },
  {
    key: 'rejected',
    title: '已驳回内容',
    value: Number(rejectedTotal.value),
    badge: `${posts.value.filter(item => item.moderation_status === 'rejected').length} / 页`,
    badgeClass: 'bg-rose-50 text-rose-700',
    hint: '当前全站驳回总数',
    icon: 'risk',
  },
])

const statusLabel = (status) => ({
  pending: '待审核',
  approved: '已通过',
  rejected: '已驳回',
}[status] || '未知')

const pillClass = (status) => ({
  pending: 'bg-amber-50 text-amber-700',
  approved: 'bg-emerald-50 text-emerald-700',
  rejected: 'bg-rose-50 text-rose-700',
}[status] || 'bg-gray-100 text-gray-600')

const formatDate = (value) => {
  if (!value) return '未知时间'
  return new Date(value).toLocaleString('zh-CN')
}

const refreshAll = async () => {
  await Promise.all([refreshPosts(), refreshStats()])
}

const moderatePost = async (id, status) => {
  message.value = ''
  messageError.value = false
  try {
    await $fetch(`${apiBase}/admin/posts/${id}/moderate`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: { status, moderation_note: '' },
    })
    await refreshAll()
    message.value = `帖子已${statusLabel(status)}。`
  } catch (error) {
    messageError.value = true
    message.value = extractApiError(error, '状态更新失败')
  }
}

const deletePost = async (id) => {
  if (!confirm('此操作将永久删除该帖子，确定继续吗？')) return

  message.value = ''
  messageError.value = false
  try {
    await $fetch(`${apiBase}/admin/posts/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    })
    await refreshAll()
    message.value = '帖子已被删除。'
  } catch (error) {
    messageError.value = true
    message.value = extractApiError(error, '删除失败')
  }
}
</script>
