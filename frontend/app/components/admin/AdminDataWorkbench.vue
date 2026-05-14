<template>
  <section class="admin-card admin-workbench-panel">
    <div class="admin-card__header admin-card__header--stacked">
      <div>
        <p class="admin-section-kicker">Database Workbench</p>
        <h2 class="admin-card__title">快捷数据库修改</h2>
        <p class="admin-card__desc">按实体筛选记录，直接修改受控字段，无需离开后台。</p>
      </div>

      <div class="admin-workbench-panel__toolbar">
        <div class="admin-chip-group">
          <button
            v-for="item in entities"
            :key="item.value"
            type="button"
            class="admin-chip"
            :class="{ 'is-active': currentEntity === item.value }"
            @click="changeEntity(item.value)"
          >
            {{ item.label }}
          </button>
        </div>

        <div class="admin-searchbar">
          <input v-model="keywordDraft" type="text" class="admin-searchbar__input" :placeholder="`搜索${currentEntityLabel}`" @keydown.enter.prevent="applySearch" />
          <button type="button" class="admin-searchbar__button" @click="applySearch">搜索</button>
        </div>
      </div>
    </div>

    <div class="admin-workbench-grid">
      <aside class="admin-workbench-list">
        <div v-if="loading" class="admin-empty-state">正在读取 {{ currentEntityLabel }} 数据...</div>
        <div v-else-if="!records.length" class="admin-empty-state">当前筛选条件下没有记录。</div>
        <button
          v-for="record in records"
          v-else
          :key="record.id"
          type="button"
          class="admin-record-card"
          :class="{ 'is-active': selectedId === record.id }"
          @click="selectRecord(record)"
        >
          <div class="admin-record-card__top">
            <strong>{{ recordTitle(record) }}</strong>
            <span>#{{ record.id }}</span>
          </div>
          <p>{{ recordSubtitle(record) }}</p>
          <small>{{ recordMeta(record) }}</small>
        </button>

        <div class="admin-workbench-list__pager">
          <button type="button" class="admin-action" :disabled="page <= 1 || loading" @click="page -= 1">上一页</button>
          <span>第 {{ page }} / {{ lastPage }} 页</span>
          <button type="button" class="admin-action" :disabled="page >= lastPage || loading" @click="page += 1">下一页</button>
        </div>
      </aside>

      <section class="admin-workbench-editor">
        <div v-if="selectedRecord" class="admin-workbench-editor__inner">
          <div class="admin-workbench-editor__header">
            <div>
              <p class="admin-section-kicker">Editing</p>
              <h3>{{ recordTitle(selectedRecord) }}</h3>
              <p>{{ recordSubtitle(selectedRecord) }}</p>
            </div>
            <span class="admin-badge">{{ currentEntityLabel }} #{{ selectedRecord.id }}</span>
          </div>

          <form class="admin-form-grid" @submit.prevent="saveRecord">
            <label v-for="field in fields" :key="field.key" class="admin-field">
              <span>{{ field.label }}</span>

              <textarea
                v-if="field.type === 'textarea'"
                v-model="formData[field.key]"
                rows="4"
                class="admin-field__control admin-field__control--textarea"
              ></textarea>

              <select
                v-else-if="field.type === 'select'"
                v-model="formData[field.key]"
                class="admin-field__control"
              >
                <option v-for="option in field.options || []" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>

              <label v-else-if="field.type === 'checkbox'" class="admin-field__checkbox">
                <input v-model="formData[field.key]" type="checkbox" />
                <span>启用此项</span>
              </label>

              <input
                v-else
                v-model="formData[field.key]"
                :type="field.type"
                class="admin-field__control"
              />
            </label>

            <p v-if="message" class="admin-inline-note" :class="saveError ? 'is-error' : 'is-success'">{{ message }}</p>

            <div class="admin-form-grid__actions">
              <button type="button" class="admin-action" @click="syncForm(selectedRecord)">重置</button>
              <button type="submit" class="admin-action admin-action--accent" :disabled="saving">{{ saving ? '保存中...' : '保存修改' }}</button>
            </div>
          </form>
        </div>

        <div v-else class="admin-empty-state admin-empty-state--editor">从左侧选择一条记录开始编辑。</div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const { apiBase, getAuthHeaders } = useApi()

const entities = [
  { value: 'users', label: '用户' },
  { value: 'posts', label: '帖子' },
  { value: 'comments', label: '评论' },
  { value: 'announcements', label: '公告' },
  { value: 'reports', label: '举报' },
]

