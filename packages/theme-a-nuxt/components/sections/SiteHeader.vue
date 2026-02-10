<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const logoText = computed(() => props.props?.logoText || 'My Site')
const demoLinks = [
  { label: 'Home', href: '/' },
  { label: 'About', href: '/about' },
  { label: 'Listings', href: '/listings' },
]
const links = computed(() => props.props?.navLinks?.length ? props.props.navLinks : demoLinks)
</script>

<template>
  <header class="ms-header">
    <div class="ms-header__inner">
      <NuxtLink to="/" class="ms-header__logo">{{ logoText }}</NuxtLink>
      <nav class="ms-header__nav">
        <NuxtLink
          v-for="(link, i) in links"
          :key="i"
          :to="link.href || '#'"
        >{{ link.label }}</NuxtLink>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.ms-header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background-color: var(--bg-color, #ffffff);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
}
.ms-header__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: var(--max-width, 1200px);
  margin: 0 auto;
  padding: 0 24px;
  height: 72px;
}
.ms-header__logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color, #2563eb);
  letter-spacing: -0.025em;
  text-decoration: none;
}
.ms-header__logo:hover { color: var(--secondary-color, #1e40af); }
.ms-header__nav {
  display: flex;
  align-items: center;
  gap: 32px;
}
.ms-header__nav a {
  color: var(--text-color, #1f2937);
  font-size: 0.9375rem;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s;
  position: relative;
}
.ms-header__nav a::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 0;
  height: 2px;
  background-color: var(--primary-color, #2563eb);
  transition: width 0.2s;
}
.ms-header__nav a:hover { color: var(--primary-color, #2563eb); }
.ms-header__nav a:hover::after { width: 100%; }

@media (max-width: 768px) {
  .ms-header__inner { height: 60px; }
  .ms-header__logo { font-size: 1.25rem; }
  .ms-header__nav { gap: 20px; }
  .ms-header__nav a { font-size: 0.875rem; }
}
@media (max-width: 640px) {
  .ms-header__inner { flex-direction: column; height: auto; padding: 16px 24px; gap: 12px; }
  .ms-header__nav { gap: 16px; }
}
</style>
