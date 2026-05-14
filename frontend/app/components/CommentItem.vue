<template>
  <div class="flex space-x-4">
    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex-shrink-0 mt-1 flex items-center justify-center text-white text-sm font-bold overflow-hidden">
      <img v-if="avatarUrl" :src="avatarUrl" alt="avatar" class="h-full w-full object-cover" />
      <span v-else>{{ username[0]?.toUpperCase() }}</span>
    </div>
    <div class="flex-1">
      <div class="bg-gray-50 p-4 rounded-xl rounded-tl-none border border-gray-100">
        <div class="flex items-center justify-between mb-2">
          <span class="font-medium text-gray-900 text-sm">{{ username }}</span>
          <span class="text-xs text-gray-500">{{ displayTime }}</span>
        </div>
        <p class="text-gray-700 text-sm leading-relaxed">{{ comment.content }}</p>
      </div>
      <!-- 子回复 -->
      <div v-if="comment.replies?.length" class="mt-3 ml-4 space-y-3">
        <div v-for="reply in comment.replies" :key="reply.id" class="flex space-x-3">
          <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-400 to-indigo-400 flex-shrink-0 mt-0.5 flex items-center justify-center text-white text-xs font-bold overflow-hidden">
            <img v-if="resolveMediaUrl(reply.user?.avatar)" :src="resolveMediaUrl(reply.user?.avatar)" alt="reply avatar" class="h-full w-full object-cover" />
            <span v-else>{{ reply.user?.username?.[0]?.toUpperCase() }}</span>
          </div>
          <div class="flex-1 bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 text-sm">
            <span class="font-medium text-gray-900 mr-2">{{ reply.user?.username }}</span>
            <span class="text-gray-600">{{ reply.content }}</span>
            <div class="text-xs text-gray-400 mt-1">{{ timeAgo(reply.created_at) }}</div>
          </div>
        </div>
      </div>
      <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 ml-1">
        <button class="hover:text-blue-600 transition-colors" @click="showReply = !showReply">{{ showReply ? '取消回复' : '回复' }}</button>
      </div>
      <!-- 回复输入框 -->
      <div v-if="showReply" class="mt-3 ml-4 flex items-start gap-3">
        <textarea
          v-model="replyContent"
          rows="2"
          class="flex-1 resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
          :placeholder="`回复 ${username}...`"
        ></textarea>
        <button
          @click="submitReply"
          :disabled="replying"
          class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white transition-colors hover:bg-gray-800 disabled:opacity-60"
        >{{ replying ? '...' : '发送' }}</button>
      </div>
      <p v-if="replyError" class="mt-1 ml-4 text-xs text-red-500">{{ replyError }}</p>
    </div>
  </div>
</template>

<script setup>
import { timeAgo } from '~/composables/useApi.js'

const props = defineProps({
  comment: { type: Object, default: () => ({}) },
  postId:  { type: [String, Number], required: true },
})

const emit = defineEmits(['replied'])

const { resolveMediaUrl, apiFetch } = useApi()

const username    = computed(() => props.comment.user?.username ?? '热心网友')
const displayTime = computed(() => timeAgo(props.comment.created_at))
const avatarUrl   = computed(() => resolveMediaUrl(props.comment.user?.avatar))

const showReply    = ref(false)
const replyContent = ref('')
const replying     = ref(false)
const replyError   = ref('')

const submitReply = async () => {
  if (!replyContent.value.trim()) return
  const auth = useAuth()
  if (!auth.isLoggedIn.value) { await navigateTo('/login'); return }

  replying.value = true
  replyError.value = ''
  try {
    await apiFetch(`/posts/${props.postId}/comments`, {
      method: 'POST',
      body: { content: replyContent.value.trim(), parent_id: props.comment.id },
    })
    replyContent.value = ''
    showReply.value = false
    emit('replied')
  } catch (e) {
    replyError.value = e.data?.message || '回复失败'
  } finally {
    replying.value = false
  }
}
</script>