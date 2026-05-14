<template>
  <div v-if="theme.transparentMode.value" class="ambient-backdrop transition-colors duration-1000" :class="theme.theme.value === 'dark' ? 'bg-[#030712]' : 'bg-[#f8fafc]'" aria-hidden="true">
    <div v-if="theme.theme.value === 'light'">
      <div class="ambient-grid"></div>
      <div
        v-for="cloud in clouds"
        :key="cloud.id"
        class="ambient-cloud"
        :style="cloud.style"
      ></div>
      <div
        v-for="flake in snowflakes"
        :key="flake.id"
        class="ambient-snowflake"
        :style="flake.style"
      ></div>
    </div>

    <div v-else>
      <div
        v-for="aurora in auroras"
        :key="aurora.id"
        class="ambient-aurora"
        :style="aurora.style"
      ></div>
      <div
        v-for="orb in orbs"
        :key="orb.id"
        class="ambient-orb"
        :style="orb.style"
      ></div>
      <div
        v-for="meteor in meteors"
        :key="meteor.id"
        class="ambient-meteor"
        :style="meteor.style"
      ></div>
    </div>
  </div>
</template>

<script setup>
const theme = useTheme()

const randomBetween = (min, max) => Number((Math.random() * (max - min) + min).toFixed(2))

const clouds = Array.from({ length: 9 }, (_, index) => ({
  id: `cloud-${index}`,
  style: {
    top: `${randomBetween(index < 4 ? 2 : 18, index < 4 ? 16 : 48)}%`,
    left: `${randomBetween(-4, 82)}%`,
    width: `${randomBetween(180, 360)}px`,
    '--cloud-height': `${randomBetween(82, 124)}px`,
    opacity: randomBetween(0.52, 0.88),
    animationDuration: `${randomBetween(20, 32)}s`,
    animationDelay: `${randomBetween(-18, 0)}s`,
  },
}))

const snowflakes = Array.from({ length: 58 }, (_, index) => ({
  id: `snow-${index}`,
  style: {
    left: `${randomBetween(2, 98)}%`,
    animationDuration: `${randomBetween(5.2, 11.6)}s`,
    animationDelay: `${randomBetween(-12, 0)}s`,
    opacity: randomBetween(0.58, 1),
    '--flake-size': `${randomBetween(8, 16)}px`,
    transform: `scale(${randomBetween(0.75, 1.55)})`,
  },
}))

const auroras = [
  {
    id: 'aurora-1',
    style: {
      top: '-12%',
      left: '-6%',
      width: '64vw',
      height: '24vw',
      background: 'linear-gradient(120deg, rgba(14,165,233,0.52), rgba(56,189,248,0.08), rgba(96,165,250,0.3))',
      animationDuration: '15s',
    },
  },
  {
    id: 'aurora-2',
    style: {
      top: '8%',
      right: '-10%',
      width: '54vw',
      height: '20vw',
      background: 'linear-gradient(135deg, rgba(59,130,246,0.34), rgba(15,23,42,0.04), rgba(34,211,238,0.46))',
      animationDuration: '18s',
      animationDelay: '-5s',
    },
  },
  {
    id: 'aurora-3',
    style: {
      bottom: '-8%',
      left: '14%',
      width: '56vw',
      height: '22vw',
      background: 'linear-gradient(135deg, rgba(14,165,233,0.06), rgba(125,211,252,0.28), rgba(37,99,235,0.3))',
      animationDuration: '22s',
      animationDelay: '-9s',
    },
  },
  {
    id: 'aurora-4',
    style: {
      top: '24%',
      left: '22%',
      width: '38vw',
      height: '16vw',
      background: 'linear-gradient(110deg, rgba(15,23,42,0.02), rgba(56,189,248,0.32), rgba(15,23,42,0.03))',
      animationDuration: '20s',
      animationDelay: '-6s',
    },
  },
]

const orbs = Array.from({ length: 22 }, (_, index) => ({
  id: `orb-${index}`,
  style: {
    left: `${randomBetween(4, 96)}%`,
    top: `${randomBetween(36, 100)}%`,
    width: `${randomBetween(8, 26)}px`,
    height: `${randomBetween(8, 26)}px`,
    animationDuration: `${randomBetween(6.5, 13)}s`,
    animationDelay: `${randomBetween(-12, 0)}s`,
  },
}))

const meteors = Array.from({ length: 6 }, (_, index) => ({
  id: `meteor-${index}`,
  style: {
    top: `${randomBetween(8, 42)}%`,
    left: `${randomBetween(54, 100)}%`,
    animationDuration: `${randomBetween(5.4, 8.2)}s`,
    animationDelay: `${randomBetween(-7, 0)}s`,
  },
}))
</script>