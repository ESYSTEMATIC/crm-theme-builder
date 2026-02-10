export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const config = useRuntimeConfig()

  return await $fetch(`${config.platformApiUrl}/api/public/properties`, { query })
})
