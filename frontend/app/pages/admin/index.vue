<template>
  <div class="space-y-8 pb-12">
    <!-- 顶部动作与标题 -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
          数据总览 
          <span v-if="pending" class="w-5 h-5 rounded-full border-2 border-gray-300 border-t-gray-900 animate-spin"></span>
        </h1>
        <p class="text-sm text-gray-500 mt-1">分析平台用户增长、活跃度与内容风控指标，实时刷新。</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-gray-200">
          导出本月报告
        </button>
        <button @click="refreshAnnouncements" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
          刷新实时仪表盘
        </button>
      </div>
    </div>

    <!-- 异常提示 -->
    <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 rounded-xl p-4 text-sm font-medium flex items-center gap-2">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ error }}
    </div>

    <!-- 顶级业务指标卡片 (Metric Cards) -->
    <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="i in 4" :key="i" class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm animate-pulse">
        <div class="h-4 bg-gray-100 rounded w-1/3 mb-4"></div>
        <div class="h-8 bg-gray-200 rounded w-1/2 mb-2"></div>
        <div class="h-3 bg-gray-50 rounded w-2/3"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <article
        v-for="card in overviewCards"
        :key="card.key"
        class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm relative overflow-hidden group hover:border-gray-300 transition-colors"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ card.title }}</p>
            <h3 class="text-3xl font-black font-mono text-gray-900 tracking-tight">{{ card.value }}</h3>
          </div>

          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
            <svg v-if="card.icon === 'posts'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            <svg v-else-if="card.icon === 'users'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1m0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0" /></svg>
            <svg v-else-if="card.icon === 'activity'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
          <span
            class="px-2 py-0.5 rounded text-xs font-bold font-mono"
            :class="trendBadgeClass(card.trendTone)"
          >
            {{ card.trendLabel }}
          </span>
          <span class="text-xs text-gray-400">{{ card.trendHint }}</span>
        </div>
      </article>
    </div>

    <!-- 中部内容与用户分析区域 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 用户生命周期 -->
      <div class="lg:col-span-2 bg-white border border-gray-200 shadow-sm rounded-2xl overflow-hidden flex flex-col">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">分类热区流转 (Category Heatmap)</h2>
            <p class="text-sm text-gray-500 mt-1">基于帖子下钻和用户留存算法的类目价值分析</p>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
        </div>
        <div class="p-0 flex-1 overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                <th class="px-6 py-4 font-semibold">类目名称</th>
                <th class="px-6 py-4 font-semibold">基准引流量 (帖)</th>
                <th class="px-6 py-4 font-semibold">曝光总量 (Views)</th>
                <th class="px-6 py-4 font-semibold">深度互动 (Likes)</th>
                <th class="px-6 py-4 font-semibold text-right">热力占比</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in categoryHeat" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-4 font-medium text-sm text-gray-900">{{ item.name }}</td>
                <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ item.posts_count || 0 }}</td>
                <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ item.views_total || 0 }}</td>
                <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ item.likes_total || 0 }}</td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2 text-xs font-medium">
                    <span class="text-blue-600">{{ heatWidth(item).toFixed(1) }}%</span>
                    <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div class="h-full bg-blue-500 rounded-full" :style="`width: ${heatWidth(item)}%`"></div>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="!categoryHeat.length">
                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">无活跃分类数据</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 右侧杂项分析 -->
      <div class="space-y-6 flex flex-col h-full">
        <!-- 角色结构分布 -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4">身份指纹 (Identity Mix)</h2>
          <div class="space-y-4">
            <div v-for="segment in (stats.role_distribution || [])" :key="segment.label">
              <div class="flex justify-between text-sm mb-1.5">
                <span class="font-medium text-gray-900">{{ segment.label }}</span>
                <span class="font-mono text-gray-500">{{ segment.value }}</span>
              </div>
              <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: `${stats.users_count ? (segment.value / stats.users_count) * 100 : 0}%` }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- 举报大盘 -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6 flex-1">
          <h2 class="text-sm uppercase tracking-widest text-gray-400 font-bold mb-4 flex items-center justify-between">
            <span>治理大盘 (Moderation)</span>
            <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="segment in (stats.moderation_breakdown?.reports || [])" :key="segment.label" class="p-4 rounded-xl bg-gray-50 border border-gray-100">
              <span class="block text-xs text-gray-500 mb-1">{{ segment.label }}</span>
              <span class="block text-2xl font-black font-mono" :class="segment.label.includes('未处理') ? 'text-red-500' : 'text-gray-900'">{{ segment.value }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部功能：近期操作流与高频贡献者 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- 高频贡献者列表 -->
      <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6">
         <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">高频池贡献者 (Top Ops)</h2>
            <p class="text-sm text-gray-500">按动作熵值排名的站内红人</p>
          </div>
        </div>
        <ul class="space-y-4">
          <li v-for="user in (stats.top_contributors || [])" :key="user.id" class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition-colors group">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-100 to-blue-50 border border-blue-100 flex items-center justify-center font-bold text-blue-600">
                {{ user.username.charAt(0).toUpperCase() }}
              </div>
              <div>
                <strong class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  {{ user.username }}
                  <span v-if="user.role === 'admin'" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-900 text-white">OP</span>
                </strong>
                <span class="text-xs text-gray-500">{{ user.posts_count }} 帖子 · {{ user.comments_count }} 评论</span>
              </div>
            </div>
            <div class="text-right">
              <span class="block font-mono font-bold text-sm text-indigo-600">{{ user.engagement_score }} VP</span>
              <span class="text-[10px] text-gray-400">熵值</span>
            </div>
          </li>
          <li v-if="!(stats.top_contributors || []).length" class="text-center py-6 text-sm text-gray-500">
            暂无贡献者数据
          </li>
        </ul>
      </div>

      <!-- 实时流日志 -->
      <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-gray-50 rounded-bl-full -z-0 opacity-50"></div>
        <div class="relative z-10 flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">审计追踪 (Audit Trail)</h2>
            <p class="text-sm text-gray-500">最近系统与处置流转变化</p>
          </div>
          <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div class="space-y-5 relative before:absolute before:inset-y-0 before:left-2 before:w-0.5 before:bg-gray-100 pl-6">
          <div v-for="item in (stats.recent_ops || [])" :key="`${item.type}-${item.time}`" class="relative">
            <div class="absolute -left-[29px] top-1 w-4 h-4 rounded-full border-[3px] border-white z-10" :class="item.type === 'report' ? 'bg-orange-400' : 'bg-blue-500'"></div>
            <h4 class="text-sm font-bold text-gray-900">{{ item.headline }}</h4>
            <p class="text-xs text-gray-500 mt-1">{{ item.detail }}</p>
            <span class="text-[10px] text-gray-400 font-mono mt-1.5 block">{{ new Date(item.time).toLocaleString() }}</span>
          </div>
          <div v-if="!(stats.recent_ops || []).length" class="text-sm text-gray-500 py-4">系统审计池平静。</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

definePageMeta({ layout: 'admin' })

const { apiBase, getAuthHeaders } = useApi()

const error = ref('')

const { data, pending, refresh } = await useFetch(`${apiBase}/admin/stats`, {
  server: false,
  headers: computed(() => getAuthHeaders()),
  onResponseError: ({ response }) => {
    if (response.status === 403) error.value = '您不具备平台全局管理员权限，访问被拒绝。'
    if (response.status === 401) error.value = '登录状态已失效，请重新登录后台。'
  },
})

const stats = computed(() => data.value ?? {})
const categoryHeat = computed(() => stats.value.category_heat ?? [])
const metricTrends = computed(() => stats.value.metric_trends ?? {})

const formatTrendLabel = (value) => {
  const numeric = Number(value ?? 0)

  if (numeric === 0) {
    return '0.0%'
  }

  return `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}%`
}

const trendTone = (value, inverse = false) => {
  const numeric = Number(value ?? 0)

  if (numeric === 0) {
    return 'neutral'
  }

  if (inverse) {
    return numeric > 0 ? 'warning' : 'up'
  }

  return numeric > 0 ? 'up' : 'down'
}

const trendBadgeClass = (tone) => ({
  up: 'bg-emerald-50 text-emerald-600',
  down: 'bg-rose-50 text-rose-600',
  warning: 'bg-orange-50 text-orange-600',
  neutral: 'bg-gray-100 text-gray-600',
}[tone] ?? 'bg-gray-100 text-gray-600')

const overviewCards = computed(() => [
  {
    key: 'posts',
    title: '全站内容库（帖）',
    value: Number(stats.value.posts_count ?? 0),
    trendLabel: formatTrendLabel(metricTrends.value.posts),
    trendTone: trendTone(metricTrends.value.posts),
    trendHint: '较上 7 天新增帖子',
    icon: 'posts',
  },
  {
    key: 'users',
    title: '注册用户规模',
    value: Number(stats.value.users_count ?? 0),
    trendLabel: formatTrendLabel(metricTrends.value.users),
    trendTone: trendTone(metricTrends.value.users),
    trendHint: '较上 7 天新增用户',
    icon: 'users',
  },
  {
    key: 'active_users',
    title: '近 7 日高频活跃',
    value: Number(stats.value.active_users_count ?? 0),
    trendLabel: formatTrendLabel(metricTrends.value.active_users),
    trendTone: trendTone(metricTrends.value.active_users),
    trendHint: '较前 7 天活跃变化',
    icon: 'activity',
  },
  {
    key: 'reports',
    title: '风险审核队列',
    value: Number(stats.value.reports_count ?? 0),
    trendLabel: formatTrendLabel(metricTrends.value.reports),
    trendTone: trendTone(metricTrends.value.reports, true),
    trendHint: '较上 7 天新增举报',
    icon: 'risk',
  },
])

const heatWidth = (item) => {
  const max = Math.max(...categoryHeat.value.map((entry) => Number(entry.posts_count) || 0), 1)
  return Math.max(5, ((Number(item.posts_count) || 0) / max) * 100)
}

const refreshAnnouncements = () => refresh()
</script>