const currentEntity = ref('users')
const keywordDraft = ref('')
const keyword = ref('')
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const saveError = ref(false)
const fields = ref([])
const records = ref([])
const selectedId = ref(null)
const page = ref(1)
const lastPage = ref(1)
const formData = ref({})

const currentEntityLabel = computed(() => entities.find((item) => item.value === currentEntity.value)?.label || '记录')
const selectedRecord = computed(() => records.value.find((item) => Number(item.id) === Number(selectedId.value)) || null)

const normalizeForField = (value, field) => {
  if (field.type === 'checkbox') return Boolean(value)
  if (field.type === 'datetime-local') {
    if (!value) return ''
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''
    return new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16)
  }
  return value ?? ''
}

const serializeForField = (value, field) => {
  if (field.type === 'checkbox') return Boolean(value)
  if (field.type === 'number') return value === '' ? null : Number(value)
  if (field.type === 'datetime-local') return value || null
  return value
}

const syncForm = (record) => {
  const next = {}
  for (const field of fields.value) {
    next[field.key] = normalizeForField(record?.[field.key], field)
  }
  formData.value = next
}

const selectRecord = (record) => {
  selectedId.value = record.id
  syncForm(record)
  message.value = ''
}

const fetchRecords = async () => {
  loading.value = true
  message.value = ''
  try {
    const response = await $fetch(`${apiBase}/admin/database/records`, {
      headers: getAuthHeaders(),
      query: {
        entity: currentEntity.value,
        keyword: keyword.value,
        page: page.value,
      },
    })

    const payload = response.data || {}
    fields.value = payload.fields || []
    records.value = payload.records?.data || []
    lastPage.value = payload.records?.last_page || 1

    const nextSelected = records.value.find((item) => Number(item.id) === Number(selectedId.value)) || records.value[0] || null
    selectedId.value = nextSelected?.id ?? null
    syncForm(nextSelected)
  } catch (error) {
    records.value = []
    selectedId.value = null
    message.value = extractApiError(error, '数据库工作台加载失败')
    saveError.value = true
  } finally {
    loading.value = false
  }
}

const applySearch = async () => {
  keyword.value = keywordDraft.value.trim()
  page.value = 1
  await fetchRecords()
}

const changeEntity = async (entity) => {
  if (currentEntity.value === entity) return
  currentEntity.value = entity
  keywordDraft.value = ''
  keyword.value = ''
  page.value = 1
  await fetchRecords()
}

const saveRecord = async () => {
  if (!selectedId.value) return

  saving.value = true
  message.value = ''
  saveError.value = false

  try {
    const body = fields.value.reduce((payload, field) => {
      payload[field.key] = serializeForField(formData.value[field.key], field)
      return payload
    }, {})

    const response = await $fetch(`${apiBase}/admin/database/records/${currentEntity.value}/${selectedId.value}`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body,
    })

    const record = response.data?.record
    if (record) {
      records.value = records.value.map((item) => Number(item.id) === Number(record.id) ? record : item)
      selectRecord(record)
    }

    message.value = '数据库记录已更新。'
  } catch (error) {
    saveError.value = true
    message.value = extractApiError(error, '保存失败，请检查输入内容')
  } finally {
    saving.value = false
  }
}

const recordTitle = (record) => {
  if (!record) return ''
  if (currentEntity.value === 'users') return record.username || `用户 #${record.id}`
  if (currentEntity.value === 'posts') return record.title || `帖子 #${record.id}`
  if (currentEntity.value === 'comments') return (record.content || '').slice(0, 18) || `评论 #${record.id}`
  if (currentEntity.value === 'announcements') return record.title || `公告 #${record.id}`
  if (currentEntity.value === 'reports') return `举报 #${record.id}`
  return `记录 #${record.id}`
}

const recordSubtitle = (record) => {
  if (!record) return ''
  if (currentEntity.value === 'users') return `${record.email || '无邮箱'} · ${record.role || 'user'}`
  if (currentEntity.value === 'posts') return `${record.user?.username || '未知作者'} · ${record.category?.name || '未分类'}`
  if (currentEntity.value === 'comments') return `${record.user?.username || '未知用户'} · ${record.post?.title || '无所属帖子'}`
  if (currentEntity.value === 'announcements') return record.body || '暂无公告摘要'
  if (currentEntity.value === 'reports') return record.reason || '暂无举报说明'
  return ''
}

const recordMeta = (record) => {
  if (!record?.created_at) return '无时间信息'
  return new Date(record.created_at).toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

watch(page, async () => {
  await fetchRecords()
})

onMounted(async () => {
  await fetchRecords()
})
</script>