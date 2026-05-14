<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">内容审核</h1>
        <p class="text-gray-500 mt-1">AI 辅助内容审核，可疑内容人工复核</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">待审核</p>
              <p class="text-2xl font-bold text-orange-600">{{ stats.pending }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">⏳</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">已通过</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">✅</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">已拒绝</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">❌</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">今日处理</p>
              <p class="text-2xl font-bold text-blue-600">{{ todayProcessed }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">📊</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              type="button"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="currentStatus === tab.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              @click="currentStatus = tab.value; fetchQueue()"
            >
              {{ tab.label }}
            </button>
          </div>
          <div class="flex-1"></div>
          <select v-model="filterType" class="px-4 py-2 rounded-lg border border-gray-200 text-sm" @change="fetchQueue">
            <option value="">全部类型</option>
            <option value="post">帖子</option>
            <option value="comment">评论</option>
            <option value="chat_message">聊天消息</option>
          </select>
          <button
            type="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            @click="fetchQueue"
          >
            刷新
          </button>
        </div>
      </div>

      <!-- Queue List -->
      <div v-if="loading" class="text-center py-12 text-gray-500">
        加载中...
      </div>

      <div v-else-if="!items.length" class="bg-white rounded-xl p-12 text-center shadow-sm border border-gray-100">
        <div class="text-4xl mb-4">🎉</div>
        <p class="text-gray-500">暂无待审核内容</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="item in items"
          :key="item.id"
          class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <img
              v-if="item.avatar"
              :src="resolveMediaUrl(item.avatar)"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div v-else class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
              {{ item.username?.[0]?.toUpperCase() || '?' }}
            </div>

            <div class="flex-1 min-w-0">
              <!-- Meta -->
              <div class="flex items-center gap-3 mb-2">
                <span class="font-medium text-gray-900">{{ item.username || '匿名用户' }}</span>
                <span class="px-2 py-0.5 rounded text-xs"
                  :class="{
                    'bg-orange-100 text-orange-700': item.status === 'pending',
                    'bg-green-100 text-green-700': item.status === 'approved',
                    'bg-red-100 text-red-700': item.status === 'rejected',
                  }"
                >
                  {{ statusLabel(item.status) }}
                </span>
                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                  {{ typeLabel(item.content_type) }}
                </span>
                <span class="text-sm text-gray-400">{{ formatTime(item.created_at) }}</span>
              </div>

              <!-- Content Preview -->
              <div class="bg-gray-50 rounded-lg p-3 mb-3 max-h-40 overflow-y-auto">
                <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ item.content }}</p>
              </div>

              <!-- Violation Info -->
              <div v-if="item.category" class="flex flex-wrap items-center gap-2 mb-3">
                <span class="px-2 py-1 rounded bg-red-50 text-red-600 text-xs font-medium">
                  {{ item.category }}
                </span>
                <span v-if="item.score" class="text-xs text-gray-500">
                  风险指数: {{ (item.score * 100).toFixed(0) }}%
                </span>
              </div>

              <!-- Analysis Details -->
              <div v-if="item.analysis" class="bg-blue-50 rounded-lg p-3 mb-3">
                <p class="text-xs text-blue-700 font-medium mb-1">AI 分析结果</p>
                <div v-if="item.analysis.categories" class="space-y-1">
                  <div v-for="(score, category) in item.analysis.categories" :key="category" class="flex items-center gap-2">
                    <span class="text-xs text-blue-600 w-24">{{ score.name || category }}</span>
                    <div class="flex-1 h-2 bg-blue-100 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="{
                          'bg-red-500': score.score > 0.6,
                          'bg-orange-500': score.score > 0.3 && score.score <= 0.6,
                          'bg-green-500': score.score <= 0.3,
                        }"
                        :style="{ width: `${score.score * 100}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 w-16 text-right">{{ (score.score * 100).toFixed(0) }}%</span>
                  </div>
                </div>
              </div>

              <!-- Reason -->
              <div v-if="item.reason" class="text-sm text-gray-600 mb-3">
                <strong>原因：</strong>{{ item.reason }}
              </div>

              <!-- Actions -->
              <div v-if="item.status === 'pending'" class="flex items-center gap-3">
                <textarea
                  v-model="rejectReasons[item.id]"
                  placeholder="拒绝原因（可选）"
                  class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm resize-none"
                  rows="1"
                ></textarea>
                <button
                  type="button"
                  class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
                  @click="rejectItem(item.id)"
                >
                  拒绝
                </button>
                <button
                  type="button"
                  class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                  @click="approveItem(item.id)"
                >
                  通过
                </button>
              </div>

              <!-- Review Info -->
              <div v-else class="text-sm text-gray-400">
                <span v-if="item.moderated_at">
                  {{ item.status === 'approved' ? '通过' : '拒绝' }}于 {{ formatTime(item.moderated_at) }}
                </span>
                <span v-if="item.reason">，原因：{{ item.reason }}</span>
              </div>
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
import { extractApiError } from '~/composables/useApi.js'

definePageMeta({
  layout: 'default',
  middleware: ['auth'],
})

const { apiBase, apiFetch, resolveMediaUrl, getAuthHeaders } = useApi()
const auth = useAuth()

const currentStatus = ref('pending')
const filterType = ref('')
const loading = ref(false)
const items = ref([])
const stats = ref({ pending: 0, approved: 0, rejected: 0 })
const rejectReasons = reactive({})
const meta = ref({ current_page: 1, last_page: 1 })

const tabs = [
  { label: '待审核', value: 'pending' },
  { label: '已通过', value: 'approved' },
  { label: '已拒绝', value: 'rejected' },
]

const todayProcessed = computed(() => {
  return stats.value.approved + stats.value.rejected
})

const fetchQueue = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page, status: currentStatus.value })
    if (filterType.value) params.set('type', filterType.value)

    const response = await apiFetch(`/admin/moderation/queue?${params}`)
    items.value = response.data || []
    if (response.meta) {
      meta.value = response.meta
    }
  } catch (error) {
    console.error('Failed to fetch moderation queue:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await apiFetch('/admin/moderation/stats')
    if (response.data?.summary) {
      stats.value = response.data.summary
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const approveItem = async (id) => {
  try {
    await apiFetch(`/admin/moderation/${id}/approve`, {
      method: 'POST',
      body: { notes: rejectReasons[id] || '审核通过' },
    })
    items.value = items.value.filter(item => item.id !== id)
    stats.value.pending--
    stats.value.approved++
  } catch (error) {
    alert(extractApiError(error, '操作失败'))
  }
}

const rejectItem = async (id) => {
  if (!rejectReasons[id]?.trim()) {
    alert('请填写拒绝原因')
    return
  }
  try {
    await apiFetch(`/admin/moderation/${id}/reject`, {
      method: 'POST',
      body: { reason: rejectReasons[id] },
    })
    items.value = items.value.filter(item => item.id !== id)
    stats.value.pending--
    stats.value.rejected++
  } catch (error) {
    alert(extractApiError(error, '操作失败'))
  }
}

const changePage = (page) => {
  fetchQueue(page)
}

const statusLabel = (status) => ({
  pending: '待审核',
  approved: '已通过',
  rejected: '已拒绝',
}[status] || status)

const typeLabel = (type) => ({
  post: '帖子',
  comment: '评论',
  chat_message: '聊天',
}[type] || type)

const formatTime = (time) => {
  if (!time) return ''
  return new Date(time).toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  auth.initAuth()
  await fetchQueue()
  await fetchStats()
})
</script>
