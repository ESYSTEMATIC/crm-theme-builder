import Redis from 'ioredis'
import mysql from 'mysql2/promise'

const CACHE_TTL = 300 // 5 minutes

let redis
let db

export function initResolveHost({ redisHost, redisPort, redisPassword, dbHost, dbPort, dbUser, dbPassword, dbName }) {
  redis = new Redis({ host: redisHost, port: redisPort, password: redisPassword || undefined, lazyConnect: true })
  redis.connect().catch(() => {})

  db = mysql.createPool({
    host: dbHost,
    port: dbPort,
    user: dbUser,
    password: dbPassword,
    database: dbName,
    waitForConnections: true,
    connectionLimit: 10,
  })
}

/**
 * Resolve a hostname to site data.
 * Custom domain first, then slug wildcard.
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
    `SELECT s.id, s.tenant_id, s.slug, t.\`key\` AS theme_key
     FROM site_domains sd
     JOIN sites s ON s.id = sd.site_id
     JOIN themes t ON t.id = s.theme_id
     WHERE sd.host = ? AND sd.status = ?`,
    [host, 'verified']
  )
  if (domainRows.length > 0) {
    siteData = domainRows[0]
  }

  // 2. Try slug from platform wildcard
  if (!siteData) {
    const slug = parseSlugFromHost(host, platformDomain)
    if (slug) {
      const [rows] = await db.query(
        `SELECT s.id, s.tenant_id, s.slug, t.\`key\` AS theme_key
         FROM sites s
         JOIN themes t ON t.id = s.theme_id
         WHERE s.slug = ?`,
        [slug]
      )
      if (rows.length > 0) {
        siteData = rows[0]
      }
    }
  }

  // Cache result
  try {
    if (siteData) {
      await redis.setex(cacheKey, CACHE_TTL, JSON.stringify(siteData))
    }
  } catch {}

  return siteData
}

/**
 * Parse slug from hostname like {slug}.listacrmsites.com
 */
function parseSlugFromHost(host, platformDomain) {
  const suffix = '.' + platformDomain
  if (host.endsWith(suffix)) {
    const slug = host.slice(0, -suffix.length)
    if (slug && !slug.includes('.')) {
      return slug
    }
  }
  return null
}
