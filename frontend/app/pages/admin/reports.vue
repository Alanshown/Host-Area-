<template>
  <div class="space-y-8 pb-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">举报治理中心</h1>
        <p class="text-sm text-gray-500 mt-1">集中处理举报流转状态、举报人与被举报内容，保持风控链路清晰可追踪。</p>
      </div>
      <div class="flex items-center gap-3">
        <NuxtLink to="/admin" class="px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm transition-all">
          返回总览
        </NuxtLink>
        <button type="button" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 shadow-sm transition-all" @click="refreshAll">
          刷新举报池
        </button>
      </div>
    </div>

    <div v-if="message" class="rounded-xl px-4 py-3 text-sm font-medium" :class="messageError ? 'bg-red-50 border border-red-200 text-red-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
      {{ message }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
      <article v-for="card in reportOverviewCards" :key="card.key" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:border-gray-300 transition-colors">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ card.title }}</p>
            <h3 class="text-3xl font-black font-mono text-gray-900 tracking-tight">{{ card.value }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400">
            <svg v-if="card.icon === 'reports'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <svg v-else-if="card.icon === 'pending'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <svg v-else-if="card.icon === 'resolved'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
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
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">举报处置工作台</h2>
            <p class="text-sm text-gray-500 mt-1">所有举报状态变更会即时写回数据库，并同步刷新统计卡片。</p>
          </div>
          <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-xs text-gray-500">当前页 {{ reports.length }} 条</span>
        </div>

        <div v-if="pendingReports" class="px-6 py-16 text-center text-sm text-gray-500">正在同步举报数据...</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                <th class="px-6 py-4 font-semibold">举报内容</th>
                <th class="px-6 py-4 font-semibold">举报人 / 被举报作者</th>
                <th class="px-6 py-4 font-semibold">原因</th>
                <th class="px-6 py-4 font-semibold">状态</th>
                <th class="px-6 py-4 font-semibold text-right">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50/60 transition-colors align-top">
                <td class="px-6 py-5 min-w-[280px]">
                  <div class="space-y-2">
                    <div class="text-xs text-gray-400 font-mono">#{{ report.id }} · {{ formatDate(report.updated_at || report.created_at) }}</div>
                    <p class="text-sm font-bold text-gray-900 line-clamp-2">{{ report.post?.title || '帖子已删除' }}</p>
                  </div>
                </td>
                <td class="px-6 py-5 min-w-[180px] text-sm text-gray-600">
                  <div class="font-semibold text-gray-900">{{ report.user?.username || '未知用户' }}</div>
                  <div class="mt-1 text-xs text-gray-500">被举报作者：{{ report.post?.user?.username || '未知作者' }}</div>
                </td>
                <td class="px-6 py-5 min-w-[220px] text-sm text-gray-600">
                  <p class="line-clamp-3">{{ report.reason || '未填写原因' }}</p>
                </td>
                <td class="px-6 py-5 min-w-[130px]">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold" :class="pillClass(report.status)">{{ statusLabel(report.status) }}</span>
                </td>
                <td class="px-6 py-5">
                  <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors" @click="quickUpdate(report.id, 'in_review')">审核中</button>
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition-colors" @click="quickUpdate(report.id, 'resolved')">已解决</button>
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold hover:bg-rose-100 transition-colors" @click="quickUpdate(report.id, 'rejected')">驳回</button>
                    <NuxtLink :to="`/admin/reports/${report.id}`" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold hover:bg-gray-200 transition-colors">详情</NuxtLink>
                  </div>
                </td>
              </tr>
              <tr v-if="!reports.length">
                <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">当前没有举报数据。</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">快速队列</h2>
          <div class="space-y-3">
            <article v-for="item in reports.slice(0, 5)" :key="`queue-${item.id}`" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-bold text-gray-900 line-clamp-2">{{ item.post?.title || '帖子已删除' }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ item.user?.username || '未知用户' }}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap" :class="pillClass(item.status)">{{ statusLabel(item.status) }}</span>
              </div>
            </article>
            <p v-if="!reports.length" class="text-sm text-gray-500">当前队列为空。</p>
          </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">治理说明</h2>
          <ul class="space-y-3 text-sm text-gray-600">
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-amber-400"></span><span>“审核中”用于管理员已接手，但还未完成处置判断的举报。</span></li>
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-emerald-400"></span><span>“已解决”会保留处理痕迹，适合已完成删除、警告或限制的案例。</span></li>
            <li class="flex items-start gap-2"><span class="mt-1 w-2 h-2 rounded-full bg-rose-400"></span><span>“已驳回”适合恶意举报或证据不足的情况。</span></li>
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

const { data: reportsData, pending: pendingReports, refresh: refreshReports } = await useFetch(`${apiBase}/admin/reports`, {
  server: false,
  headers,
  default: () => ({ data: [] }),
})

const { data: statsData, refresh: refreshStats } = await useFetch(`${apiBase}/admin/stats`, {
  server: false,
  headers,
  default: () => ({}),
})

const reports = computed(() => reportsData.value?.data ?? [])
const stats = computed(() => statsData.value ?? {})
const moderationReports = computed(() => stats.value.moderation_breakdown?.reports ?? [])
const pendingCount = computed(() => moderationReports.value.filter((item) => ['pending', 'in_review'].includes(item.key)).reduce((sum, item) => sum + Number(item.value || 0), 0))
const resolvedCount = computed(() => moderationReports.value.find((item) => item.key === 'resolved')?.value ?? 0)
const rejectedCount = computed(() => moderationReports.value.find((item) => item.key === 'rejected')?.value ?? 0)
const metricTrends = computed(() => stats.value.metric_trends ?? {})

const formatTrendLabel = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return '0.0%'
  return `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}%`
}

const reportOverviewCards = computed(() => [
  {
    key: 'reports',
    title: '待处理风险队列',
    value: Number(stats.value.reports_count ?? 0),
    badge: formatTrendLabel(metricTrends.value.reports),
    badgeClass: Number(metricTrends.value.reports ?? 0) > 0 ? 'bg-orange-50 text-orange-700' : 'bg-emerald-50 text-emerald-700',
    hint: '较上 7 天新增举报',
    icon: 'reports',
  },
  {
    key: 'pending',
    title: '待处理 / 审核中',
    value: Number(pendingCount.value),
    badge: `${reports.value.filter((item) => ['pending', 'in_review'].includes(item.status)).length} / 页`,
    badgeClass: 'bg-amber-50 text-amber-700',
    hint: '当前分页待办密度',
    icon: 'pending',
  },
  {
    key: 'resolved',
    title: '已解决举报',
    value: Number(resolvedCount.value),
    badge: `${reports.value.filter((item) => item.status === 'resolved').length} / 页`,
    badgeClass: 'bg-emerald-50 text-emerald-700',
    hint: '全站已完成处置',
    icon: 'resolved',
  },
  {
    key: 'rejected',
    title: '已驳回举报',
    value: Number(rejectedCount.value),
    badge: `${reports.value.filter((item) => item.status === 'rejected').length} / 页`,
    badgeClass: 'bg-rose-50 text-rose-700',
    hint: '全站驳回记录',
    icon: 'rejected',
  },
])

const statusLabel = (status) => ({
  pending: '待处理',
  in_review: '审核中',
  resolved: '已解决',
  rejected: '已驳回',
}[status] || '未知状态')

const pillClass = (status) => ({
  pending: 'bg-amber-50 text-amber-700',
  in_review: 'bg-blue-50 text-blue-700',
  resolved: 'bg-emerald-50 text-emerald-700',
  rejected: 'bg-rose-50 text-rose-700',
}[status] || 'bg-gray-100 text-gray-600')

const formatDate = (value) => {
  if (!value) return '未知时间'
  return new Date(value).toLocaleString('zh-CN')
}

const refreshAll = async () => {
  await Promise.all([refreshReports(), refreshStats()])
}

const quickUpdate = async (id, status) => {
  message.value = ''
  messageError.value = false

  try {
    await $fetch(`${apiBase}/admin/reports/${id}`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: {
        status,
        admin_note: status === 'resolved'
          ? '管理员已在列表页快速处理完成。'
          : status === 'rejected'
            ? '管理员已驳回本次举报。'
            : '管理员已接手进入审核流程。',
      },
    })
    await refreshAll()
    message.value = '举报状态已更新。'
  } catch (error) {
    messageError.value = true
    message.value = extractApiError(error, '举报状态更新失败')
  }
}
</script>