<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
      <p class="text-7xl font-black text-gray-200 mb-4">{{ error?.statusCode ?? 404 }}</p>
      <h1 class="text-xl font-bold text-gray-800 mb-2">
        {{ error?.statusCode === 500 ? '服务器出错了' : '页面不存在' }}
      </h1>
      <p class="text-sm text-gray-500 mb-8">
        {{ error?.message || '抱歉，您访问的页面不存在或已被删除。' }}
      </p>
      <div class="flex items-center justify-center gap-3">
        <button
          @click="handleError"
          class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
        >
          返回首页
        </button>
        <button
          @click="$router.back()"
          class="border border-gray-300 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-50 transition-colors text-sm"
        >
          返回上一页
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ error: Object })

const handleError = () => clearError({ redirect: '/' })
</script>
