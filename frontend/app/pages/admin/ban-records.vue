<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
          <NuxtLink to="/admin" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </NuxtLink>
          <h1 class="text-3xl font-bold text-gray-900">封禁记录</h1>
        </div>
        <p class="text-gray-500">查看用户禁言和封禁记录，包括系统自动巡检和手动处罚</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">总禁言</p>
              <p class="text-2xl font-bold text-yellow-600">{{ stats.total_mutes }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">🔇</span>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">自动: {{ stats.auto_mutes }} | 手动: {{ stats.manual_mutes }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">总封禁</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.total_bans }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">🔨</span>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">自动: {{ stats.auto_bans }} | 手动: {{ stats.manual_bans }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">当前有效</p>
              <p class="text-2xl font-bold text-orange-600">{{ stats.active_mutes + stats.active_bans }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">⚠️</span>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">禁言: {{ stats.active_mutes }} | 封禁: {{ stats.active_bans }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">自动巡检</p>
              <p class="text-2xl font-bold text-blue-600">{{ stats.auto_mutes + stats.auto_bans }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">🤖</span>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">系统自动检测处罚次数</p>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex gap-2">
            <button
              v-for="tab in typeTabs"
              :key="tab.value"
              type="button"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="currentType === tab.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              @click="currentType = tab.value; fetchRecords()"
            >
              {{ tab.label }}
            </button>
          </div>

          <div class="flex-1"></div>

          <select v-model="filterSource" class="px-4 py-2 rounded-lg border border-gray-200 text-sm" @change="fetchRecords">
            <option value="">全部来源</option>
            <option value="auto">自动巡检</option>
            <option value="manual">手动处罚</option>
            <option value="report">举报处理</option>
          </select>

          <button
            type="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            @click="fetchRecords"
          >
            刷新
          </button>
        </div>
      </div>

      <!-- Records List -->
      <div v-if="loading" class="text-center py-12 text-gray-500">
        加载中...
      </div>

      <div v-else-if="!records.length" class="bg-white rounded-xl p-12 text-center shadow-sm border border-gray-100">
        <div class="text-4xl mb-4">✅</div>
        <p class="text-gray-500">暂无封禁记录</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="record in records"
          :key="record.id"
          class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center text-blue-600 font-bold text-lg">
              {{ record.user?.username?.[0]?.toUpperCase() || '?' }}
            </div>

            <div class="flex-1 min-w-0">
              <!-- Header -->
              <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span class="font-bold text-gray-900">{{ record.user?.username || '未知用户' }}</span>

                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="record.ban_type === 'mute' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                >
                  {{ record.ban_type === 'mute' ? '禁言 30 分钟' : '封禁 1 天' }}
                </span>

                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="{
                    'bg-blue-100 text-blue-700': record.source === 'auto',
                    'bg-purple-100 text-purple-700': record.source === 'manual',
                    'bg-orange-100 text-orange-700': record.source === 'report',
                  }"
                >
                  {{ sourceLabel(record.source) }}
                </span>

                <span
                  v-if="record.unbanned_at"
                  class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700"
                >
                  已解封
                </span>
                <span
                  v-else-if="isActive(record)"
                  class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 animate-pulse"
                >
                  处罚中
                </span>
                <span
                  v-else
                  class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500"
                >
                  已过期
                </span>
              </div>

              <!-- Reason -->
              <div class="mb-3">
                <span class="text-sm font-medium text-gray-700">原因：</span>
                <span class="text-sm text-red-600">{{ record.reason }}</span>
              </div>

              <!-- Detail -->
              <div v-if="record.detail" class="text-sm text-gray-600 mb-3 bg-gray-50 rounded-lg p-3">
                {{ record.detail }}
              </div>

              <!-- Evidence -->
              <div v-if="record.evidence && record.evidence.length" class="mb-3">
                <span class="text-xs text-gray-500">违规消息数：{{ record.evidence.length }} 条</span>
              </div>

              <!-- Time Info -->
              <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                <span>处罚时间：{{ formatTime(record.created_at) }}</span>
                <span>到期时间：{{ formatTime(record.banned_until) }}</span>
                <span v-if="record.unbanned_at">解封时间：{{ formatTime(record.unbanned_at) }}</span>
                <span v-if="record.banned_by_user">
                  操作者：{{ record.banned_by_user.username }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="isActive(record) && !record.unbanned_at" class="flex-shrink-0">
              <button
                type="button"
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                @click="unbanUser(record.id)"
              >
                解除处罚
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="mt-6 flex justify-center gap-2">
        <button
          type="button"
          class="px-4 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50 disabled:opacity-50"
          :disabled="meta.current_page <= 1"
          @click="changePage(meta.current_page - 1)"
        >
          上一页
        </button>
        <span class="px-4 py-2 text-sm text-gray-600">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>
        <button
          type="button"
          class="px-4 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50 disabled:opacity-50"
          :disabled="meta.current_page >= meta.last_page"
          @click="changePage(meta.current_page + 1)"
        >
          下一页
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin',
  middleware: ['auth'],
})

const { apiBase, apiFetch } = useApi()
const auth = useAuth()

const records = ref([])
const loading = ref(false)
const currentType = ref('all')
const filterSource = ref('')
const meta = ref({ current_page: 1, last_page: 1 })

const stats = ref({
  total_mutes: 0,
  total_bans: 0,
  auto_mutes: 0,
  auto_bans: 0,
  manual_mutes: 0,
  manual_bans: 0,
  active_mutes: 0,
  active_bans: 0,
})

const typeTabs = [
  { label: '全部', value: 'all' },
  { label: '禁言', value: 'mute' },
  { label: '封禁', value: 'ban' },
]

const sourceLabel = (source) => {
  const labels = {
    auto: '自动巡检',
    manual: '手动处罚',
    report: '举报处理',
  }
  return labels[source] || source
}

const isActive = (record) => {
  if (!record.banned_until) return false
  const until = new Date(record.banned_until)
  return until > new Date() && !record.unbanned_at
}

const formatTime = (time) => {
  if (!time) return '-'
  return new Date(time).toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const fetchRecords = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page })
    if (currentType.value !== 'all') params.set('type', currentType.value)
    if (filterSource.value) params.set('source', filterSource.value)

    const response = await apiFetch(`/admin/ban-records?${params}`)
    records.value = response.data || []
    if (response.meta) {
      meta.value = response.meta
    }
  } catch (error) {
    console.error('Failed to fetch ban records:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await apiFetch('/admin/ban-records/stats')
    stats.value = response.data || stats.value
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const changePage = (page) => {
  fetchRecords(page)
}

const unbanUser = async (id) => {
  if (!confirm('确定要解除该用户的处罚吗？')) return

  try {
    await apiFetch(`/admin/ban-records/${id}/unban`, { method: 'POST' })
    fetchRecords()
    fetchStats()
  } catch (error) {
    alert('操作失败')
  }
}

onMounted(async () => {
  auth.initAuth()
  await fetchRecords()
  await fetchStats()
})
</script>
