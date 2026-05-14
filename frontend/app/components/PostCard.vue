<template>
  <div class="bg-white/70 border border-gray-200/80 rounded-2xl p-5 mb-4 shadow-[0_4px_20px_rgb(0,0,0,0.03)] backdrop-blur-md hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-blue-100 transition-all cursor-pointer duration-300 group hover:-translate-y-1">
    <div class="flex items-center space-x-3 mb-4">
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex-shrink-0 flex items-center justify-center text-white text-sm font-bold overflow-hidden ring-2 ring-white/50 shadow-sm">
        <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
        <span v-else>{{ username[0]?.toUpperCase() }}</span>
      </div>
      <div class="flex-1">
        <div class="text-sm font-semibold text-gray-900 leading-none mb-1">{{ username }}</div>
        <div class="text-xs text-gray-500 font-medium">{{ displayTime }}</div>
      </div>
      <div class="px-3 py-1 bg-gradient-to-r from-blue-50 to-indigo-50/50 border border-blue-100/50 text-[11px] text-blue-600 rounded-full font-semibold shadow-sm"># {{ categoryName }}</div>
    </div>

    <!-- 封面图 -->
    <div v-if="coverUrl" class="mb-4 flex w-full items-center justify-center overflow-hidden rounded-xl border border-gray-100/50 bg-gray-50/50">
      <img :src="coverUrl" alt="cover" class="w-full h-auto max-h-72 object-contain" />
    </div>

    <h2 class="text-base font-bold text-gray-900 group-hover:text-blue-600 transition-colors mb-2 line-clamp-2 leading-snug">
      {{ post.title }}
    </h2>
    <p class="text-gray-500 text-sm line-clamp-2 mb-4 leading-relaxed">
      {{ post.content }}
    </p>

    <div class="flex items-center text-sm text-gray-400 space-x-5 border-t border-gray-50 pt-3">
      <button
        @click.prevent.stop="toggleLike"
        class="flex items-center transition-colors cursor-pointer"
        :class="isLiked ? 'text-blue-500' : 'hover:text-blue-500'"
      >
        <svg class="w-4 h-4 mr-1.5" :fill="isLiked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
        <span>{{ localLikes }}</span>
      </button>

      <button
        @click.prevent.stop="toggleFavorite"
        class="flex items-center transition-colors cursor-pointer"
        :class="isFavorited ? 'text-amber-500' : 'hover:text-amber-500'"
      >
        <svg class="w-4 h-4 mr-1.5" :fill="isFavorited ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
        <span>{{ isFavorited ? '已收藏' : '收藏' }}</span>
      </button>

      <div class="flex items-center">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span>{{ post.comments_count ?? 0 }}</span>
      </div>
      <div class="flex items-center">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <span>{{ post.views ?? 0 }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { timeAgo } from '~/composables/useApi.js'

const props = defineProps({
  post:        { type: Object, default: () => ({}) },
  liked:       { type: Boolean, default: false },
  favorited:   { type: Boolean, default: false },
})

const emit = defineEmits(['like-toggled', 'favorite-toggled'])

const { resolveMediaUrl, apiFetch } = useApi()

const username     = computed(() => props.post.user?.username ?? props.post.username ?? 'Unknown')
const categoryName = computed(() => props.post.category?.name ?? props.post.category ?? '未分类')
const displayTime  = computed(() => timeAgo(props.post.created_at))
const avatarUrl    = computed(() => resolveMediaUrl(props.post.user?.avatar ?? props.post.avatar))
const coverUrl     = computed(() => resolveMediaUrl(props.post.cover_image))

const isLiked     = ref(props.liked)
const isFavorited = ref(props.favorited)
const localLikes  = ref(props.post.likes ?? 0)

watch(() => props.liked, (v) => { isLiked.value = v })
watch(() => props.favorited, (v) => { isFavorited.value = v })
watch(() => props.post.likes, (v) => { localLikes.value = v ?? 0 })

const toggleLike = async () => {
  const auth = useAuth()
  if (!auth.isLoggedIn.value) { await navigateTo('/login'); return }
  try {
    const res = await apiFetch(`/posts/${props.post.id}/like`, { method: 'POST' })
    isLiked.value = !!res.liked
    localLikes.value = res.likes ?? localLikes.value
    emit('like-toggled', { postId: props.post.id, liked: isLiked.value, likes: localLikes.value })
  } catch {}
}

const toggleFavorite = async () => {
  const auth = useAuth()
  if (!auth.isLoggedIn.value) { await navigateTo('/login'); return }
  try {
    const res = await apiFetch(`/posts/${props.post.id}/favorite`, { method: 'POST' })
    isFavorited.value = !!res.favorited
    emit('favorite-toggled', { postId: props.post.id, favorited: isFavorited.value })
  } catch {}
}
</script>