<template>
  <div :class="[isChannelPage ? 'h-[100dvh]' : 'min-h-screen', 'bg-gray-50 flex flex-col text-gray-800 font-sans transition-colors duration-500', theme.theme.value === 'dark' ? 'theme-dark' : 'theme-light', theme.transparentMode.value ? 'theme-transparent-shell' : '']">
    <EdgeLoadingFrame />
    <div
      v-if="theme.isAnimating.value"
      :class="['theme-sweep', theme.transitionTheme.value === 'dark' ? 'theme-sweep-dark' : 'theme-sweep-light']"
    ></div>
    <AmbientBackdrop />
    <Navbar v-if="!isChannelPage" />
    <AnnouncementBoard v-if="!isChannelPage" />
    <main :class="isChannelPage ? 'relative z-10 flex flex-col flex-grow min-h-0 overflow-hidden' : 'relative z-10 flex-grow container mx-auto px-4 py-8 max-w-5xl'">
      <slot />
    </main>
    <footer v-if="!isChannelPage" class="relative z-10 bg-white border-t border-gray-200 py-6 text-center text-sm text-gray-500">
      &copy; 2026 毕业设计.
    </footer>
  </div>
</template>

<script setup>
const theme = useTheme()
const auth = useAuth()
const route = useRoute()

const isChannelPage = computed(() => route.path === '/channel' || route.path.startsWith('/channel/'))

onMounted(() => {
  theme.initTheme()
  auth.initAuth()
  auth.refreshMe()
})
</script>