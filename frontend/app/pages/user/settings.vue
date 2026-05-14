<template>
  <div class="mx-auto max-w-3xl">
    <h1 class="mb-6 text-xl font-bold text-gray-900">个人设置</h1>

    <div v-if="!auth.isLoggedIn.value" class="py-16 text-center text-gray-400">
      <p class="mb-3">请先登录</p>
      <NuxtLink to="/login" class="text-blue-600 hover:underline">前往登录</NuxtLink>
    </div>

    <form v-else @submit.prevent="save" :class="['loading-frame-shell space-y-6 rounded-2xl border border-gray-200/80 bg-white/70 backdrop-blur-xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)]', saving ? 'is-loading' : '']">
      <div class="grid gap-6 md:grid-cols-[220px,1fr]">
        <div class="space-y-4">
          <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 p-4 text-center">
            <div class="mx-auto h-24 w-24 overflow-hidden rounded-full bg-blue-600 text-white shadow-sm">
              <img v-if="avatarPreview" :src="avatarPreview" alt="avatar preview" class="h-full w-full object-cover" />
              <div v-else class="flex h-full w-full items-center justify-center text-3xl font-bold">{{ (form.username || '?')[0].toUpperCase() }}</div>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-800">{{ auth.user.value?.username }}</p>
            <p class="mt-1 text-xs text-gray-400">头像会自动裁切为圆形显示</p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">上传头像</label>
            <div class="relative group cursor-pointer border-2 border-dashed border-gray-200 hover:border-blue-400 bg-gray-50/50 hover:bg-blue-50/30 rounded-xl p-4 transition-all text-center overflow-hidden">
              <input type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="handleAvatarChange" />
              <div class="flex flex-col items-center justify-center space-y-1 text-gray-400 group-hover:text-blue-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="text-xs font-medium">点击更换头像</span>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-5">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">用户名</label>
            <input v-model="form.username" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">个人简介</label>
            <textarea v-model="form.bio" rows="4" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="说些关于自己的事情..."></textarea>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">主页背景图</label>
            <div class="relative group cursor-pointer border-2 border-dashed border-gray-200 hover:border-blue-400 bg-gray-50/50 hover:bg-blue-50/30 rounded-xl p-5 transition-all text-center overflow-hidden">
              <input type="file" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="handleBannerChange" />
              <div class="flex flex-col items-center justify-center space-y-2 text-gray-500 group-hover:text-blue-500 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-xs font-medium">点击或拖拽上传背景 (多选)</span>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">支持最多保留 5 张图片。多次选择会追加到队列中，只有点删除按钮才会移除。选中任意背景后可手动固定展示区域。</p>
            <div v-if="bannerPreviewItems.length" class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-3">
              <div v-for="item in bannerPreviewItems" :key="item.id" class="group relative overflow-hidden rounded-xl border border-gray-200" :class="selectedBannerId === item.id ? 'ring-2 ring-blue-500 border-blue-400' : ''">
                <button type="button" class="block w-full text-left" @click="selectBanner(item.id)">
                  <img :src="item.preview" alt="banner preview" class="h-24 w-full object-cover" :style="getBannerDisplayStyle(item)" />
                </button>
                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-2 py-2 text-[11px] text-white">
                  <span>{{ item.source === 'existing' ? '已保存' : '待上传' }}</span>
                  <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500/90 text-white transition hover:bg-red-600" @click="removeBanner(item)">&times;</button>
                </div>
              </div>
            </div>
            <div v-if="selectedBanner" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
              <div class="mb-3 overflow-hidden rounded-xl border border-gray-200 bg-gray-100/80">
                <div class="h-36 overflow-hidden">
                  <img :src="selectedBanner.preview" alt="banner crop preview" class="h-full w-full object-cover" :style="getBannerDisplayStyle(selectedBanner)" />
                </div>
              </div>
              <div class="space-y-3">
                <label class="block text-xs font-medium text-gray-600">横向取景 {{ Math.round(selectedBanner.focusX ?? 50) }}%</label>
                <input type="range" min="0" max="100" :value="selectedBanner.focusX ?? 50" class="w-full" @input="updateSelectedBanner('focusX', $event.target.value)" />
                <label class="block text-xs font-medium text-gray-600">纵向取景 {{ Math.round(selectedBanner.focusY ?? 50) }}%</label>
                <input type="range" min="0" max="100" :value="selectedBanner.focusY ?? 50" class="w-full" @input="updateSelectedBanner('focusY', $event.target.value)" />
                <label class="block text-xs font-medium text-gray-600">裁切缩放 {{ Number(selectedBanner.zoom ?? 1).toFixed(2) }}x</label>
                <input type="range" min="1" max="2.5" step="0.01" :value="selectedBanner.zoom ?? 1" class="w-full" @input="updateSelectedBanner('zoom', $event.target.value)" />
              </div>
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">新密码</label>
              <input v-model="form.password" type="password" minlength="6" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">确认密码</label>
              <input v-model="form.password_confirmation" type="password" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
        </div>
      </div>

      <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600">资料已更新。</p>

      <button type="submit" :disabled="saving" class="w-full rounded-lg bg-blue-600 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50">{{ saving ? '保存中...' : '保存修改' }}</button>
    </form>
  </div>
</template>

<script setup>
import { extractApiError, getBannerDisplayStyle, normalizeBannerItem, serializeBannerItem } from '~/composables/useApi.js'

const auth = useAuth()
const { apiBase, resolveMediaUrl } = useApi()

const form = reactive({
  username: auth.user.value?.username ?? '',
  bio: auth.user.value?.bio ?? '',
  password: '',
  password_confirmation: '',
})

