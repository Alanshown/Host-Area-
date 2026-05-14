<template>
  <div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">通知中心</h1>
        <p class="mt-1 text-sm text-gray-500">这里会聚合评论提醒、关注提醒、举报进展和系统公告，并支持未读管理。</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="pending"
          @click="refreshNotifications"
        >
          刷新
        </button>
        <button
          v-if="summary.unread > 0"
          type="button"
          class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="actionPending"
          @click="markAllRead"
        >
          全部标为已读
        </button>
      </div>
    </div>

    <div v-if="!auth.isLoggedIn.value" class="py-16 text-center text-gray-400">
      <p class="mb-3">请先登录</p>
      <NuxtLink to="/login" class="text-blue-600 hover:underline">前往登录</NuxtLink>
    </div>

    <template v-else>
      <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
        <button
          v-for="item in summaryCards"
          :key="item.key"
          type="button"
          class="rounded-2xl border px-4 py-3 text-left transition"
          :class="activeFilter === item.key ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
          @click="activeFilter = item.key"
        >
          <p class="text-xs text-gray-500">{{ item.label }}</p>
          <div class="mt-1 flex items-center gap-2">
            <p class="text-lg font-bold text-gray-900">{{ item.value }}</p>
            <span v-if="item.unread" class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-semibold text-white">{{ item.unread }}</span>
          </div>
        </button>
      </div>

      <div class="mb-5 flex flex-wrap gap-2">
        <button
          v-for="item in statusFilters"
          :key="item.key"
          type="button"
          class="rounded-full px-4 py-2 text-sm font-medium transition"
          :class="activeReadFilter === item.key ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
          @click="activeReadFilter = item.key"
        >
          {{ item.label }}
        </button>
      </div>

      <div v-if="pending" class="space-y-4">
        <div v-for="i in 4" :key="i" class="rounded-2xl border border-gray-200 bg-white p-5 animate-pulse">
          <div class="mb-3 h-4 w-1/3 rounded bg-gray-200"></div>
          <div class="mb-2 h-3 w-3/4 rounded bg-gray-100"></div>
          <div class="h-3 w-1/2 rounded bg-gray-100"></div>
        </div>
      </div>

      <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-6 py-8 text-sm text-red-600">
        {{ error }}
      </div>

      <div v-else-if="filteredNotifications.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-white/70 px-6 py-16 text-center text-sm text-gray-400">
        当前筛选条件下还没有通知
      </div>

      <div v-else class="space-y-4">
        <article
          v-for="item in filteredNotifications"
          :key="item.id"
          class="rounded-[24px] border bg-white/80 p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl transition"
          :class="item.is_read ? 'border-gray-200/80' : 'border-blue-200 ring-1 ring-blue-100'"
        >
          <div class="flex items-start gap-4">
            <NuxtLink v-if="item.actor?.id" :to="`/user/${item.actor.id}`" class="shrink-0">
              <img v-if="resolveMediaUrl(item.actor.avatar)" :src="resolveMediaUrl(item.actor.avatar)" :alt="item.actor.username" class="h-11 w-11 rounded-full object-cover" />
              <div v-else class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                {{ item.actor.username?.[0]?.toUpperCase() || '?' }}
              </div>
            </NuxtLink>
            <div v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-500">
              {{ typeInitial(item.type) }}
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-wide" :class="typeTone(item.type)">{{ typeLabel(item.type) }}</span>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="item.is_read ? 'bg-gray-100 text-gray-500' : 'bg-red-50 text-red-600'">
                  {{ item.is_read ? '已读' : '未读' }}
                </span>
                <time class="text-xs text-gray-400">{{ timeAgo(item.time) }}</time>
              </div>
              <h2 class="mt-3 text-base font-bold text-gray-900">{{ item.title }}</h2>
              <p class="mt-1 text-sm font-medium text-gray-700">{{ item.headline }}</p>
              <p class="mt-2 text-sm leading-6 text-gray-500">{{ item.detail }}</p>

              <div class="mt-4 flex flex-wrap gap-2">
                <NuxtLink
                  v-if="item.link && !item.meta?.external"
                  :to="item.link"
                  @click="handleOpenNotification(item)"
                  class="rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                  前往查看
                </NuxtLink>
                <a
                  v-else-if="item.link && item.meta?.external"
                  :href="item.link"
                  target="_blank"
                  rel="noreferrer noopener"
                  @click="handleOpenNotification(item)"
                  class="rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                  {{ item.meta?.link_label || '打开链接' }}
                </a>
                <button
                  type="button"
                  class="rounded-full border px-4 py-2 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
                  :class="item.is_read ? 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' : 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'"
                  :disabled="actionPending"
                  @click="toggleRead(item)"
                >
                  {{ item.is_read ? '标为未读' : '标为已读' }}
                </button>
              </div>
            </div>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<script setup>
