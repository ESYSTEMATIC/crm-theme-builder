export default defineNuxtConfig({
  ssr: true,
  devtools: { enabled: false },

  runtimeConfig: {
    platformApiUrl: process.env.NUXT_PLATFORM_API_URL || 'http://platform-api:8000',
  },

  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1',
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap' },
      ],
    },
  },

  css: ['~/assets/css/theme.css'],

  compatibilityDate: '2025-01-01',
})
