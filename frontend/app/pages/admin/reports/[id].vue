<template>
  <div class="admin-page">
    <section class="admin-grid admin-grid--two">
      <article class="admin-card">
        <div class="admin-card__header">
          <div>
            <p class="admin-section-kicker">Case Review</p>
            <h2 class="admin-card__title">举报详情 #{{ report.id || id }}</h2>
            <p class="admin-card__desc">把举报理由、原帖内容和处理动作聚合到同一工作视图里。</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <NuxtLink to="/admin/reports" class="admin-action">返回列表</NuxtLink>
            <NuxtLink v-if="report.post?.id" :to="`/post/${report.post.id}`" class="admin-action admin-action--accent">查看原帖</NuxtLink>
          </div>
        </div>

        <div class="admin-list-stack">
          <article class="admin-list-card">
            <div class="admin-list-card__meta">
              <strong>{{ report.user?.username || '未知用户' }}</strong>
              <small>举报人</small>
            </div>
            <span class="admin-status-pill" :class="pillClass(form.status)">{{ statusLabel(form.status) }}</span>
          </article>
          <article class="admin-list-card">
            <div class="admin-list-card__meta">
              <strong>{{ report.post?.user?.username || '未知作者' }}</strong>
              <small>被举报作者</small>
            </div>
            <span class="admin-status-pill is-review">帖子审核：{{ postModerationLabel }}</span>
          </article>
        </div>
      </article>

      <section class="admin-metric-grid">
        <article class="admin-metric-card">
          <span class="admin-section-kicker">Status</span>
          <strong>{{ statusLabel(form.status) }}</strong>
          <small>当前处理状态</small>
        </article>
        <article class="admin-metric-card">
          <span class="admin-section-kicker">Category</span>
          <strong>{{ report.post?.category?.name || '未分类' }}</strong>
          <small>原帖所属分类</small>
        </article>
        <article class="admin-metric-card">
          <span class="admin-section-kicker">Note</span>
          <strong>{{ form.admin_note ? '已填写' : '待填写' }}</strong>
          <small>审核备注状态</small>
        </article>
        <article class="admin-metric-card">
          <span class="admin-section-kicker">Route</span>
          <strong>{{ report.post?.id ? 'Linked' : 'Missing' }}</strong>
          <small>原帖跳转可用性</small>
        </article>
      </section>
    </section>

    <section v-if="pending" class="admin-table-card">
      <div class="px-6 py-10 text-center text-sm text-slate-400">正在加载举报详情...</div>
    </section>

    <template v-else>
      <div class="admin-grid admin-grid--two">
        <section class="admin-card">
          <div class="admin-card__header">
            <div>
              <p class="admin-section-kicker">Evidence</p>
              <h3 class="admin-card__title">举报理由与原帖内容</h3>
              <p class="admin-card__desc">直接比对举报原因与帖子正文，避免跳出当前视图。</p>
            </div>
          </div>

          <div class="admin-list-stack">
            <article class="admin-list-card">
              <div class="admin-list-card__meta">
                <strong>{{ report.post?.title || '帖子已删除' }}</strong>
                <small>{{ report.reason }}</small>
              </div>
            </article>
          </div>

          <div class="mt-4 rounded-3xl border border-white/10 bg-slate-950/30 p-5 text-sm leading-7 text-slate-200">
            {{ report.post?.content || '帖子已删除或不可用。' }}
          </div>

          <p v-if="report.post?.moderation_note" class="admin-inline-note mt-4">帖子审核备注：{{ report.post.moderation_note }}</p>
        </section>

        <section class="admin-card">
          <div class="admin-card__header">
            <div>
              <p class="admin-section-kicker">Decision</p>
              <h3 class="admin-card__title">审核处理</h3>
              <p class="admin-card__desc">在详情页直接完成状态流转和备注记录。</p>
            </div>
          </div>

          <form class="admin-form-grid" @submit.prevent="submitReview">
            <label class="admin-field">
              <span>处理状态</span>
              <select v-model="form.status" class="admin-field__control">
                <option value="pending">待处理</option>
                <option value="in_review">审核中</option>
                <option value="resolved">已解决</option>
                <option value="rejected">已驳回</option>
              </select>
            </label>

            <label class="admin-field">
              <span>审核备注</span>
              <textarea v-model="form.admin_note" rows="8" class="admin-field__control admin-field__control--textarea" placeholder="记录处理过程、判定理由与后续动作"></textarea>
            </label>

            <p v-if="message" class="admin-inline-note" :class="messageError ? 'is-error' : 'is-success'">{{ message }}</p>

            <div class="admin-form-grid__actions">
              <button type="button" class="admin-action" @click="resetForm">重置</button>
              <button type="submit" class="admin-action admin-action--accent">保存审核结果</button>
            </div>
          </form>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
const route = useRoute()
const id = route.params.id
const { apiBase, getAuthHeaders, extractApiError } = useApi()

const form = reactive({
  status: 'pending',
  admin_note: '',
})
const message = ref('')
const messageError = ref(false)

const { data, pending, refresh } = await useFetch(`${apiBase}/admin/reports/${id}`, {
  server: false,
  headers: computed(() => getAuthHeaders()),
  default: () => ({ data: {} }),
})

const report = computed(() => data.value?.data ?? {})
const postModerationLabel = computed(() => ({
  approved: '已通过',
  pending: '待审核',
  rejected: '已驳回',
}[report.value.post?.moderation_status] || '未知'))

watch(report, (value) => {
  form.status = value.status ?? 'pending'
  form.admin_note = value.admin_note ?? ''
}, { immediate: true })

const statusLabel = (status) => ({
  pending: '待处理',
  in_review: '审核中',
  resolved: '已解决',
  rejected: '已驳回',
}[status] || '未知状态')

const pillClass = (status) => ({
  pending: 'is-pending',
  in_review: 'is-review',
  resolved: 'is-resolved',
  rejected: 'is-rejected',
}[status] || '')

const resetForm = () => {
  form.status = report.value.status ?? 'pending'
  form.admin_note = report.value.admin_note ?? ''
  message.value = ''
  messageError.value = false
}

const submitReview = async () => {
  message.value = ''
  messageError.value = false
  try {
    await $fetch(`${apiBase}/admin/reports/${id}`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: { ...form },
    })
    await refresh()
    message.value = '举报处理结果已更新。'
  } catch (error) {
    messageError.value = true
    message.value = extractApiError(error, '更新失败，请稍后重试')
  }
}
</script>