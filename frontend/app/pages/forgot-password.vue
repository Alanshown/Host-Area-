<template>
  <div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md">
      <div :class="['loading-frame-shell bg-white/70 backdrop-blur-xl rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 p-8', loading ? 'is-loading' : '']">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">重置密码</h1>
        <p class="text-gray-500 text-sm mb-8">输入您的用户名验证并修改密码</p>
        <form class="space-y-4 mt-4" @submit.prevent="handleReset">
          <FloatingInput v-model="form.username" type="text" label="用户名" />
          <FloatingInput v-model="form.password" type="password" label="新密码" />
          <FloatingInput v-model="form.password_confirmation" type="password" label="确认新密码" />
          <p v-if="resetError" class="text-sm text-red-500 mt-2">{{ resetError }}</p>
          <div class="pt-4">
            <button type="submit" :disabled="loading" class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm disabled:opacity-60">
              {{ loading ? '处理中...' : '重置密码' }}
            </button>
          </div>
        </form>
        <p class="mt-6 text-center text-sm text-gray-500">
          记起来了？
          <NuxtLink to="/login" class="text-blue-600 hover:underline font-medium">返回登录</NuxtLink>
        </p>
      </div>
    </div>

    <!-- 3次九宫格验证集成 -->
    <AuthPuzzleModal
      v-model:open="puzzleOpen"
      @verified="completeReset"
      @cancel="handlePuzzleCancel"
    />
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const { fetchApi } = useApi()

const form = reactive({ username: '', password: '', password_confirmation: '' })
const loading = ref(false)
const resetError = ref('')
const puzzleOpen = ref(false)
const pendingSubmit = ref(false)

const handleReset = async () => {
  resetError.value = ''

  if (!form.username.trim() || !form.password || !form.password_confirmation) {
    resetError.value = '请填写完整信息'
    return
  }
  if (form.password.length < 8) {
    resetError.value = '密码长度不能少于 8 位'
    return
  }
  if (form.password !== form.password_confirmation) {
    resetError.value = '两次密码输入不一致'
    return
  }

  // 触发九宫格验证流程
  pendingSubmit.value = true
  puzzleOpen.value = true
}

const completeReset = async () => {
  if (!pendingSubmit.value) return
  pendingSubmit.value = false

  loading.value = true
  try {
    await fetchApi('/auth/reset-password', {
      method: 'POST',
      body: form
    })
    alert('密码重置成功，请重新登录！')
    await navigateTo('/login')
  } catch (e) {
    resetError.value = extractApiError(e, '重置失败，请核实用户名')
  } finally {
    loading.value = false
  }
}

const handlePuzzleCancel = () => {
  pendingSubmit.value = false
}
</script>