<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const heading = computed(() => props.props?.heading || 'Live Bold. Live Here.')
const subheading = computed(() => props.props?.subheading || 'Discover extraordinary properties curated for modern living')
const ctaText = computed(() => props.props?.ctaText || '')
const ctaHref = computed(() => props.props?.ctaHref || '/listings')
</script>

<template>
  <section class="tb-banner">
    <div class="tb-banner__content">
      <h1 class="tb-banner__heading">{{ heading }}</h1>
      <p class="tb-banner__sub">{{ subheading }}</p>
      <NuxtLink v-if="ctaText" :to="ctaHref" class="tb-btn">{{ ctaText }}</NuxtLink>
    </div>
  </section>
</template>

<style scoped>
.tb-banner { position: relative; min-height: 480px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 80px 24px; background: linear-gradient(135deg, var(--tb-bg-dark, #0f172a) 0%, #1a1a2e 50%, var(--tb-bg-dark, #0f172a) 100%); overflow: hidden; }
.tb-banner::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle at 30% 50%, rgba(249,115,22,0.08) 0%, transparent 50%), radial-gradient(circle at 70% 50%, rgba(234,88,12,0.05) 0%, transparent 50%); animation: tb-glow 8s ease-in-out infinite alternate; }
@keyframes tb-glow { 0% { transform: translate(0,0); } 100% { transform: translate(-5%,5%); } }
.tb-banner__content { position: relative; z-index: 1; max-width: 720px; }
.tb-banner__heading { font-size: clamp(2.5rem, 6vw, 4rem); font-weight: 800; line-height: 1.1; letter-spacing: -1px; margin-bottom: 16px; background: linear-gradient(135deg, var(--tb-text, #f1f5f9) 0%, var(--tb-primary, #f97316) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.tb-banner__sub { font-size: 1.2rem; color: var(--tb-text-muted, #94a3b8); margin-bottom: 32px; max-width: 540px; margin-left: auto; margin-right: auto; }
.tb-btn { display: inline-block; padding: 14px 36px; background: var(--tb-primary, #f97316); color: #fff; font-weight: 600; font-size: 1rem; border: none; border-radius: 50px; text-decoration: none; cursor: pointer; box-shadow: 0 4px 24px rgba(249,115,22,0.3); transition: transform 0.2s, box-shadow 0.2s; }
.tb-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 32px rgba(249,115,22,0.4); }

@media (max-width: 768px) {
  .tb-banner { min-height: 380px; padding: 60px 24px; }
}
</style>
