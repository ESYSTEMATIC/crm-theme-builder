<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const brandName = computed(() => props.props?.brandName || 'Bold Realty')
const links = computed(() => props.props?.links?.length ? props.props.links : [
  { label: 'Home', href: '/' },
  { label: 'About', href: '/about' },
  { label: 'Listings', href: '/listings' },
])
</script>

<template>
  <nav class="tb-navbar">
    <div class="tb-navbar__inner">
      <NuxtLink to="/" class="tb-navbar__brand">{{ brandName }}</NuxtLink>
      <ul class="tb-navbar__links">
        <li v-for="(link, i) in links" :key="i">
          <NuxtLink :to="link.href || '#'">{{ link.label }}</NuxtLink>
        </li>
      </ul>
    </div>
  </nav>
</template>

<style scoped>
.tb-navbar { position: sticky; top: 0; z-index: 100; background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid var(--tb-border, #475569); padding: 0 24px; }
.tb-navbar__inner { max-width: var(--tb-max-width, 1200px); margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 64px; }
.tb-navbar__brand { font-size: 1.5rem; font-weight: 800; color: var(--tb-primary, #f97316); letter-spacing: -0.5px; text-decoration: none; }
.tb-navbar__links { display: flex; gap: 32px; list-style: none; margin: 0; padding: 0; }
.tb-navbar__links a { color: var(--tb-text-muted, #94a3b8); font-weight: 500; font-size: 0.95rem; text-decoration: none; transition: color 0.2s; }
.tb-navbar__links a:hover { color: var(--tb-text, #f1f5f9); }

@media (max-width: 480px) {
  .tb-navbar__links { display: none; }
}
</style>
