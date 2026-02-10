<script setup>
const { data: siteData } = await useSiteData()

const brandStyle = computed(() => {
  const brand = siteData.value?.payload?.settings?.brand || {}
  return {
    '--tb-primary': brand.primaryColor || '#f97316',
    '--tb-primary-dark': brand.secondaryColor || '#ea580c',
    '--tb-font': brand.font || 'Poppins, sans-serif',
    '--tb-bg-dark': '#0f172a',
    '--tb-bg-card': '#1e293b',
    '--tb-bg-surface': '#334155',
    '--tb-text': '#f1f5f9',
    '--tb-text-muted': '#94a3b8',
    '--tb-border': '#475569',
    '--tb-radius': '12px',
    '--tb-max-width': '1200px',
  }
})
</script>

<template>
  <div class="tb-site" :style="brandStyle">
    <slot />
  </div>
</template>

<style>
.tb-site {
  min-height: 100vh;
  background-color: var(--tb-bg-dark, #0f172a);
  color: var(--tb-text, #f1f5f9);
  font-family: var(--tb-font, 'Poppins, sans-serif');
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

.tb-site * {
  box-sizing: border-box;
}
</style>
