<template>
  <div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md">
      <div :class="['loading-frame-shell bg-white/70 backdrop-blur-xl rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 p-8', loading ? 'is-loading' : '']">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">创建账号</h1>
        <p class="text-gray-500 text-sm mb-8">加入 Hostarea 技术社区</p>
        <form class="space-y-2 mt-4" @submit.prevent="handleRegister">
          <FloatingInput v-model="form.username" type="text" label="用户名" />
          <FloatingInput v-model="form.email" type="email" label="邮箱" />
          <FloatingInput v-model="form.password" type="password" label="密码" />
          <FloatingInput v-model="form.passwordConfirmation" type="password" label="确认密码" />
          <p v-if="registerError" class="text-sm text-red-500 mt-2">{{ registerError }}</p>
          <div class="pt-4">
            <button type="submit" :disabled="loading" class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm disabled:opacity-60">
              {{ loading ? '注册中...' : '注册' }}
            </button>
          </div>
        </form>
        <p class="mt-6 text-center text-sm text-gray-500">
          已有账号？
          <NuxtLink to="/login" class="text-blue-600 hover:underline font-medium">立即登录</NuxtLink>
        </p>
      </div>
    </div>

    <AuthPuzzleModal
      v-model:open="puzzleOpen"
      @verified="completeRegister"
      @cancel="handlePuzzleCancel"
    />
  </div>
</template>

<script setup>
import { extractApiError } from '~/composables/useApi.js'

const { register } = useAuth()

const form = reactive({ username: '', email: '', password: '', passwordConfirmation: '' })
const loading       = ref(false)
const registerError = ref('')
const puzzleOpen = ref(false)
const pendingSubmit = ref(false)

const handleRegister = async () => {
  registerError.value = ''

  if (!form.username.trim() || !form.email.trim() || !form.password || !form.passwordConfirmation) {
    registerError.value = '请完整填写注册信息'
    return
  }

  if (form.password !== form.passwordConfirmation) {
    registerError.value = '两次输入的密码不一致'
    return
  }

  pendingSubmit.value = true
  puzzleOpen.value = true
}

const completeRegister = async () => {
  if (!pendingSubmit.value) return
  pendingSubmit.value = false

  loading.value = true
  try {
    await register(form.username, form.email, form.password, form.passwordConfirmation)
    await navigateTo('/')
  } catch (e) {
    const errors = e.data?.errors
    if (errors) {
      registerError.value = Object.values(errors).flat().join('；')
    } else {
      registerError.value = extractApiError(e, '注册失败，请稍后重试')
    }
  } finally {
    loading.value = false
  }
}

const handlePuzzleCancel = () => {
  pendingSubmit.value = false
}
</script>
