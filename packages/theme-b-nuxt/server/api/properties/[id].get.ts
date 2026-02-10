export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')
  const query = getQuery(event)
  const config = useRuntimeConfig()

  return await $fetch(`${config.platformApiUrl}/api/public/properties/${id}`, { query })
})
