<template>
  <div v-if="visible" class="edge-loading-frame" aria-hidden="true">
    <span class="edge-loading-frame__line edge-loading-frame__line--top"></span>
    <span class="edge-loading-frame__line edge-loading-frame__line--right"></span>
    <span class="edge-loading-frame__line edge-loading-frame__line--bottom"></span>
    <span class="edge-loading-frame__line edge-loading-frame__line--left"></span>
  </div>
</template>

<script setup>
const visible = ref(false)
const nuxtApp = useNuxtApp()
let hideTimer = null

const show = () => {
  clearTimeout(hideTimer)
  visible.value = true
}

const hide = () => {
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    visible.value = false
  }, 180)
}

nuxtApp.hook('page:start', show)
nuxtApp.hook('page:finish', hide)
nuxtApp.hook('page:loading:end', hide)

onBeforeUnmount(() => {
  clearTimeout(hideTimer)
})
</script>