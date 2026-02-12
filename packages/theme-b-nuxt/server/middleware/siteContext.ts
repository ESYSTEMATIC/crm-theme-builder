/**
 * Server middleware that reads site context headers set by the site-gateway
 * and stores them on the event context for use in page rendering.
 */
export default defineEventHandler((event) => {
  const headers = getRequestHeaders(event)

  event.context.site = {
    id: headers['x-site-id'] ? parseInt(headers['x-site-id'], 10) : null,
    tenantId: headers['x-tenant-id'] || null,
    themeKey: headers['x-theme-key'] || null,
    slug: headers['x-site-slug'] || null,
  }
})
