import { extractApiError } from '~/composables/useApi.js'

export const useNotifications = () => {
  const auth = useAuth()
  const { apiFetch } = useApi()
  const items = useState('notification_items', () => [])
  const summary = useState('notification_summary', () => ({
    total: 0,
    unread: 0,
    comments: 0,
    follows: 0,
    reports: 0,
    system: 0,
  }))
  const pending = useState('notification_pending', () => false)
  const error = useState('notification_error', () => '')

  const reset = () => {
    items.value = []
    summary.value = {
      total: 0,
      unread: 0,
      comments: 0,
      follows: 0,
      reports: 0,
      system: 0,
    }
    error.value = ''
  }

  const loadNotifications = async () => {
    if (!auth.isLoggedIn.value) {
      reset()
      return { data: [], summary: summary.value }
    }

    pending.value = true
    error.value = ''

    try {
      const response = await apiFetch('/user/notifications')
      items.value = response.data ?? []
      summary.value = {
        total: response.summary?.total ?? items.value.length,
        unread: response.summary?.unread ?? items.value.filter(item => !item.is_read).length,
        comments: response.summary?.comments ?? 0,
        follows: response.summary?.follows ?? 0,
        reports: response.summary?.reports ?? 0,
        system: response.summary?.system ?? 0,
      }
      return response
    } catch (err) {
      reset()
      error.value = extractApiError(err, '通知中心加载失败，请稍后重试')
      throw err
    } finally {
      pending.value = false
    }
  }

  const updateNotificationStatus = async ({ ids, read = true, markAll = false }) => {
    const normalizedIds = Array.isArray(ids) ? ids.filter(Boolean) : []

    await apiFetch('/user/notifications/read', {
      method: 'POST',
      body: {
        ids: normalizedIds,
        read,
        mark_all: markAll,
      },
    })

    if (markAll) {
      items.value = items.value.map(item => ({ ...item, is_read: true }))
    } else if (normalizedIds.length) {
      const lookup = new Set(normalizedIds)
      items.value = items.value.map(item => lookup.has(item.id) ? { ...item, is_read: read } : item)
    }

    summary.value = {
      ...summary.value,
      unread: items.value.filter(item => !item.is_read).length,
      total: items.value.length,
    }
  }

  return {
    items,
    summary,
    pending,
    error,
    reset,
    loadNotifications,
    updateNotificationStatus,
  }
}
