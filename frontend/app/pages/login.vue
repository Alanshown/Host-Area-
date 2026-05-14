<template>
  <div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md">
      <div :class="['loading-frame-shell bg-white/70 backdrop-blur-xl rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 p-8', loading ? 'is-loading' : '']">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">欢迎回来</h1>
        <p class="text-gray-500 text-sm mb-8">登录您的 Hostarea 账号</p>
        <form class="space-y-4 mt-4" @submit.prevent="handleLogin">
          <FloatingInput v-model="form.email" type="email" label="邮箱" />
          <FloatingInput v-model="form.password" type="password" label="密码" />
          <div class="flex justify-end">
            <NuxtLink to="/forgot-password" class="text-sm text-blue-600 hover:underline font-medium">忘记密码？</NuxtLink>
          </div>
          <p v-if="loginError" class="text-sm text-red-500 mt-2">{{ loginError }}</p>
          <div class="pt-4">
            <button type="submit" :disabled="loading" class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm disabled:opacity-60">
              {{ loading ? '登录中...' : '登录' }}
            </button>
          </div>
        </form>
        <p class="mt-6 text-center text-sm text-gray-500">
          还没有账号？
          <NuxtLink to="/register" class="text-blue-600 hover:underline font-medium">立即注册</NuxtLink>
        </p>
      </div>
    </div>

    <AuthPuzzleModal
      v-model:open="puzzleOpen"
      @verified="completeLogin"
      @cancel="handlePuzzleCancel"
    />
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const { login } = useAuth()

const form = reactive({ email: '', password: '' })
const loading    = ref(false)
const loginError = ref('')
const puzzleOpen = ref(false)
const pendingSubmit = ref(false)

const handleLogin = async () => {
  loginError.value = ''

  if (!form.email.trim() || !form.password) {
    loginError.value = '请输入邮箱和密码'
    return
  }

  pendingSubmit.value = true
  puzzleOpen.value = true
}

const completeLogin = async () => {
  if (!pendingSubmit.value) return
  pendingSubmit.value = false

  loading.value = true
  try {
    await login(form.email, form.password)
    await navigateTo('/')
  } catch (e) {
    loginError.value = extractApiError(e, '登录失败，请检查邮箱和密码')
  } finally {
    loading.value = false
  }
}

const handlePuzzleCancel = () => {
  pendingSubmit.value = false
}
</script>
