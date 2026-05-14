export default defineNuxtConfig({
  compatibilityDate: '2026-03-14',
  devServer: {
    host: '0.0.0.0',
    port: 3000,
  },
  modules: [
    "@nuxtjs/tailwindcss"
  ],
  css: [
    "~/assets/css/theme.css"
  ],
  devtools: { enabled: true },
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api'
    }
  }
})
