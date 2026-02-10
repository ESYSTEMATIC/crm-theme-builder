/**
 * Server API route to proxy lead form submissions to platform-api.
 * This avoids exposing the internal platform-api URL to the browser.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const config = useRuntimeConfig()

  const response = await $fetch(`${config.platformApiUrl}/api/public/leads`, {
    method: 'POST',
    body,
  })

  return response
})
