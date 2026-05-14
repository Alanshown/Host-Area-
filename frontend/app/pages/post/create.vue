<template>
  <div class="max-w-4xl mx-auto py-8">
    <div :class="['loading-frame-shell bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 p-8', submitting ? 'is-loading' : '']">
      <h1 class="text-2xl font-bold text-gray-900 mb-8">发布新帖子</h1>

      <form class="space-y-6" @submit.prevent="handleSubmit">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">标题</label>
          <input
            v-model="form.title"
            type="text"
            placeholder="请输入帖子标题 (最少 5 个字符)"
            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">封面图片（可选）</label>
          <div class="relative group cursor-pointer border-2 border-dashed border-gray-200 hover:border-blue-400 bg-gray-50/50 hover:bg-blue-50/30 rounded-2xl p-6 transition-all text-center overflow-hidden">
            <input type="file" accept="image/*" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="handleCoverChange" />
            <div class="flex flex-col items-center justify-center space-y-2 text-gray-500 group-hover:text-blue-500 transition-colors">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span class="text-sm font-medium">点击或拖拽上传封面图片</span>
              <span class="text-xs text-gray-400">支持 JPG, PNG, WEBP (多选)</span>
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-400">可连续选择多张候选封面，提交时会使用当前高亮的主封面。</p>
          <div v-if="coverItems.length" class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3">
            <div
              v-for="item in coverItems"
              :key="item.id"
              class="group relative overflow-hidden rounded-xl border border-gray-200"
              :class="activeCoverId === item.id ? 'ring-2 ring-blue-500 border-blue-400' : 'hover:border-gray-300'"
            >
              <button type="button" class="block w-full text-left" @click="setActiveCover(item.id)">
                <div class="flex h-36 w-full items-center justify-center bg-gray-100/80">
                  <img :src="item.url" alt="cover preview" class="h-full w-full object-contain" />
                </div>
              </button>
              <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-3 py-2 text-xs text-white">
                <span>{{ activeCoverId === item.id ? '主封面' : '设为主封面' }}</span>
                <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500/90 text-white transition hover:bg-red-600" @click.stop="removeCover(item.id)">&times;</button>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">选择分类</label>
          <select
            v-model="form.category_id"
            class="w-full md:w-1/2 px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white"
          >
            <option value="">请选择一个分类</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">正文内容</label>
          <textarea
            v-model="form.content"
            rows="12"
            placeholder="支持 Markdown 语法... 来分享你的想法吧！"
            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50 focus:bg-white resize-y font-mono text-sm"
          ></textarea>
        </div>

        <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

        <div class="pt-6 flex items-center justify-end space-x-4 border-t border-gray-100">
          <NuxtLink
            to="/"
            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-full hover:bg-gray-50 transition-colors"
          >
            取消
          </NuxtLink>
          <button
            type="submit"
            :disabled="submitting"
            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-all hover:shadow disabled:opacity-60"
          >
            {{ submitting ? '发布中...' : '发布帖子' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

const { data: catsRes } = await useFetch(`${apiBase}/categories`)
const categories = computed(() => catsRes.value?.data ?? [])

const form        = reactive({ title: '', category_id: '', content: '' })
const submitting  = ref(false)
const submitError = ref('')
const coverItems = ref([])
const activeCoverId = ref('')

const handleCoverChange = (e) => {
  const files = Array.from(e.target.files || [])

  files.forEach((file) => {
    const id = `${file.name}-${file.size}-${file.lastModified}-${Math.random().toString(16).slice(2)}`
    coverItems.value.push({ id, file, url: URL.createObjectURL(file) })
    activeCoverId.value = id
  })

  e.target.value = ''
}
const setActiveCover = (id) => {
  activeCoverId.value = id
}
const removeCover = (id) => {
  const target = coverItems.value.find((item) => item.id === id)
  if (target?.url) {
    URL.revokeObjectURL(target.url)
  }
  coverItems.value = coverItems.value.filter((item) => item.id !== id)
  if (activeCoverId.value === id) {
    activeCoverId.value = coverItems.value[0]?.id ?? ''
  }
}

const handleSubmit = async () => {
  submitError.value = ''
  if (!form.title.trim() || !form.category_id || !form.content.trim()) {
    submitError.value = '请填写标题、分类和正文内容'
    return
  }
  if (form.title.trim().length < 5) {
    submitError.value = '标题至少需要 5 个字符'
    return
  }
  if (form.content.trim().length < 10) {
    submitError.value = '正文内容至少需要 10 个字符'
    return
  }
  submitting.value = true
  try {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      submitError.value = '请先登录后再发布帖子'
      submitting.value = false
      return
    }

    const body = new FormData()
    body.append('title', form.title)
    body.append('content', form.content)
    body.append('category_id', form.category_id)
    const activeCover = coverItems.value.find((item) => item.id === activeCoverId.value) ?? coverItems.value[0]
    if (activeCover?.file) {
      body.append('cover_image', activeCover.file)
    }

    const result = await $fetch(`${apiBase}/posts`, {
      method:  'POST',
      headers: { Authorization: `Bearer ${token}` },
      body,
    })
    const nextPath = result.data?.moderation_status === 'approved'
      ? `/post/${result.data.id}`
      : '/user/posts'
    await navigateTo(nextPath)
  } catch (e) {
    submitError.value = extractApiError(e, '发布失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

onBeforeUnmount(() => {
  coverItems.value.forEach((item) => {
    if (item.url) {
      URL.revokeObjectURL(item.url)
    }
  })
})
</script>