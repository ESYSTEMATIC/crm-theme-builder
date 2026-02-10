<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const title = computed(() => props.props?.title || 'Find Your Dream Home')
const subtitle = computed(() => props.props?.subtitle || 'Browse our curated selection of premium properties')
const backgroundImage = computed(() => props.props?.backgroundImage || '')
</script>

<template>
  <section
    class="ms-hero"
    :style="backgroundImage ? { backgroundImage: `url(${backgroundImage})` } : {}"
  >
    <div class="ms-hero__overlay"></div>
    <div class="ms-hero__content">
      <h1 class="ms-hero__title">{{ title }}</h1>
      <p class="ms-hero__subtitle">{{ subtitle }}</p>
    </div>
  </section>
</template>

<style scoped>
.ms-hero {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 520px;
  padding: 80px 24px;
  background-color: var(--bg-dark, #111827);
  background-size: cover;
  background-position: center;
  overflow: hidden;
}
.ms-hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.6) 100%);
  z-index: 1;
}
.ms-hero__content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 720px;
}
.ms-hero__title {
  font-size: 3rem;
  font-weight: 800;
  color: var(--text-inverse, #ffffff);
  line-height: 1.15;
  margin-bottom: 16px;
  letter-spacing: -0.025em;
}
.ms-hero__subtitle {
  font-size: 1.25rem;
  color: rgba(255,255,255,0.85);
  line-height: 1.6;
  font-weight: 400;
}

@media (max-width: 768px) {
  .ms-hero { min-height: 400px; padding: 60px 24px; }
  .ms-hero__title { font-size: 2.25rem; }
  .ms-hero__subtitle { font-size: 1.0625rem; }
}
@media (max-width: 640px) {
  .ms-hero__title { font-size: 1.875rem; }
}
</style>
