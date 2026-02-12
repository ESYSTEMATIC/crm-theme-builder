/**
 * Fetches the full site payload from platform-api.
 * Called server-side during SSR — reads site context from event headers.
 */
export function useSiteData() {
  return useAsyncData('site-data', async (nuxtApp) => {
    const event = useRequestEvent()
    const siteCtx = event?.context?.site

    if (!siteCtx?.id) {
      throw createError({ statusCode: 404, statusMessage: 'No site context' })
    }

    const config = useRuntimeConfig()
    const apiUrl = config.platformApiUrl

    const url = `${apiUrl}/api/internal/site-payload/${siteCtx.id}`
    const data = await $fetch(url)
    return data
  }, {
    getCachedData: (key, nuxtApp) => nuxtApp.payload.data[key] || nuxtApp.static.data[key],
  })
}
