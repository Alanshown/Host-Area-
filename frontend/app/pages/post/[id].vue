<template>
  <div class="mx-auto max-w-4xl">
    <div class="mb-6 text-sm text-gray-500">
      <NuxtLink to="/" class="transition-colors hover:text-gray-900">首页</NuxtLink>
      <span class="mx-2">/</span>
      <NuxtLink to="/category" class="transition-colors hover:text-gray-900">{{ post.category?.name ?? '分类' }}</NuxtLink>
      <span class="mx-2">/</span>
      <span class="text-gray-900">帖子详情</span>
    </div>

    <article class="mb-8 rounded-2xl border border-gray-200/80 bg-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl md:p-8">
      <div v-if="coverImage" class="mb-6 flex w-full justify-center overflow-hidden rounded-2xl border border-gray-100/50 bg-gray-50/50">
        <img :src="coverImage" alt="cover" class="w-full h-auto max-h-[65vh] object-contain" />
      </div>

      <div class="mb-5 flex items-start justify-between gap-4">
        <div>
          <h1 class="mb-3 text-2xl font-bold leading-snug text-gray-900 md:text-3xl">{{ post.title }}</h1>
          <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
            <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-600">{{ post.category?.name ?? '未分类' }}</span>
            <span>{{ timeAgo(post.created_at) }}</span>
            <span>{{ post.views ?? 0 }} 浏览</span>
            <span>{{ post.comments_count ?? comments.length }} 评论</span>
          </div>
        </div>

        <div v-if="isOwner" class="hidden sm:flex items-center gap-2">
          <NuxtLink :to="`/post/edit/${post.id}`" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">编辑</NuxtLink>
        </div>
      </div>

      <div class="mb-8 flex flex-col gap-4 border-b border-gray-100 pb-6 md:flex-row md:items-center md:justify-between">
        <NuxtLink :to="`/user/${post.user?.id}`" class="flex items-center gap-3">
          <div class="h-12 w-12 overflow-hidden rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 text-white">
            <img v-if="authorAvatar" :src="authorAvatar" alt="author avatar" class="h-full w-full object-cover" />
            <div v-else class="flex h-full w-full items-center justify-center text-base font-bold">{{ author[0]?.toUpperCase() }}</div>
          </div>
          <div>
            <div class="font-medium text-gray-900">{{ author }}</div>
            <div class="text-xs text-gray-500">{{ post.user?.bio || '这个用户还没有留下简介。' }}</div>
          </div>
        </NuxtLink>

        <div class="flex flex-wrap items-center gap-3">
          <button class="flex items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium transition-colors" :class="liked ? 'border-blue-200 bg-blue-50 text-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-blue-600'" @click="toggleLike">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
            <span>{{ liked ? '已点赞' : '点赞' }}</span>
            <span>{{ post.likes ?? 0 }}</span>
          </button>

          <button class="flex items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-medium transition-colors" :class="favorited ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-amber-600'" @click="toggleFavorite">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            <span>{{ favorited ? '已收藏' : '收藏帖子' }}</span>
          </button>

          <button v-if="!isOwner" class="flex items-center gap-2 rounded-full border border-rose-200 px-5 py-2.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50" @click="reportOpen = !reportOpen">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h10l-1 5 3 1-2 4H5V5z"/></svg>
            <span>举报帖子</span>
          </button>
        </div>
      </div>

      <Transition name="announcement-float">
        <div v-if="reportOpen && !isOwner" class="mb-8 rounded-[24px] border border-rose-200/70 bg-rose-50/70 p-5 backdrop-blur-xl">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h3 class="text-base font-semibold text-rose-700">提交举报</h3>
              <p class="mt-1 text-sm text-rose-500">举报会进入管理员处理流，避免重复提交同一帖子。</p>
            </div>
            <button type="button" class="text-sm text-rose-500 hover:text-rose-700" @click="reportOpen = false">收起</button>
          </div>
          <textarea v-model="reportReason" rows="4" class="mt-4 w-full resize-none rounded-2xl border border-rose-200 bg-white/80 px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100" placeholder="请填写举报原因，例如违规内容、恶意引战、广告导流等"></textarea>
          <p v-if="reportMessage" class="mt-2 text-sm" :class="reportError ? 'text-rose-600' : 'text-emerald-600'">{{ reportMessage }}</p>
          <div class="mt-3 flex justify-end">
            <button type="button" :disabled="reportSubmitting" class="rounded-2xl bg-rose-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-rose-500 disabled:opacity-60" @click="submitReport">{{ reportSubmitting ? '提交中...' : '确认举报' }}</button>
          </div>
        </div>
      </Transition>

      <div class="prose max-w-none whitespace-pre-wrap break-words text-gray-700 prose-headings:text-gray-900 prose-p:text-gray-700">{{ post.content }}</div>
    </article>

    <section class="rounded-[24px] border border-gray-200/80 bg-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl md:p-8">
      <h3 class="mb-6 text-lg font-bold text-gray-900">评论（{{ comments.length }}）</h3>

      <div class="mb-8 flex items-start gap-4">
        <div class="h-10 w-10 overflow-hidden rounded-full bg-gray-200">
          <img v-if="myAvatar" :src="myAvatar" alt="my avatar" class="h-full w-full object-cover" />
          <div v-else class="flex h-full w-full items-center justify-center text-sm font-bold text-gray-500">{{ myLetter }}</div>
        </div>
        <div class="flex-1">
          <textarea v-model="newComment" class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 outline-none transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500" rows="3" placeholder="写下你的看法..."></textarea>
          <p v-if="commentError" class="mt-1 text-xs text-red-500">{{ commentError }}</p>
          <div class="mt-2 flex justify-end">
            <button @click="submitComment" :disabled="submitting" class="rounded-md bg-gray-900 px-5 py-2 text-white transition-colors hover:bg-gray-800 disabled:opacity-60">{{ submitting ? '发布中...' : '发表评论' }}</button>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <CommentItem v-for="comment in comments" :key="comment.id" :comment="comment" :postId="id" @replied="onCommentReply" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { timeAgo } from '~/composables/useApi.js'

