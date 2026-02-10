/**
 * Fetches property listings via local Nuxt server route.
 * Works for both SSR and client-side navigation.
 */
export function useProperties(siteId: number | null) {
  return useAsyncData(`properties-${siteId}`, async () => {
    if (!siteId) return { data: [] }

    const result = await $fetch('/api/properties', { query: { site_id: siteId } })
    return result
  })
}

/**
 * Fetches a single property by ID.
 */
export function useProperty(siteId: number | null, propertyId: string | number | null) {
  return useAsyncData(`property-${siteId}-${propertyId}`, async () => {
    if (!siteId || !propertyId) return null

    const result = await $fetch(`/api/properties/${propertyId}`, { query: { site_id: siteId } })
    return result
  })
}
