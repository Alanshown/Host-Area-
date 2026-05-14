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
          <h1 class="text-3xl font-bold text-gray-900">敏感词管理</h1>
        </div>
        <p class="text-gray-500">配置关键词库，用于内容巡检验证和违规检测</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">总词库</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">📚</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">启用中</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">✅</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">禁用</p>
              <p class="text-2xl font-bold text-gray-400">{{ stats.inactive }}</p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">🚫</span>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">触发封禁</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.banLevel }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <span class="text-2xl">🔨</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex gap-2">
            <button
              v-for="tab in statusTabs"
              :key="tab.value"
              type="button"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="currentStatus === tab.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              @click="currentStatus = tab.value; fetchWords()"
            >
              {{ tab.label }}
            </button>
          </div>

          <div class="flex-1"></div>

          <select v-model="filterCategory" class="px-4 py-2 rounded-lg border border-gray-200 text-sm" @change="fetchWords">
            <option value="">全部分类</option>
            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
          </select>

          <select v-model="filterLevel" class="px-4 py-2 rounded-lg border border-gray-200 text-sm" @change="fetchWords">
            <option value="">全部级别</option>
            <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
          </select>

          <div class="relative">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="搜索敏感词..."
              class="pl-10 pr-4 py-2 rounded-lg border border-gray-200 text-sm w-64"
              @input="debouncedSearch"
            />
          </div>

          <button
            type="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            @click="showAddModal = true"
          >
            + 添加敏感词
          </button>

          <button
            type="button"
            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
            @click="showImportModal = true"
          >
            批量导入
          </button>
        </div>
      </div>

      <!-- Words List -->
      <div v-if="loading" class="text-center py-12 text-gray-500">
        加载中...
      </div>

      <div v-else-if="!words.length" class="bg-white rounded-xl p-12 text-center shadow-sm border border-gray-100">
        <div class="text-4xl mb-4">📭</div>
        <p class="text-gray-500">暂无敏感词</p>
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">敏感词</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分类</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">级别</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="word in words" :key="word.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <span class="font-medium text-red-600">{{ word.word }}</span>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2 py-1 rounded text-xs font-medium"
                  :class="categoryClass(word.category)"
                >
                  {{ categories[word.category] || word.category }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-2 py-1 rounded text-xs font-medium"
                  :class="levelClass(word.level)"
                >
                  {{ levels[word.level] || word.level }}
                </span>
              </td>
              <td class="px-6 py-4">
                <button
                  type="button"
                  class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                  :class="word.is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                  @click="toggleWord(word)"
                >
                  {{ word.is_active ? '启用' : '禁用' }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                {{ word.description || '-' }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    @click="editWord(word)"
                  >
                    编辑
                  </button>
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                    @click="deleteWord(word.id)"
                  >
                    删除
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModals">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
          {{ showEditModal ? '编辑敏感词' : '添加敏感词' }}
        </h2>

        <form @submit.prevent="showEditModal ? updateWord() : addWord()">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">敏感词 *</label>
              <input
                v-model="form.word"
                type="text"
                required
                maxlength="100"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="输入敏感词"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">分类</label>
              <select
                v-model="form.category"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              >
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">级别</label>
              <select
                v-model="form.level"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              >
                <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                {{ levelDescription }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">描述</label>
              <textarea
                v-model="form.description"
                rows="2"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="可选的描述说明"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              type="button"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
              @click="closeModals"
            >
              取消
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            >
              {{ showEditModal ? '保存修改' : '添加' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showImportModal = false">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl">
        <h2 class="text-xl font-bold text-gray-900 mb-4">批量导入敏感词</h2>

        <form @submit.prevent="importWords">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">敏感词列表</label>
              <textarea
                v-model="importData.words"
                rows="8"
                required
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="每行一个敏感词，支持批量导入"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">每行一个敏感词，空行和重复词会自动跳过</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">分类</label>
              <select
                v-model="importData.category"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              >
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">级别</label>
              <select
                v-model="importData.level"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              >
                <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              type="button"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
              @click="showImportModal = false"
            >
              取消
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
            >
              开始导入
            </button>
          </div>
        </form>
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

const words = ref([])
const loading = ref(false)
const currentStatus = ref('all')
const filterCategory = ref('')
const filterLevel = ref('')
const searchQuery = ref('')
const meta = ref({ current_page: 1, last_page: 1 })

const showAddModal = ref(false)
const showEditModal = ref(false)
const showImportModal = ref(false)

const form = ref({
  id: null,
  word: '',
  category: 'custom',
  level: 'warning',
  description: '',
})

const importData = ref({
  words: '',
  category: 'custom',
  level: 'warning',
})

const categories = {
  custom: '自定义',
  abuse: '辱骂攻击',
  violence: '暴力血腥',
  porn: '色情低俗',
  politics: '政治敏感',
}

const levels = {
  warning: '警告',
  mute: '禁言',
  ban: '封禁',
}

const statusTabs = [
  { label: '全部', value: 'all' },
  { label: '启用中', value: 'active' },
  { label: '已禁用', value: 'inactive' },
]

const stats = ref({ total: 0, active: 0, inactive: 0, banLevel: 0 })

const levelDescription = computed(() => {
  const descriptions = {
    warning: '检测到后仅记录日志，不执行处罚',
    mute: '检测到后禁言用户 30 分钟',
    ban: '检测到后封禁账号 1 天',
  }
  return descriptions[form.value.level] || ''
})

const categoryClass = (category) => {
  const classes = {
    custom: 'bg-gray-100 text-gray-600',
    abuse: 'bg-red-100 text-red-700',
    violence: 'bg-orange-100 text-orange-700',
    porn: 'bg-purple-100 text-purple-700',
    politics: 'bg-yellow-100 text-yellow-700',
  }
  return classes[category] || 'bg-gray-100 text-gray-600'
}

const levelClass = (level) => {
  const classes = {
    warning: 'bg-gray-100 text-gray-600',
    mute: 'bg-yellow-100 text-yellow-700',
    ban: 'bg-red-100 text-red-700',
  }
  return classes[level] || 'bg-gray-100 text-gray-600'
}

const fetchWords = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page })
    if (currentStatus.value === 'active') params.set('is_active', 'true')
    if (currentStatus.value === 'inactive') params.set('is_active', 'false')
    if (filterCategory.value) params.set('category', filterCategory.value)
    if (filterLevel.value) params.set('level', filterLevel.value)
    if (searchQuery.value) params.set('search', searchQuery.value)

    const response = await apiFetch(`/admin/sensitive-words?${params}`)
    words.value = response.data || []
    if (response.meta) {
      meta.value = response.meta
    }
  } catch (error) {
    console.error('Failed to fetch sensitive words:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await apiFetch('/admin/sensitive-words')
    const allWords = response.data || []
    stats.value = {
      total: allWords.length,
      active: allWords.filter(w => w.is_active).length,
      inactive: allWords.filter(w => !w.is_active).length,
      banLevel: allWords.filter(w => w.level === 'ban' && w.is_active).length,
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

let searchTimer = null
const debouncedSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    fetchWords()
  }, 300)
}

const changePage = (page) => {
  fetchWords(page)
}

const toggleWord = async (word) => {
  try {
    await apiFetch(`/admin/sensitive-words/${word.id}/toggle`, { method: 'POST' })
    word.is_active = !word.is_active
    fetchStats()
  } catch (error) {
    alert('操作失败')
  }
}

const editWord = (word) => {
  form.value = { ...word }
  showEditModal.value = true
}

const deleteWord = async (id) => {
  if (!confirm('确定要删除这个敏感词吗？')) return
  try {
    await apiFetch(`/admin/sensitive-words/${id}`, { method: 'DELETE' })
    words.value = words.value.filter(w => w.id !== id)
    fetchStats()
  } catch (error) {
    alert('删除失败')
  }
}

const addWord = async () => {
  try {
    await apiFetch('/admin/sensitive-words', {
      method: 'POST',
      body: form.value,
    })
    showAddModal.value = false
    resetForm()
    fetchWords()
    fetchStats()
  } catch (error) {
    alert('添加失败')
  }
}

const updateWord = async () => {
  try {
    await apiFetch(`/admin/sensitive-words/${form.value.id}`, {
      method: 'PUT',
      body: form.value,
    })
    showEditModal.value = false
    resetForm()
    fetchWords()
  } catch (error) {
    alert('保存失败')
  }
}

const importWords = async () => {
  try {
    await apiFetch('/admin/sensitive-words/import', {
      method: 'POST',
      body: importData.value,
    })
    showImportModal.value = false
    importData.value = { words: '', category: 'custom', level: 'warning' }
    fetchWords()
    fetchStats()
    alert('导入成功')
  } catch (error) {
    alert('导入失败')
  }
}

const resetForm = () => {
  form.value = {
    id: null,
    word: '',
    category: 'custom',
    level: 'warning',
    description: '',
  }
}

const closeModals = () => {
  showAddModal.value = false
  showEditModal.value = false
  resetForm()
}

onMounted(async () => {
  auth.initAuth()
  await fetchWords()
  await fetchStats()
})
</script>
