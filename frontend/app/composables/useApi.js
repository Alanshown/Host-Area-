export const useApi = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase
  const siteBase = apiBase.replace(/\/api$/, '')

  const getAuthHeaders = () => {
    if (!process.client) return {}
    const token = localStorage.getItem('auth_token')
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  const apiFetch = (path, options = {}) => {
    return $fetch(`${apiBase}${path}`, {
      headers: { ...getAuthHeaders(), ...(options.headers || {}) },
      ...options,
    })
  }

  const resolveMediaUrl = (path) => {
    if (!path) return ''
    if (/^https?:\/\//i.test(path)) return path
    return `${siteBase}${path.startsWith('/') ? path : `/${path}`}`
  }

  return { apiBase, apiFetch, resolveMediaUrl, siteBase, getAuthHeaders }
}

export const normalizeBannerItem = (item) => {
  if (!item) return null

  if (typeof item === 'string') {
    return {
      path: item,
      focusX: 50,
      focusY: 50,
      zoom: 1,
    }
  }

  return {
    path: item.path || item.url || '',
    focusX: Number.isFinite(Number(item.focusX)) ? Number(item.focusX) : 50,
    focusY: Number.isFinite(Number(item.focusY)) ? Number(item.focusY) : 50,
    zoom: Number.isFinite(Number(item.zoom)) ? Number(item.zoom) : 1,
  }
}

export const serializeBannerItem = (item) => JSON.stringify({
  path: item.path,
  focusX: item.focusX,
  focusY: item.focusY,
  zoom: item.zoom,
})

export const getBannerDisplayStyle = (item) => {
  const normalized = normalizeBannerItem(item)

  if (!normalized) return {}

  return {
    objectPosition: `${normalized.focusX}% ${normalized.focusY}%`,
    transform: `scale(${normalized.zoom})`,
    transformOrigin: `${normalized.focusX}% ${normalized.focusY}%`,
  }
}

export const extractApiError = (error, fallback = '请求失败，请稍后重试') => {
  const errors = error?.data?.errors

  if (errors && typeof errors === 'object') {
    const firstMessage = Object.values(errors)
      .flat()
      .find(Boolean)

    if (firstMessage) {
      return firstMessage
    }
  }

  return error?.data?.message || fallback
}

export const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr.replace(' ', 'T'))
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  if (minutes < 1) return '刚刚'
  if (minutes < 60) return `${minutes} 分钟前`
  if (hours < 24) return `${hours} 小时前`
  if (days < 30) return `${days} 天前`
  return dateStr.slice(0, 10)
}
