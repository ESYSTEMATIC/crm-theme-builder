import 'dotenv/config'
import express from 'express'
import { createProxyMiddleware } from 'http-proxy-middleware'
import { initResolveHost, resolveHost } from './resolveHost.js'

const app = express()
const PORT = parseInt(process.env.PORT, 10) || 3000
const PLATFORM_DOMAIN = process.env.PLATFORM_DOMAIN

// Parse THEME_ROUTES env: "solo-theme-v1=http://solo-theme:3000"
const THEME_ROUTES = {}
;(process.env.THEME_ROUTES || '').split(',').forEach((entry) => {
  const [key, url] = entry.split('=')
  if (key && url) THEME_ROUTES[key.trim()] = url.trim()
})

// Initialize database and Redis connections
initResolveHost({
  redisHost: process.env.REDIS_HOST,
  redisPort: parseInt(process.env.REDIS_PORT, 10) || 6379,
  redisPassword: process.env.REDIS_PASSWORD,
  dbHost: process.env.DB_PLATFORM_HOST,
  dbPort: parseInt(process.env.DB_PLATFORM_PORT, 10) || 3306,
  dbUser: process.env.DB_PLATFORM_USERNAME,
  dbPassword: process.env.DB_PLATFORM_PASSWORD,
  dbName: process.env.DB_PLATFORM_DATABASE,
})

/**
 * Main middleware: resolve host → proxy to theme Nuxt server
 */
app.use(async (req, res, next) => {
  const host = req.hostname || req.headers.host?.split(':')[0]

  const siteData = await resolveHost(host, PLATFORM_DOMAIN)
  if (!siteData) {
    return res.status(404).send('Site not found')
  }

  if (!siteData.theme_key) {
    return res.status(500).send('Site has no theme assigned')
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
