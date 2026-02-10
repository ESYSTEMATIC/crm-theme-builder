<script setup>
const { data: siteData } = await useSiteData()

const brandStyle = computed(() => {
  const brand = siteData.value?.payload?.settings?.brand || {}
  return {
    '--primary-color': brand.primaryColor || '#2563eb',
    '--secondary-color': brand.secondaryColor || '#1e40af',
    '--font-family': brand.font || 'Inter, sans-serif',
    '--text-color': '#1f2937',
    '--text-light': '#6b7280',
    '--text-inverse': '#ffffff',
    '--bg-color': '#ffffff',
    '--bg-light': '#f9fafb',
    '--bg-dark': '#111827',
    '--border-color': '#e5e7eb',
    '--shadow-sm': '0 1px 2px rgba(0,0,0,0.05)',
    '--shadow-md': '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1)',
    '--shadow-lg': '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)',
    '--shadow-xl': '0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)',
    '--radius-sm': '6px',
    '--radius-md': '8px',
    '--radius-lg': '12px',
    '--max-width': '1200px',
  }
})
</script>

<template>
  <div class="ms-site" :style="brandStyle">
    <slot />
  </div>
</template>

<style>
.ms-site {
  min-height: 100vh;
  background-color: var(--bg-color, #ffffff);
  color: var(--text-color, #1f2937);
  font-family: var(--font-family, 'Inter, sans-serif');
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

.ms-site * {
  box-sizing: border-box;
}
</style>
