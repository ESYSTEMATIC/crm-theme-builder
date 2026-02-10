import axios from 'axios'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

/**
 * Fetch all available themes.
 * GET /api/themes
 */
export function getThemes() {
  return client.get('/api/themes').then((res) => res.data)
}

/**
 * Create a new site.
 * POST /api/sites
 */
export function createSite(data) {
  return client.post('/api/sites', data).then((res) => res.data)
}

/**
 * Fetch a site by ID.
 * GET /api/sites/{id}
 */
export function getSite(id) {
  return client.get(`/api/sites/${id}`).then((res) => res.data)
}

/**
 * Fetch the current draft for a site.
 * GET /api/sites/{siteId}/draft
 */
export function getDraft(siteId) {
  return client.get(`/api/sites/${siteId}/draft`).then((res) => res.data)
}

/**
 * Save (update) the draft payload for a site.
 * PUT /api/sites/{siteId}/draft
 */
export function saveDraft(siteId, payloadJson) {
  return client
    .put(`/api/sites/${siteId}/draft`, { payload_json: payloadJson })
    .then((res) => res.data)
}

/**
 * Create a preview session for a site.
 * POST /api/sites/{siteId}/preview-session
 */
export function createPreviewSession(siteId) {
  return client
    .post(`/api/sites/${siteId}/preview-session`)
    .then((res) => res.data)
}

/**
 * Publish a site.
 * POST /api/sites/{siteId}/publish
 */
export function publishSite(siteId) {
  return client.post(`/api/sites/${siteId}/publish`).then((res) => res.data)
}

export default client
