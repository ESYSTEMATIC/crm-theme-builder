<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const copyright = computed(() => props.props?.copyright || '\u00a9 2024 Bold Realty. All rights reserved.')
</script>

<template>
  <footer class="tb-footer">
    {{ copyright }}
  </footer>
</template>

<style scoped>
.tb-footer { background: var(--tb-bg-card, #1e293b); border-top: 1px solid var(--tb-border, #475569); padding: 32px 24px; text-align: center; color: var(--tb-text-muted, #94a3b8); font-size: 0.9rem; }
</style>
