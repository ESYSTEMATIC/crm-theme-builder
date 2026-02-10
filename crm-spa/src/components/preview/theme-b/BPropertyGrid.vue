<script setup>
defineProps({ props: Object })

const placeholders = [
  { title: 'Modern Villa', address: '123 Oak Street', price: 450000, beds: 3, baths: 2, sqft: 1800 },
  { title: 'Downtown Condo', address: '456 Main Ave', price: 325000, beds: 2, baths: 1, sqft: 1200 },
  { title: 'Family Home', address: '789 Elm Drive', price: 575000, beds: 4, baths: 3, sqft: 2400 },
  { title: 'Luxury Penthouse', address: '101 Sky Tower', price: 890000, beds: 3, baths: 2, sqft: 2000 },
  { title: 'Cozy Cottage', address: '222 Pine Lane', price: 280000, beds: 2, baths: 1, sqft: 950 },
  { title: 'Estate Manor', address: '333 Grand Blvd', price: 1200000, beds: 5, baths: 4, sqft: 3500 },
]

function formatPrice(n) {
  return '$' + Number(n).toLocaleString('en-US')
}
</script>

<template>
  <section class="tb-pgrid">
    <div class="tb-pgrid__inner">
      <h2 v-if="props?.heading" class="tb-pgrid__heading">{{ props.heading }}</h2>
      <div class="tb-pgrid__grid">
        <div v-for="(p, i) in placeholders" :key="i" class="tb-pcard">
          <div class="tb-pcard__img-placeholder">{{ p.title }}</div>
          <div class="tb-pcard__body">
            <div class="tb-pcard__price">{{ formatPrice(p.price) }}</div>
            <div class="tb-pcard__title">{{ p.title }}</div>
            <div class="tb-pcard__address">{{ p.address }}</div>
            <div class="tb-pcard__meta">
              <span>&#x1F6CF;&#xFE0F; {{ p.beds }} bd</span>
              <span>&#x1F6BF; {{ p.baths }} ba</span>
              <span>&#x1F4D0; {{ p.sqft.toLocaleString() }} sqft</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.tb-pgrid { padding: 80px 24px; background: var(--tb-bg-dark, #0f172a); }
.tb-pgrid__inner { max-width: 1200px; margin: 0 auto; }
.tb-pgrid__heading { text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 48px; color: var(--tb-text, #f1f5f9); }
.tb-pgrid__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.tb-pcard { background: var(--tb-bg-card, #1e293b); border: 1px solid var(--tb-border, #475569); border-radius: 12px; overflow: hidden; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; cursor: pointer; }
.tb-pcard:hover { transform: translateY(-6px); border-color: var(--tb-primary, #f97316); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
.tb-pcard__img-placeholder { width: 100%; height: 220px; background: var(--tb-bg-surface, #334155); display: flex; align-items: center; justify-content: center; color: var(--tb-text-muted, #94a3b8); font-size: 0.9rem; }
.tb-pcard__body { padding: 20px; }
.tb-pcard__price { font-size: 1.4rem; font-weight: 700; color: var(--tb-primary, #f97316); margin-bottom: 4px; }
.tb-pcard__title { font-size: 1.05rem; font-weight: 600; margin-bottom: 4px; color: var(--tb-text, #f1f5f9); }
.tb-pcard__address { font-size: 0.85rem; color: var(--tb-text-muted, #94a3b8); margin-bottom: 12px; }
.tb-pcard__meta { display: flex; gap: 16px; font-size: 0.85rem; color: var(--tb-text-muted, #94a3b8); }
.tb-pcard__meta span { display: flex; align-items: center; gap: 4px; }
</style>
