<template>
  <div class="admin-donut">
    <div class="admin-donut__ring" :style="ringStyle">
      <div class="admin-donut__core">
        <strong>{{ total }}</strong>
        <span>{{ centerLabel }}</span>
      </div>
    </div>

    <div class="admin-donut__legend">
      <div v-for="segment in normalizedSegments" :key="segment.label" class="admin-donut__legend-item">
        <div class="admin-donut__legend-main">
          <i :style="{ background: segment.color }"></i>
          <span>{{ segment.label }}</span>
        </div>
        <strong>{{ segment.value }}</strong>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  segments: {
    type: Array,
    default: () => [],
  },
  centerLabel: {
    type: String,
    default: '总量',
  },
})

const palette = ['#22d3ee', '#38bdf8', '#818cf8', '#f59e0b', '#fb7185', '#34d399']

const normalizedSegments = computed(() => props.segments.map((segment, index) => ({
  ...segment,
  value: Number(segment.value) || 0,
  color: segment.color || palette[index % palette.length],
})))

const total = computed(() => normalizedSegments.value.reduce((sum, segment) => sum + segment.value, 0))

const ringStyle = computed(() => {
  if (!total.value) {
    return {
      background: 'conic-gradient(rgba(148,163,184,0.24) 0deg 360deg)',
    }
  }

  let current = 0
  const stops = normalizedSegments.value.map((segment) => {
    const start = current
    current += (segment.value / total.value) * 360
    return `${segment.color} ${start}deg ${current}deg`
  })

  return {
    background: `conic-gradient(${stops.join(', ')})`,
  }
})
</script>