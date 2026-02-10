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
  <section class="ms-gallery">
    <h2 v-if="props?.title" class="ms-gallery__title">{{ props.title }}</h2>
    <div class="ms-gallery__grid" :style="{ gridTemplateColumns: `repeat(${props?.columns || 3}, 1fr)` }">
      <div v-for="(p, i) in placeholders" :key="i" class="ms-gallery__card">
        <div class="ms-gallery__card-image--placeholder">{{ p.title.charAt(0) }}</div>
        <div class="ms-gallery__card-body">
          <h3 class="ms-gallery__card-title">{{ p.title }}</h3>
          <p class="ms-gallery__card-address">{{ p.address }}</p>
          <div class="ms-gallery__card-price">{{ formatPrice(p.price) }}</div>
          <div class="ms-gallery__card-meta">
            <span>{{ p.beds }} Beds</span>
            <span>{{ p.baths }} Baths</span>
            <span>{{ p.sqft.toLocaleString() }} Sqft</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.ms-gallery { padding: 80px 24px; background-color: var(--bg-light, #f9fafb); }
.ms-gallery__title { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 48px; color: var(--text-color, #1f2937); letter-spacing: -0.025em; }
.ms-gallery__grid { display: grid; gap: 24px; max-width: 1200px; margin: 0 auto; }
.ms-gallery__card { background-color: var(--bg-color, #fff); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
.ms-gallery__card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }
.ms-gallery__card-image--placeholder { display: flex; align-items: center; justify-content: center; height: 220px; background: linear-gradient(135deg, var(--primary-color, #2563eb), var(--secondary-color, #1e40af)); color: #fff; font-size: 2.5rem; font-weight: 700; opacity: 0.8; }
.ms-gallery__card-body { padding: 20px; }
.ms-gallery__card-title { font-size: 1.125rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color, #1f2937); }
.ms-gallery__card-address { font-size: 0.875rem; color: var(--text-light, #6b7280); margin-bottom: 12px; }
.ms-gallery__card-price { font-size: 1.25rem; font-weight: 700; color: var(--primary-color, #2563eb); }
.ms-gallery__card-meta { display: flex; gap: 16px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
.ms-gallery__card-meta span { font-size: 0.8125rem; color: var(--text-light, #6b7280); font-weight: 500; }
</style>
