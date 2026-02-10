<script setup>
defineProps({ props: Object })
</script>

<template>
  <footer class="ms-footer">
    <p class="ms-footer__text">{{ props?.text || '\u00a9 2024 My Site. All rights reserved.' }}</p>
    <nav v-if="props?.links?.length" class="ms-footer__links">
      <span v-for="(link, i) in props.links" :key="i">{{ link.label }}</span>
    </nav>
  </footer>
</template>

<style scoped>
.ms-footer { background-color: #111827; padding: 48px 24px; text-align: center; }
.ms-footer__text { color: rgba(255,255,255,0.65); font-size: 0.9375rem; margin-bottom: 16px; }
.ms-footer__links { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
.ms-footer__links span { color: rgba(255,255,255,0.5); font-size: 0.875rem; cursor: default; }
.ms-footer__links span:hover { color: #fff; }
</style>
