<template>
  <NuxtLink
    ref="brandRef"
    to="/"
    class="interactive-brand"
    @mousemove="updatePointer"
    @mouseleave="resetPointer"
    @click="triggerPulse(activeIndex)"
  >
    <span class="interactive-brand__halo" aria-hidden="true"></span>
    <span class="interactive-brand__word" aria-label="Hostarea">
      <span
        v-for="(letter, index) in letters"
        :key="`${letter}-${index}`"
        class="interactive-brand__letter"
        :class="{
          'is-active': isNearActive(index),
          'is-pulsed': pulseIndex === index,
        }"
        :style="letterStyle(index)"
        @mouseenter="setActiveIndex(index)"
        @click.stop="triggerPulse(index)"
      >
        <span class="interactive-brand__letter-base">{{ letter }}</span>
        <span class="interactive-brand__letter-fx" aria-hidden="true">{{ letter }}</span>
      </span>
    </span>
  </NuxtLink>
</template>

<script setup>
const brandRef = ref(null)
const activeIndex = ref(4)
const pulseIndex = ref(-1)
let pulseTimer = null

const letters = 'Hostarea'.split('')

const getBrandElement = () => {
  const target = brandRef.value?.$el ?? brandRef.value
  return target instanceof HTMLElement ? target : null
}

const setActiveIndex = (index) => {
  activeIndex.value = index
}

const updatePointer = (event) => {
  const element = getBrandElement()

  if (!element) return

  const rect = element.getBoundingClientRect()
  const x = ((event.clientX - rect.left) / rect.width) * 100
  const y = ((event.clientY - rect.top) / rect.height) * 100
  const nextIndex = Math.max(0, Math.min(letters.length - 1, Math.floor((event.clientX - rect.left) / (rect.width / letters.length))))

  element.style.setProperty('--mx', `${x}%`)
  element.style.setProperty('--my', `${y}%`)
  activeIndex.value = nextIndex
}

const resetPointer = () => {
  const element = getBrandElement()

  if (!element) return

  element.style.setProperty('--mx', '50%')
  element.style.setProperty('--my', '50%')
  activeIndex.value = 4
}

const isNearActive = (index) => Math.abs(index - activeIndex.value) <= 1

const letterStyle = (index) => {
  const distance = Math.abs(index - activeIndex.value)
  const intensity = Math.max(0, 1 - distance * 0.32)

  return {
    '--letter-intensity': intensity.toFixed(2),
    '--letter-offset': `${(index - activeIndex.value) * 2.5}px`,
    '--letter-delay': `${index * 24}ms`,
  }
}

const triggerPulse = (index) => {
  clearTimeout(pulseTimer)
  pulseIndex.value = index
  pulseTimer = window.setTimeout(() => {
    pulseIndex.value = -1
  }, 420)
}

onBeforeUnmount(() => {
  clearTimeout(pulseTimer)
})
</script>