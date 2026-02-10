import Redis from 'ioredis'
import mysql from 'mysql2/promise'

const CACHE_TTL = 300 // 5 minutes
const PREVIEW_CACHE_TTL = 60 // 1 minute

let redis
let db

export function initResolveHost({ redisHost, redisPort, dbHost, dbPort, dbUser, dbPassword, dbName }) {
  redis = new Redis({ host: redisHost, port: redisPort || 6379, lazyConnect: true })
  redis.connect().catch(() => {})

  db = mysql.createPool({
    host: dbHost,
    port: dbPort || 3306,
    user: dbUser || 'root',
    password: dbPassword || 'secret',
    database: dbName || 'microsite_platform',
    waitForConnections: true,
    connectionLimit: 10,
  })
}

/**
 * Resolve a hostname to site data.
 * Mirrors HostSiteResolver.php logic: custom domain first, then slug wildcard.
 */
export async function resolveHost(host, platformDomain) {
  const cacheKey = `site_host:${host}`

  // Try Redis cache first
  try {
    const cached = await redis.get(cacheKey)
    if (cached) return JSON.parse(cached)
  } catch {}

  let siteData = null

  // 1. Try exact custom domain match
  const [domainRows] = await db.query(
    'SELECT site_id FROM site_domains WHERE host = ? AND status = ?',
    [host, 'verified']
  )
  if (domainRows.length > 0) {
    siteData = await loadSiteData(domainRows[0].site_id)
  }

  // 2. Try slug from platform wildcard
  if (!siteData) {
    const slug = parseSlugFromHost(host, platformDomain)
    if (slug) {
      const [siteRows] = await db.query('SELECT * FROM sites WHERE slug = ?', [slug])
      if (siteRows.length > 0) {
        siteData = await buildSiteData(siteRows[0])
      }
    }
  }

  // Cache result (even null to avoid repeated misses)
  try {
    if (siteData) {
      await redis.setex(cacheKey, CACHE_TTL, JSON.stringify(siteData))
    }
  } catch {}

  return siteData
}

/**
 * Parse slug from hostname like {slug}.crmwebsite.com
 */
function parseSlugFromHost(host, platformDomain) {
  const suffix = '.' + platformDomain
  if (host.endsWith(suffix)) {
    const slug = host.slice(0, -suffix.length)
    // No dots = not a subdomain-of-subdomain
    if (slug && !slug.includes('.')) {
      return slug
    }
  }
  return null
}

async function loadSiteData(siteId) {
  const [rows] = await db.query('SELECT * FROM sites WHERE id = ?', [siteId])
  if (rows.length === 0) return null
  return buildSiteData(rows[0])
}

async function buildSiteData(site) {
  // Get theme key
  const [themeRows] = await db.query('SELECT `key` FROM themes WHERE id = ?', [site.theme_id])
  const themeKey = themeRows.length > 0 ? themeRows[0].key : null

  // Get published version number
  let publishedVersionNumber = null
  if (site.published_version_id) {
    const [pvRows] = await db.query(
      'SELECT version FROM site_versions WHERE id = ?',
      [site.published_version_id]
    )
    if (pvRows.length > 0) publishedVersionNumber = pvRows[0].version
  }

  // Get draft version
  const [draftRows] = await db.query(
    'SELECT id, version FROM site_versions WHERE site_id = ? AND status = ? ORDER BY version DESC LIMIT 1',
    [site.id, 'draft']
  )

  return {
    id: site.id,
    tenant_id: site.tenant_id,
    slug: site.slug,
    theme_key: themeKey,
    published_version_id: site.published_version_id || null,
    published_version_number: publishedVersionNumber,
    draft_version_id: draftRows.length > 0 ? draftRows[0].id : null,
    draft_version_number: draftRows.length > 0 ? draftRows[0].version : null,
  }
}

/**
 * Validate a preview session token.
 * Returns draft version number if valid, null otherwise.
 */
export async function validatePreviewToken(siteId, token) {
  const cacheKey = `preview_token:${token}`

  try {
    const cached = await redis.get(cacheKey)
    if (cached !== null) return cached === 'null' ? null : parseInt(cached, 10)
  } catch {}

  const [rows] = await db.query(
    'SELECT site_version_id FROM preview_sessions WHERE token = ? AND site_id = ? AND expires_at > NOW()',
    [token, siteId]
  )

  if (rows.length === 0) {
    try { await redis.setex(cacheKey, PREVIEW_CACHE_TTL, 'null') } catch {}
    return null
  }

  const [vRows] = await db.query(
    'SELECT version FROM site_versions WHERE id = ?',
    [rows[0].site_version_id]
  )

  const version = vRows.length > 0 ? vRows[0].version : null
  try { await redis.setex(cacheKey, PREVIEW_CACHE_TTL, String(version)) } catch {}
  return version
}
