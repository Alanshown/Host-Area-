<template>
  <div class="space-y-8 pb-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">用户治理中心</h1>
        <p class="text-sm text-gray-500 mt-1">统一查看账号增长、角色结构与风险用户，并直接执行封禁管理。</p>
      </div>
      <div class="flex items-center gap-3">
        <NuxtLink to="/admin" class="px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm transition-all">
          返回总览
        </NuxtLink>
        <button type="button" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 shadow-sm transition-all" @click="refreshAll">
          刷新用户池
        </button>
      </div>
    </div>

    <div v-if="message" class="rounded-xl px-4 py-3 text-sm font-medium" :class="messageError ? 'bg-red-50 border border-red-200 text-red-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
      {{ message }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
      <article v-for="card in userOverviewCards" :key="card.key" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:border-gray-300 transition-colors">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ card.title }}</p>
            <h3 class="text-3xl font-black font-mono text-gray-900 tracking-tight">{{ card.value }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400">
            <svg v-if="card.icon === 'users'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1m0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0" /></svg>
            <svg v-else-if="card.icon === 'activity'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            <svg v-else-if="card.icon === 'banned'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M6.343 17.657a8 8 0 1111.314-11.314A8 8 0 016.343 17.657z" /></svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h6m-6 0V9a4 4 0 10-8 0v2m0 0H5a2 2 0 00-2 2v4a2 2 0 002 2h10a2 2 0 002-2v-4a2 2 0 00-2-2h-2" /></svg>
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
        <div class="px-6 py-5 border-b border-gray-100">
          <h2 class="text-lg font-bold text-gray-900 tracking-tight">用户增长与角色结构</h2>
          <p class="text-sm text-gray-500 mt-1">图表与数值全部来自 admin/stats 和 admin/users 的真实返回。</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
          <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-5">
            <div class="mb-4">
              <p class="text-xs uppercase tracking-widest text-cyan-500 font-bold">User Growth</p>
              <h3 class="mt-2 text-xl font-black text-gray-900">新增用户曲线</h3>
              <p class="text-sm text-gray-500 mt-1">过去 14 天账号增长节奏。</p>
            </div>
            <AdminLineChart :labels="stats.chart_labels || []" :series="userSeries" />
          </div>

          <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-5">
            <div class="mb-4">
              <p class="text-xs uppercase tracking-widest text-indigo-500 font-bold">Role Structure</p>
              <h3 class="mt-2 text-xl font-black text-gray-900">角色占比</h3>
              <p class="text-sm text-gray-500 mt-1">普通用户、管理员和封禁账号结构。</p>
            </div>
            <AdminDonutChart :segments="stats.role_distribution || []" center-label="账号数" />
          </div>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">账号健康摘要</h2>
          <div class="space-y-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs text-gray-500">管理员账号</p>
              <p class="mt-1 text-2xl font-black font-mono text-gray-900">{{ adminCount }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs text-gray-500">封禁中账号</p>
              <p class="mt-1 text-2xl font-black font-mono text-rose-600">{{ bannedCount }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs text-gray-500">当前页内容产出</p>
              <p class="mt-1 text-sm font-mono text-gray-700">{{ userPostCount }} 帖 · {{ userCommentCount }} 评 · {{ userReportCount }} 举报</p>
            </div>
          </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">最近加入</h2>
          <div class="space-y-3">
            <article v-for="user in users.slice(0, 5)" :key="`recent-${user.id}`" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-bold text-gray-900">{{ user.username }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ user.email }}</p>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold" :class="statusClass(user)">
                  {{ user.is_banned ? '封禁中' : user.role === 'admin' ? '管理员' : '正常' }}
                </span>
              </div>
            </article>
            <p v-if="!users.length" class="text-sm text-gray-500">暂无用户数据。</p>
          </div>
        </section>
      </aside>
    </div>

    <section class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold text-gray-900 tracking-tight">用户治理工作台</h2>
          <p class="text-sm text-gray-500 mt-1">当前列表来自 users 表分页数据，封禁操作实时作用到数据库。</p>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-xs text-gray-500">当前页 {{ users.length }} 人</span>
      </div>

      <div v-if="pendingUsers" class="px-6 py-16 text-center text-sm text-gray-500">正在同步用户数据...</div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
              <th class="px-6 py-4 font-semibold">用户</th>
              <th class="px-6 py-4 font-semibold">角色 / 状态</th>
              <th class="px-6 py-4 font-semibold">内容产出</th>
              <th class="px-6 py-4 font-semibold">封禁信息</th>
              <th class="px-6 py-4 font-semibold text-right">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/60 transition-colors align-top">
              <td class="px-6 py-5 min-w-[260px]">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-100 to-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 font-bold">
                    {{ user.username?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                  <div>
                    <div class="text-sm font-bold text-gray-900">{{ user.username }}</div>
                    <div class="text-xs text-gray-500 mt-1">#{{ user.id }} · {{ user.email }}</div>
                    <div class="text-xs text-gray-400 mt-1">注册于 {{ formatDate(user.created_at) }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 min-w-[160px]">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold" :class="statusClass(user)">
                  {{ user.is_banned ? '封禁中' : user.role === 'admin' ? '管理员' : '普通用户' }}
                </span>
                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ user.bio || '该用户暂无简介。' }}</p>
              </td>
              <td class="px-6 py-5 text-sm text-gray-600 min-w-[170px]">
                <div class="font-mono">{{ user.posts_count || 0 }} 帖子</div>
                <div class="font-mono mt-1">{{ user.comments_count || 0 }} 评论</div>
                <div class="font-mono mt-1">{{ user.reports_count || 0 }} 举报</div>
              </td>
              <td class="px-6 py-5 text-sm text-gray-600 min-w-[180px]">
                <div>{{ formatBan(user) }}</div>
                <div v-if="user.ban_reason" class="text-xs text-gray-500 mt-2 line-clamp-2">{{ user.ban_reason }}</div>
              </td>
              <td class="px-6 py-5">
                <div class="flex justify-end">
                  <button
                    v-if="user.role !== 'admin'"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                    :class="user.is_banned ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                    @click="toggleBan(user)"
                  >
                    {{ user.is_banned ? '解除封禁' : '封禁账号' }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">当前没有用户数据。</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
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

const { data: usersData, pending: pendingUsers, refresh: refreshUsers } = await useFetch(`${apiBase}/admin/users`, {
  server: false,
  headers,
  default: () => ({ data: [] }),
})

const { data: statsData, refresh: refreshStats } = await useFetch(`${apiBase}/admin/stats`, {
  server: false,
  headers,
  default: () => ({}),
})

const users = computed(() => usersData.value?.data ?? [])
const stats = computed(() => statsData.value ?? {})
const roleDistribution = computed(() => stats.value.role_distribution ?? [])
const metricTrends = computed(() => stats.value.metric_trends ?? {})

const adminCount = computed(() => roleDistribution.value.find(item => item.key === 'admin')?.value ?? 0)
const bannedCount = computed(() => roleDistribution.value.find(item => item.key === 'banned')?.value ?? 0)
const userPostCount = computed(() => users.value.reduce((sum, user) => sum + Number(user.posts_count || 0), 0))
const userCommentCount = computed(() => users.value.reduce((sum, user) => sum + Number(user.comments_count || 0), 0))
const userReportCount = computed(() => users.value.reduce((sum, user) => sum + Number(user.reports_count || 0), 0))

const userSeries = computed(() => ([
  { name: '新增用户', color: '#22d3ee', fill: 'rgba(34,211,238,0.12)', values: stats.value.user_growth_series || [] },
]))

const formatTrendLabel = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return '0.0%'
  return `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}%`
}

const trendTone = (value) => {
  const numeric = Number(value ?? 0)
  if (numeric === 0) return 'neutral'
  return numeric > 0 ? 'up' : 'down'
}

const trendBadgeClass = (tone) => ({
  up: 'bg-emerald-50 text-emerald-700',
  down: 'bg-rose-50 text-rose-700',
  neutral: 'bg-gray-100 text-gray-600',
}[tone] ?? 'bg-gray-100 text-gray-600')

const userOverviewCards = computed(() => [
  {
    key: 'users',
    title: '平台注册用户',
    value: Number(stats.value.users_count ?? 0),
    badge: formatTrendLabel(metricTrends.value.users),
    badgeClass: trendBadgeClass(trendTone(metricTrends.value.users)),
    hint: '较上 7 天新增用户',
    icon: 'users',
  },
  {
    key: 'active',
    title: '7 日活跃成员',
    value: Number(stats.value.active_users_count ?? 0),
    badge: formatTrendLabel(metricTrends.value.active_users),
    badgeClass: trendBadgeClass(trendTone(metricTrends.value.active_users)),
    hint: '较前 7 天活跃变化',
    icon: 'activity',
  },
  {
    key: 'banned',
    title: '封禁中账号',
    value: Number(bannedCount.value),
    badge: `${users.value.filter(user => user.is_banned).length} / 页`,
    badgeClass: 'bg-rose-50 text-rose-700',
    hint: '当前分页封禁密度',
    icon: 'banned',
  },
  {
    key: 'reports',
    title: '当前页风险产出',
    value: Number(userReportCount.value),
    badge: `${userPostCount.value} 帖 / ${userCommentCount.value} 评`,
    badgeClass: 'bg-gray-100 text-gray-700',
    hint: '当前分页内容产出',
    icon: 'shield',
  },
])

const statusClass = (user) => {
  if (user.is_banned) return 'bg-rose-50 text-rose-700'
  if (user.role === 'admin') return 'bg-slate-900 text-white'
  return 'bg-emerald-50 text-emerald-700'
}

const formatBan = (user) => {
  if (!user.is_banned) return '正常'
  if (!user.banned_until) return '长期封禁'
  return `至 ${new Date(user.banned_until).toLocaleString('zh-CN')}`
}

const formatDate = (value) => {
  if (!value) return '未知时间'
  return new Date(value).toLocaleDateString('zh-CN')
}

const refreshAll = async () => {
  await Promise.all([refreshUsers(), refreshStats()])
}

const toggleBan = async (user) => {
  message.value = ''
  messageError.value = false
  try {
    await $fetch(`${apiBase}/admin/users/${user.id}/ban`, {
      method: 'POST',
      headers: headers.value,
    })
    await refreshAll()
    message.value = user.is_banned ? '账号已解除封禁。' : '账号已封禁。'
  } catch (error) {
    messageError.value = true
    message.value = extractApiError(error, '账号状态更新失败')
  }
}
</script>