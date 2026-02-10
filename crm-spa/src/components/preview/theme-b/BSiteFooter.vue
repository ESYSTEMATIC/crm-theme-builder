<script setup>
defineProps({ props: Object })
</script>

<template>
  <footer class="tb-footer">
    {{ props?.copyright || '' }}
  </footer>
</template>

<style scoped>
.tb-footer { background: var(--tb-bg-card, #1e293b); border-top: 1px solid var(--tb-border, #475569); padding: 32px 24px; text-align: center; color: var(--tb-text-muted, #94a3b8); font-size: 0.9rem; }
</style>