import { extractApiError, timeAgo } from '~/composables/useApi.js'

const auth = useAuth()
const { resolveMediaUrl } = useApi()
const { items: notifications, summary, pending, error, loadNotifications, updateNotificationStatus } = useNotifications()
const activeFilter = ref('all')
const activeReadFilter = ref('all')
const actionPending = ref(false)

const summaryCards = computed(() => [
  { key: 'all', label: '全部', value: summary.value.total ?? 0, unread: summary.value.unread ?? 0 },
  { key: 'comment', label: '评论', value: summary.value.comments ?? 0, unread: unreadByType.value.comment },
  { key: 'follow', label: '关注', value: summary.value.follows ?? 0, unread: unreadByType.value.follow },
  { key: 'report', label: '举报', value: summary.value.reports ?? 0, unread: unreadByType.value.report },
  { key: 'system', label: '系统', value: summary.value.system ?? 0, unread: unreadByType.value.system },
])

const statusFilters = [
  { key: 'all', label: '全部状态' },
  { key: 'unread', label: '仅看未读' },
  { key: 'read', label: '仅看已读' },
]

const unreadByType = computed(() => {
  return notifications.value.reduce((result, item) => {
    if (!item.is_read && result[item.type] !== undefined) {
      result[item.type] += 1
    }
    return result
  }, {
    comment: 0,
    follow: 0,
    report: 0,
    system: 0,
  })
})

const filteredNotifications = computed(() => {
  let items = notifications.value

  if (activeFilter.value !== 'all') {
    items = items.filter((item) => item.type === activeFilter.value)
  }

  if (activeReadFilter.value === 'unread') {
    items = items.filter((item) => !item.is_read)
  } else if (activeReadFilter.value === 'read') {
    items = items.filter((item) => item.is_read)
  }

  return items
})

const typeLabel = (type) => {
  const map = {
    comment: '评论提醒',
    follow: '关注提醒',
    report: '举报进展',
    system: '系统通知',
  }
  return map[type] || '通知'
}

const typeInitial = (type) => {
  const map = {
    comment: '评',
    follow: '关',
    report: '举',
    system: '系',
  }
  return map[type] || '通'
}

const typeTone = (type) => {
  const map = {
    comment: 'bg-sky-50 text-sky-700',
    follow: 'bg-emerald-50 text-emerald-700',
    report: 'bg-amber-50 text-amber-700',
    system: 'bg-violet-50 text-violet-700',
  }
  return map[type] || 'bg-gray-100 text-gray-700'
}

const refreshNotifications = async () => {
  try {
    await loadNotifications()
  } catch (err) {
    error.value = extractApiError(err, '通知中心加载失败，请稍后重试')
  }
}

const toggleRead = async (item) => {
  actionPending.value = true

  try {
    await updateNotificationStatus({ ids: [item.id], read: !item.is_read })
  } catch (err) {
    error.value = extractApiError(err, '通知状态更新失败，请稍后重试')
  } finally {
    actionPending.value = false
  }
}

const markAllRead = async () => {
  actionPending.value = true

  try {
    await updateNotificationStatus({ markAll: true, read: true })
  } catch (err) {
    error.value = extractApiError(err, '全部已读更新失败，请稍后重试')
  } finally {
    actionPending.value = false
  }
}

const handleOpenNotification = async (item) => {
  if (item.is_read || actionPending.value) return

  try {
    await updateNotificationStatus({ ids: [item.id], read: true })
  } catch {}
}

onMounted(async () => {
  auth.initAuth()
  await refreshNotifications()
})
</script>