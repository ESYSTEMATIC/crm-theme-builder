<script setup>
defineProps({ props: Object, settings: Object })
</script>

<template>
  <nav class="tb-navbar">
    <div class="tb-navbar__inner">
      <span class="tb-navbar__brand">{{ props?.brandName || 'Site' }}</span>
      <ul class="tb-navbar__links">
        <li v-for="(link, i) in (props?.links || [])" :key="i">
          <span>{{ link.label }}</span>
        </li>
      </ul>
    </div>
  </nav>
</template>

<style scoped>
.tb-navbar { position: relative; background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid var(--tb-border, #475569); padding: 0 24px; }
.tb-navbar__inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 64px; }
.tb-navbar__brand { font-size: 1.5rem; font-weight: 800; color: var(--tb-primary, #f97316); letter-spacing: -0.5px; }
.tb-navbar__links { display: flex; gap: 32px; list-style: none; }
.tb-navbar__links span { color: var(--tb-text-muted, #94a3b8); font-weight: 500; font-size: 0.95rem; cursor: default; transition: color 0.2s; }
.tb-navbar__links span:hover { color: var(--tb-text, #f1f5f9); }
</style>
