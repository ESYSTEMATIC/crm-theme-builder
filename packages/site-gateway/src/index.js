import express from 'express'
import { createProxyMiddleware } from 'http-proxy-middleware'
import cookieLib from 'cookie'
import { initResolveHost, resolveHost, validatePreviewToken } from './resolveHost.js'

const app = express()
const PORT = parseInt(process.env.PORT || '3000', 10)
const PLATFORM_DOMAIN = process.env.PLATFORM_DOMAIN || 'crmwebsite.com'

// Parse THEME_ROUTES env: "theme-a-v1=http://theme-a-nuxt:3001,theme-b-v1=http://theme-b-nuxt:3002"
const THEME_ROUTES = {}
;(process.env.THEME_ROUTES || '').split(',').forEach((entry) => {
  const [key, url] = entry.split('=')
  if (key && url) THEME_ROUTES[key.trim()] = url.trim()
})

// Initialize database and Redis connections
initResolveHost({
  redisHost: process.env.REDIS_HOST || 'redis',
  redisPort: parseInt(process.env.REDIS_PORT || '6379', 10),
  dbHost: process.env.DB_PLATFORM_HOST || 'mysql_platform',
  dbPort: parseInt(process.env.DB_PLATFORM_PORT || '3306', 10),
  dbUser: process.env.DB_PLATFORM_USERNAME || 'root',
  dbPassword: process.env.DB_PLATFORM_PASSWORD || 'secret',
  dbName: process.env.DB_PLATFORM_DATABASE || 'microsite_platform',
})

/**
 * Preview endpoint: /__preview?token=...
 * Validates token, sets HttpOnly cookie, redirects to /
 */
app.get('/__preview', async (req, res) => {
  const token = req.query.token
  if (!token) return res.status(400).send('Missing token')

  const host = req.hostname || req.headers.host?.split(':')[0]
  const siteData = await resolveHost(host, PLATFORM_DOMAIN)
  if (!siteData) return res.status(404).send('Site not found')

  const draftVersion = await validatePreviewToken(siteData.id, token)
  if (draftVersion === null) return res.status(403).send('Invalid or expired preview token')

  // Set HttpOnly cookie (same as PHP site-runtime)
  res.setHeader('Set-Cookie', cookieLib.serialize('preview_session', token, {
    httpOnly: true,
    sameSite: 'lax',
    path: '/',
    maxAge: 60 * 60, // 1 hour
    secure: req.protocol === 'https',
  }))

  res.redirect(302, '/')
})

/**
 * Main middleware: resolve host → determine mode → proxy to theme Nuxt server
 */
app.use(async (req, res, next) => {
  const host = req.hostname || req.headers.host?.split(':')[0]

  // Resolve hostname to site data
  const siteData = await resolveHost(host, PLATFORM_DOMAIN)
  if (!siteData) {
    return res.status(404).send('Site not found')
  }

  if (!siteData.theme_key) {
    return res.status(500).send('Site has no theme assigned')
  }

  // Determine mode: check preview_session cookie
  let mode = 'published'
  let version = siteData.published_version_number

  const cookies = cookieLib.parse(req.headers.cookie || '')
  const previewToken = cookies.preview_session
  if (previewToken) {
    const draftVersion = await validatePreviewToken(siteData.id, previewToken)
    if (draftVersion !== null) {
      mode = 'draft'
      version = draftVersion
    }
  }

  // If no published version and not in preview mode, show 404
  if (mode === 'published' && !version) {
    return res.status(404).send('Site not published yet')
  }

  // Find target Nuxt server for this theme
  const target = THEME_ROUTES[siteData.theme_key]
  if (!target) {
    return res.status(500).send(`No Nuxt server configured for theme: ${siteData.theme_key}`)
  }

  // Set context headers for the Nuxt server
  req.headers['x-site-id'] = String(siteData.id)
  req.headers['x-tenant-id'] = String(siteData.tenant_id)
  req.headers['x-theme-key'] = siteData.theme_key
  req.headers['x-site-mode'] = mode
  req.headers['x-site-version'] = String(version)
  req.headers['x-site-slug'] = siteData.slug

  // Proxy to theme Nuxt server
  const proxy = createProxyMiddleware({
    target,
    changeOrigin: true,
    ws: false,
    on: {
      error: (err, _req, res) => {
        console.error(`Proxy error to ${target}:`, err.message)
        if (!res.headersSent) {
          res.status(502).send('Theme server unavailable')
        }
      },
    },
  })

  proxy(req, res, next)
})

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Site gateway listening on :${PORT}`)
  console.log(`Platform domain: ${PLATFORM_DOMAIN}`)
  console.log(`Theme routes:`, THEME_ROUTES)
})
