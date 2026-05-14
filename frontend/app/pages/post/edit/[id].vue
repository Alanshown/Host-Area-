<template>
  <div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold text-gray-900">编辑帖子</h1>
      <NuxtLink :to="`/post/${route.params.id}`" class="text-sm text-gray-500 hover:text-gray-700">← 返回帖子</NuxtLink>
    </div>

    <div v-if="loadPending" class="bg-white border border-gray-200 rounded-xl p-6 animate-pulse">
      <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
      <div class="h-4 bg-gray-100 rounded w-1/3 mb-4"></div>
      <div class="h-32 bg-gray-100 rounded"></div>
    </div>

    <form v-else @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">标题</label>
        <input
          v-model="form.title"
          type="text"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="请输入帖子标题"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">分类</label>
        <select
          v-model="form.category_id"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
        >
          <option value="" disabled>选择分类...</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">正文内容</label>
        <textarea
          v-model="form.content"
          required
          rows="12"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          placeholder="编辑帖子内容..."
        ></textarea>
      </div>

      <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ error }}</p>

      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="saving"
          class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50"
        >
          {{ saving ? '保存中...' : '保存修改' }}
        </button>
        <NuxtLink
          :to="`/post/${route.params.id}`"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm"
        >
          取消
        </NuxtLink>
      </div>
    </form>
  </div>
</template>

<script setup>
const route   = useRoute()
const router  = useRouter()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase
const auth    = useAuth()

const form = reactive({ title: '', content: '', category_id: '' })
const saving     = ref(false)
const error      = ref('')
const loadPending = ref(true)

// Load categories and post in parallel
const [catData, postData] = await Promise.all([
  $fetch(`${apiBase}/categories`),
  $fetch(`${apiBase}/posts/${route.params.id}`)
])
const categories = catData?.data ?? []
form.title       = postData?.data?.title ?? ''
form.content     = postData?.data?.content ?? ''
form.category_id = postData?.data?.category_id ?? ''
loadPending.value = false

const submit = async () => {
  if (!auth.isLoggedIn.value) { navigateTo('/login'); return }
  error.value  = ''
  saving.value = true
  try {
    const result = await $fetch(`${apiBase}/posts/${route.params.id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json'
      },
      body: { title: form.title, content: form.content, category_id: form.category_id }
    })
    navigateTo(result?.data?.moderation_status === 'approved' ? `/post/${route.params.id}` : '/user/posts')
  } catch (e) {
    error.value = e.data?.message ?? '保存失败，请重试'
  } finally {
    saving.value = false
  }
}
</script>