const route = useRoute()
const auth = useAuth()
const { apiBase, apiFetch, resolveMediaUrl } = useApi()
const id = route.params.id

const { data: postRes, refresh: refreshPost } = await useFetch(`${apiBase}/posts/${id}`)
const { data: commentsRes, refresh: refreshComments } = await useFetch(`${apiBase}/posts/${id}/comments`)

const post = ref(postRes.value?.data ?? {})
watch(postRes, (value) => {
  post.value = value?.data ?? {}
})

const comments = computed(() => commentsRes.value?.data ?? [])
const author = computed(() => post.value.user?.username ?? '未知作者')
const authorAvatar = computed(() => resolveMediaUrl(post.value.user?.avatar))
const coverImage = computed(() => resolveMediaUrl(post.value.cover_image))
const isOwner = computed(() => auth.user.value?.id && post.value.user?.id === auth.user.value.id)

const liked = ref(false)
const favorited = ref(false)
const newComment = ref('')
const submitting = ref(false)
const commentError = ref('')
const reportOpen = ref(false)
const reportReason = ref('')
const reportMessage = ref('')
const reportError = ref(false)
const reportSubmitting = ref(false)

const myLetter = computed(() => (auth.user.value?.username || '?')[0].toUpperCase())
const myAvatar = computed(() => resolveMediaUrl(auth.user.value?.avatar))

const syncInteractions = async () => {
  if (!auth.isLoggedIn.value) {
    liked.value = false
    favorited.value = false
    return
  }

  const [likedRes, favoritedRes] = await Promise.all([
    apiFetch(`/posts/${id}/liked`),
    apiFetch(`/posts/${id}/favorited`),
  ])

  liked.value = !!likedRes.liked
  favorited.value = !!favoritedRes.favorited
}

onMounted(async () => {
  auth.initAuth()
  await syncInteractions()
})

const ensureLogin = async () => {
  if (!auth.isLoggedIn.value) {
    await navigateTo('/login')
    return false
  }

  return true
}

const toggleLike = async () => {
  if (!await ensureLogin()) return

  const res = await apiFetch(`/posts/${id}/like`, { method: 'POST' })
  liked.value = !!res.liked
  post.value.likes = res.likes ?? post.value.likes
}

const toggleFavorite = async () => {
  if (!await ensureLogin()) return

  const res = await apiFetch(`/posts/${id}/favorite`, { method: 'POST' })
  favorited.value = !!res.favorited
}

const submitComment = async () => {
  if (!newComment.value.trim()) return
  if (!await ensureLogin()) return

  commentError.value = ''
  submitting.value = true

  try {
    await apiFetch(`/posts/${id}/comments`, {
      method: 'POST',
      body: { content: newComment.value.trim() },
    })

    newComment.value = ''
    await Promise.all([refreshComments(), refreshPost()])
  } catch (error) {
    commentError.value = error.data?.message || '评论发布失败，请稍后重试'
  } finally {
    submitting.value = false
  }
}

const submitReport = async () => {
  if (!await ensureLogin()) return
  if (!reportReason.value.trim()) {
    reportError.value = true
    reportMessage.value = '请先填写举报原因'
    return
  }

  reportSubmitting.value = true
  reportError.value = false
  reportMessage.value = ''

  try {
    const response = await apiFetch(`/posts/${id}/report`, {
      method: 'POST',
      body: { reason: reportReason.value.trim() },
    })
    reportMessage.value = response.message || '举报已提交'
    reportReason.value = ''
    reportOpen.value = false
  } catch (error) {
    reportError.value = true
    reportMessage.value = error?.data?.message || '举报提交失败，请稍后重试'
  } finally {
    reportSubmitting.value = false
  }
}

const onCommentReply = async () => {
  await Promise.all([refreshComments(), refreshPost()])
}
</script>
