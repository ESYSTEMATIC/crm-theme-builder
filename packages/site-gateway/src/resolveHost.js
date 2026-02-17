import Redis from 'ioredis'
import mysql from 'mysql2/promise'

const CACHE_TTL = 300 // 5 minutes
const NEGATIVE_CACHE_TTL = 60 // 1 minute for 404s

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
 * Looks up full domain (custom or subdomain) in microsite_domains table.
 */
export async function resolveHost(host, platformDomain) {
  const cacheKey = `site_host:${host}`

  // Try Redis cache first
  try {
    const cached = await redis.get(cacheKey)
    if (cached === '__null__') return null
    if (cached) return JSON.parse(cached)
  } catch {}

  let siteData = null

  try {
    // 1. Try exact domain match
    const [rows] = await db.query(
      `SELECT id, tenant_id, domain, theme_key
       FROM microsite_domains
       WHERE domain = ? AND is_active = 1 AND deleted_at IS NULL`,
      [host]
    )
    if (rows.length > 0) {
      siteData = rows[0]
    }

    // 2. Try subdomain match (e.g., slug.listacrmsites.com)
    if (!siteData) {
      const subdomain = parseSubdomain(host, platformDomain)
      if (subdomain) {
        const [subRows] = await db.query(
          `SELECT id, tenant_id, domain, theme_key
           FROM microsite_domains
           WHERE domain = ? AND is_active = 1 AND deleted_at IS NULL`,
          [subdomain + '.' + platformDomain]
        )
        if (subRows.length > 0) {
          siteData = subRows[0]
        }
      }
    }
  } catch (err) {
    console.error('DB query error in resolveHost:', err.message)
    return null
  }

  // Cache result (including negative lookups)
  try {
    if (siteData) {
      await redis.setex(cacheKey, CACHE_TTL, JSON.stringify(siteData))
    } else {
      await redis.setex(cacheKey, NEGATIVE_CACHE_TTL, '__null__')
    }
  } catch {}

  return siteData
}

/**
 * Extract subdomain from hostname like {sub}.listacrmsites.com
 */
function parseSubdomain(host, platformDomain) {
  const suffix = '.' + platformDomain
  if (host.endsWith(suffix)) {
    const sub = host.slice(0, -suffix.length)
    if (sub && !sub.includes('.')) {
      return sub
    }
  }
  return null
}
