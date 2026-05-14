<template>
  <div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900">我的举报记录</h1>
        <p class="mt-1 text-sm text-gray-500">普通用户也可以直接看到自己提交的举报是否已进入处理、已解决或被驳回。</p>
      </div>
      <NuxtLink v-if="auth.user.value?.id" :to="`/user/${auth.user.value.id}`" class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50">返回我的主页</NuxtLink>
    </div>

    <div v-if="!auth.isLoggedIn.value" class="rounded-2xl border border-dashed border-gray-200 bg-white/70 px-6 py-16 text-center text-gray-400">
      <p class="mb-3">请先登录后查看举报记录</p>
      <NuxtLink to="/login" class="text-blue-600 hover:underline">前往登录</NuxtLink>
    </div>

    <div v-else-if="pending" class="space-y-4">
      <div v-for="i in 4" :key="i" class="rounded-2xl border border-gray-200 bg-white/70 p-5 animate-pulse">
        <div class="h-5 w-1/3 rounded bg-gray-200"></div>
        <div class="mt-4 h-3 w-full rounded bg-gray-100"></div>
        <div class="mt-2 h-3 w-2/3 rounded bg-gray-100"></div>
      </div>
    </div>

    <div v-else-if="reports.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-white/70 px-6 py-16 text-center text-sm text-gray-400">
      你还没有提交过任何举报
    </div>

    <div v-else class="space-y-4">
      <article v-for="report in reports" :key="report.id" class="rounded-[24px] border border-gray-200/80 bg-white/75 p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(report.status)">{{ statusLabel(report.status) }}</span>
              <span class="text-xs text-gray-400">举报 #{{ report.id }}</span>
            </div>
            <h2 class="mt-3 text-base font-semibold text-gray-900">{{ report.post?.title || '帖子已删除' }}</h2>
            <p class="mt-3 text-sm leading-7 text-gray-600">{{ report.reason }}</p>
          </div>
          <div class="text-right text-xs text-gray-500">
            <p>被举报作者：{{ report.post?.user?.username || '未知作者' }}</p>
            <p class="mt-1">提交时间：{{ timeAgo(report.created_at) }}</p>
          </div>
        </div>

        <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 md:grid-cols-[1fr,auto] md:items-end">
          <div>
            <p class="text-xs uppercase tracking-[0.22em] text-gray-400">处理备注</p>
            <p class="mt-2 rounded-2xl bg-gray-50 px-4 py-3 text-sm leading-7 text-gray-600">{{ report.admin_note || '管理员尚未填写处理备注。' }}</p>
          </div>
          <div class="text-xs text-gray-500">
            <p>处理人：{{ report.reviewer?.username || '待分配' }}</p>
            <p class="mt-1">处理时间：{{ report.reviewed_at ? timeAgo(report.reviewed_at) : '尚未处理' }}</p>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { timeAgo } from '~/composables/useApi.js'

const auth = useAuth()
const { apiBase, getAuthHeaders } = useApi()

if (process.client) {
  auth.initAuth()
}

const { data, pending } = await useFetch(
  () => auth.isLoggedIn.value ? `${apiBase}/user/reports` : null,
  {
    server: false,
    headers: computed(() => auth.isLoggedIn.value ? getAuthHeaders() : {}),
    default: () => ({ data: [] }),
  }
)

const reports = computed(() => data.value?.data ?? [])

const statusLabel = (status) => ({
  pending: '待处理',
  in_review: '审核中',
  resolved: '已解决',
  rejected: '已驳回',
}[status] || '未知状态')

const statusClass = (status) => ({
  pending: 'bg-amber-100 text-amber-700',
  in_review: 'bg-sky-100 text-sky-700',
  resolved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-rose-100 text-rose-700',
}[status] || 'bg-gray-100 text-gray-600')
</script>