const avatarFile = ref(null)
const existingBannerItems = ref([])
const newBannerItems = ref([])
const selectedBannerId = ref('')
const saving = ref(false)
const error = ref('')
const success = ref(false)
const avatarPreview = ref(resolveMediaUrl(auth.user.value?.avatar))
const bannerPreviewItems = computed(() => [
  ...existingBannerItems.value.map((item, index) => ({
    ...item,
    id: item.id || `existing-${index}-${item.path}`,
    source: 'existing',
    preview: resolveMediaUrl(item.path),
  })),
  ...newBannerItems.value.map((item) => ({
    id: item.id,
    source: 'new',
    preview: item.preview,
    file: item.file,
    focusX: item.focusX,
    focusY: item.focusY,
    zoom: item.zoom,
  })),
])
const selectedBanner = computed(() => bannerPreviewItems.value.find((item) => item.id === selectedBannerId.value) || null)

watch(() => auth.user.value, (value) => {
  if (!value) return

  form.username = value.username ?? ''
  form.bio = value.bio ?? ''
  avatarPreview.value = resolveMediaUrl(value.avatar)
  existingBannerItems.value = (value.profile_banners ?? [])
    .map((item, index) => {
      const normalized = normalizeBannerItem(item)
      return normalized ? { ...normalized, id: `existing-${index}-${normalized.path}` } : null
    })
    .filter(Boolean)
  newBannerItems.value.forEach((item) => URL.revokeObjectURL(item.preview))
  newBannerItems.value = []
  selectedBannerId.value = existingBannerItems.value[0]?.id ?? ''
}, { immediate: true })

const handleAvatarChange = (event) => {
  const [file] = event.target.files || []
  avatarFile.value = file || null
  avatarPreview.value = file ? URL.createObjectURL(file) : resolveMediaUrl(auth.user.value?.avatar)
}

const handleBannerChange = (event) => {
  const files = Array.from(event.target.files || [])
  const availableSlots = Math.max(0, 5 - existingBannerItems.value.length - newBannerItems.value.length)
  const acceptedFiles = files.slice(0, availableSlots)

  acceptedFiles.forEach((file) => {
    const id = `${file.name}-${file.size}-${file.lastModified}-${Math.random().toString(16).slice(2)}`
    newBannerItems.value.push({
      id,
      file,
      preview: URL.createObjectURL(file),
      focusX: 50,
      focusY: 50,
      zoom: 1,
    })
    selectedBannerId.value = id
  })

  event.target.value = ''
}

const removeBanner = (item) => {
  if (item.source === 'existing') {
    existingBannerItems.value = existingBannerItems.value.filter((banner) => banner.id !== item.id)
    if (selectedBannerId.value === item.id) {
      selectedBannerId.value = bannerPreviewItems.value.filter((banner) => banner.id !== item.id)[0]?.id ?? ''
    }
    return
  }

  const target = newBannerItems.value.find((banner) => banner.id === item.id)
  if (target?.preview) {
    URL.revokeObjectURL(target.preview)
  }
  newBannerItems.value = newBannerItems.value.filter((banner) => banner.id !== item.id)
  if (selectedBannerId.value === item.id) {
    selectedBannerId.value = bannerPreviewItems.value.filter((banner) => banner.id !== item.id)[0]?.id ?? ''
  }
}

const selectBanner = (id) => {
  selectedBannerId.value = id
}

const updateSelectedBanner = (field, rawValue) => {
  if (!selectedBanner.value) return

  const nextValue = field === 'zoom' ? Number(rawValue) : Math.round(Number(rawValue))

  if (selectedBanner.value.source === 'existing') {
    existingBannerItems.value = existingBannerItems.value.map((item) => item.id === selectedBanner.value.id ? { ...item, [field]: nextValue } : item)
    return
  }

  newBannerItems.value = newBannerItems.value.map((item) => item.id === selectedBanner.value.id ? { ...item, [field]: nextValue } : item)
}

const save = async () => {
  error.value = ''
  success.value = false

  if (form.password && form.password !== form.password_confirmation) {
    error.value = '两次密码输入不一致'
    return
  }

  saving.value = true

  try {
    const body = new FormData()
    body.append('_method', 'PUT')
    body.append('username', form.username)
    body.append('bio', form.bio || '')

    if (form.password) {
      body.append('password', form.password)
      body.append('password_confirmation', form.password_confirmation)
    }

    if (avatarFile.value) {
      body.append('avatar', avatarFile.value)
    }

    existingBannerItems.value.forEach((item) => {
      body.append('existing_profile_banners[]', serializeBannerItem(item))
    })

    newBannerItems.value.forEach(({ file, ...meta }) => {
      body.append('profile_banners[]', file)
      body.append('profile_banners_meta[]', serializeBannerItem(meta))
    })

    const token = localStorage.getItem('auth_token')
    const res = await $fetch(`${apiBase}/auth/profile`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body,
    })

    auth.setUser(res.user ?? res.data)
    form.password = ''
    form.password_confirmation = ''
    avatarFile.value = null
    newBannerItems.value.forEach((item) => URL.revokeObjectURL(item.preview))
    newBannerItems.value = []
    avatarPreview.value = resolveMediaUrl(auth.user.value?.avatar)
    existingBannerItems.value = (auth.user.value?.profile_banners ?? [])
      .map((item, index) => {
        const normalized = normalizeBannerItem(item)
        return normalized ? { ...normalized, id: `existing-${index}-${normalized.path}` } : null
      })
      .filter(Boolean)
    selectedBannerId.value = existingBannerItems.value[0]?.id ?? ''
    success.value = true
  } catch (err) {
    error.value = extractApiError(err, '保存失败，请重试')
  } finally {
    saving.value = false
  }
}

onBeforeUnmount(() => {
  newBannerItems.value.forEach((item) => URL.revokeObjectURL(item.preview))
})
</script>
