<template>
  <div class="admin-line-chart">
    <div class="admin-line-chart__legend">
      <span v-for="series in normalizedSeries" :key="series.name" class="admin-line-chart__legend-item">
        <i :style="{ background: series.color }"></i>
        {{ series.name }}
      </span>
    </div>

    <div class="admin-line-chart__canvas">
      <svg viewBox="0 0 100 56" preserveAspectRatio="none" class="admin-line-chart__svg" aria-hidden="true">
        <line v-for="grid in 5" :key="grid" x1="0" :y1="gridY(grid)" x2="100" :y2="gridY(grid)" class="admin-line-chart__grid" />
        <g v-for="series in normalizedSeries" :key="`${series.name}-shape`">
          <path :d="series.areaPath" :fill="series.fill || `${series.color}22`" />
          <path :d="series.linePath" :stroke="series.color" class="admin-line-chart__path" />
        </g>
      </svg>
    </div>

    <div class="admin-line-chart__labels">
      <span v-for="label in labels" :key="label">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  labels: {
    type: Array,
    default: () => [],
  },
  series: {
    type: Array,
    default: () => [],
  },
})

const maxValue = computed(() => {
  const values = props.series.flatMap((item) => item.values || [])
  return Math.max(...values.map((value) => Number(value) || 0), 1)
})

const buildPointString = (values) => {
  const total = Math.max(values.length - 1, 1)
  return values.map((value, index) => {
    const x = (index / total) * 100
    const y = 50 - ((Number(value) || 0) / maxValue.value) * 42
    return `${x},${Math.max(6, y)}`
  }).join(' ')
}

const buildAreaPath = (values) => {
  const points = buildPointString(values).split(' ')
  if (!points.length) return 'M0,50 L100,50 Z'
  return `M${points[0]} L${points.slice(1).join(' L ')} L100,52 L0,52 Z`
}

const normalizedSeries = computed(() => props.series.map((item) => ({
  ...item,
  color: item.color || '#34d399',
  linePath: `M${buildPointString(item.values || []).replace(/ /g, ' L')}`,
  areaPath: buildAreaPath(item.values || []),
})))

const gridY = (grid) => 6 + ((grid - 1) * 11.5)
</script>