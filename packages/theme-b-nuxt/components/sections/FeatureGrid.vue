<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
})

const ICONS = {
  search: '\u{1F50D}',
  shield: '\u{1F6E1}\uFE0F',
  zap: '\u26A1',
  bed: '\u{1F6CF}\uFE0F',
  bath: '\u{1F6BF}',
  area: '\u{1F4D0}',
}

const title = computed(() => props.props?.title || 'Why Choose Us')

const demoItems = [
  { icon: 'search', title: 'Curated Selection', desc: 'Hand-picked properties that meet the highest standards' },
  { icon: 'shield', title: 'Trusted Agents', desc: 'Work with experienced professionals who know your market' },
  { icon: 'zap', title: 'Fast Closing', desc: 'Streamlined process to get you into your dream home faster' },
]

const items = computed(() => props.props?.items?.length ? props.props.items : demoItems)
</script>

<template>
  <section class="tb-features">
    <div class="tb-features__inner">
      <h2 class="tb-features__title">{{ title }}</h2>
      <div class="tb-features__grid">
        <div v-for="(item, i) in items" :key="i" class="tb-feature-card">
          <div class="tb-feature-card__icon">{{ ICONS[item.icon] || '\u2728' }}</div>
          <h3 class="tb-feature-card__title">{{ item.title || '' }}</h3>
          <p class="tb-feature-card__desc">{{ item.desc || '' }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.tb-features { padding: 80px 24px; background: var(--tb-bg-card, #1e293b); }
.tb-features__inner { max-width: var(--tb-max-width, 1200px); margin: 0 auto; }
.tb-features__title { text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 48px; color: var(--tb-text, #f1f5f9); }
.tb-features__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; }
.tb-feature-card { background: var(--tb-bg-dark, #0f172a); border: 1px solid var(--tb-border, #475569); border-radius: var(--tb-radius, 12px); padding: 32px; text-align: center; transition: transform 0.2s, border-color 0.2s; }
.tb-feature-card:hover { transform: translateY(-4px); border-color: var(--tb-primary, #f97316); }
.tb-feature-card__icon { width: 56px; height: 56px; margin: 0 auto 16px; background: rgba(249,115,22,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.tb-feature-card__title { font-size: 1.15rem; font-weight: 600; margin-bottom: 8px; color: var(--tb-text, #f1f5f9); }
.tb-feature-card__desc { font-size: 0.95rem; color: var(--tb-text-muted, #94a3b8); line-height: 1.5; }
</style>
