<script setup>
import SiteNavbar from '~/components/sections/SiteNavbar.vue'
import HeroBanner from '~/components/sections/HeroBanner.vue'
import FeatureGrid from '~/components/sections/FeatureGrid.vue'
import CtaForm from '~/components/sections/CtaForm.vue'
import PropertyGrid from '~/components/sections/PropertyGrid.vue'
import SiteFooter from '~/components/sections/SiteFooter.vue'

const SECTION_MAP = {
  'navbar': SiteNavbar,
  'banner': HeroBanner,
  'features': FeatureGrid,
  'cta': CtaForm,
  'property-grid': PropertyGrid,
  'site-footer': SiteFooter,
}

const { data: siteData } = await useSiteData()
const route = useRoute()

// Build current path from slug params
const currentPath = computed(() => {
  const slugParts = route.params.slug
  if (!slugParts || (Array.isArray(slugParts) && slugParts.length === 0)) return '/'
  return '/' + (Array.isArray(slugParts) ? slugParts.join('/') : slugParts)
})

// Match path to manifest route
const matchedRoute = computed(() => {
  const manifest = siteData.value?.manifest
  if (!manifest?.routes) return null

  // Exact match first
  const exact = manifest.routes.find(r => r.path === currentPath.value)
  if (exact) return exact

  // Detail route fallback: /listings/123 → match /listings/:id
  const parentPath = currentPath.value.replace(/\/[^/]+$/, '')
  return manifest.routes.find(r => r.path.includes(':') && r.path.startsWith(parentPath))
})

const routeId = computed(() => matchedRoute.value?.id || '')

const sections = computed(() => {
  if (!routeId.value || !siteData.value?.payload?.routes) return []
  return siteData.value.payload.routes[routeId.value]?.sections || []
})

const settings = computed(() => siteData.value?.payload?.settings || {})

// SEO
const seoTitle = computed(() => {
  const routePayload = siteData.value?.payload?.routes?.[routeId.value]
  const title = routePayload?.seo?.title || 'Page'
  const suffix = settings.value?.seo?.titleSuffix || ''
  return title + suffix
})

useHead({ title: seoTitle })

function getSectionComponent(type) {
  return SECTION_MAP[type] || null
}
</script>

<template>
  <div>
    <template v-for="(section, i) in sections" :key="i">
      <component
        v-if="section.visible !== false && getSectionComponent(section.type)"
        :is="getSectionComponent(section.type)"
        :props="section.props"
        :settings="settings"
        :site-id="siteData?.site_id"
        :current-path="currentPath"
      />
    </template>

    <div v-if="!sections.length" class="tb-empty">
      <p>Page not found</p>
    </div>
  </div>
</template>

<style scoped>
.tb-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  color: var(--tb-text-muted, #94a3b8);
  font-size: 1.2rem;
}
</style>
