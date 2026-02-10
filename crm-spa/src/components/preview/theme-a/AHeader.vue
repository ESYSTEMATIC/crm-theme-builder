<script setup>
defineProps({ props: Object, settings: Object })

const demoLinks = [
  { label: 'Home' },
  { label: 'About' },
  { label: 'Listings' },
]
</script>

<template>
  <header class="ms-header">
    <div class="ms-header__inner">
      <span class="ms-header__logo">{{ props?.logoText || 'My Site' }}</span>
      <nav class="ms-header__nav">
        <span v-for="(link, i) in (props?.navLinks?.length ? props.navLinks : demoLinks)" :key="i">{{ link.label }}</span>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.ms-header {
  position: relative;
  background-color: var(--bg-color, #ffffff);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.ms-header__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  height: 72px;
}
.ms-header__logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color, #2563eb);
  letter-spacing: -0.025em;
}
.ms-header__nav {
  display: flex;
  align-items: center;
  gap: 32px;
}
.ms-header__nav span {
  color: var(--text-color, #1f2937);
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: default;
  transition: color 0.2s;
}
.ms-header__nav span:hover {
  color: var(--primary-color, #2563eb);
}
</style>
