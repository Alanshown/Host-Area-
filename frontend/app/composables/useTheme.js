export const useTheme = () => {
  const theme = useState('theme_mode', () => 'light')
  const isAnimating = useState('theme_animating', () => false)
  const transitionTheme = useState('theme_transition_target', () => 'light')
  const transparentMode = useState('theme_transparent_mode', () => false)

  const applyTheme = (value) => {
    theme.value = value

    if (!process.client) return

    document.documentElement.classList.toggle('theme-dark', value === 'dark')
    document.documentElement.dataset.theme = value
    localStorage.setItem('theme_mode', value)
  }

  const applyTransparentMode = (value) => {
    transparentMode.value = value

    if (!process.client) return

    document.documentElement.classList.toggle('scene-transparent', value)
    document.documentElement.dataset.scene = value ? 'transparent' : 'solid'
    localStorage.setItem('theme_transparent_mode', value ? '1' : '0')
  }

  const initTheme = () => {
    if (!process.client) return
    applyTheme(localStorage.getItem('theme_mode') || 'light')
    applyTransparentMode(localStorage.getItem('theme_transparent_mode') === '1')
  }

  const toggleTheme = () => {
    const next = theme.value === 'light' ? 'dark' : 'light'
    transitionTheme.value = next
    isAnimating.value = true

    window.setTimeout(() => applyTheme(next), 140)
    window.setTimeout(() => {
      isAnimating.value = false
    }, 900)
  }

  const toggleTransparentMode = () => {
    applyTransparentMode(!transparentMode.value)
  }

  return {
    theme,
    isAnimating,
    transitionTheme,
    transparentMode,
    initTheme,
    toggleTheme,
    toggleTransparentMode,
    applyTransparentMode,
  }
}