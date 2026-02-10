<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const text = computed(() => props.props?.text || '\u00a9 2024 My Site. All rights reserved.')
const links = computed(() => props.props?.links || [])
</script>

<template>
  <footer class="ms-footer">
    <p class="ms-footer__text">{{ text }}</p>
    <nav v-if="links.length" class="ms-footer__links">
      <NuxtLink v-for="(link, i) in links" :key="i" :to="link.href || '#'">{{ link.label }}</NuxtLink>
    </nav>
  </footer>
</template>

<style scoped>
.ms-footer { background-color: var(--bg-dark, #111827); padding: 48px 24px; text-align: center; }
.ms-footer__text { color: rgba(255,255,255,0.65); font-size: 0.9375rem; margin-bottom: 16px; }
.ms-footer__links { display: flex; justify-content: center; gap: 24px; list-style: none; flex-wrap: wrap; }
.ms-footer__links a { color: rgba(255,255,255,0.5); font-size: 0.875rem; text-decoration: none; transition: color 0.2s; }
.ms-footer__links a:hover { color: var(--text-inverse, #fff); }
</style>
