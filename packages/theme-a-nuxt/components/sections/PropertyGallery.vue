<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
  siteId: Number,
  currentPath: String,
})

const title = computed(() => props.props?.title || 'Featured Properties')
const columns = computed(() => props.props?.columns || 3)

const { data: propertiesData } = await useProperties(props.siteId)
const properties = computed(() => propertiesData.value?.data || [])

function formatPrice(n) {
  return '$' + Number(n).toLocaleString('en-US')
}

// Check if we're on a detail route (e.g., /listings/123)
const route = useRoute()
const detailId = computed(() => {
  const slugParts = route.params.slug
  if (Array.isArray(slugParts) && slugParts.length >= 2) {
    return slugParts[slugParts.length - 1]
  }
  return null
})

// If on detail route, fetch single property
const { data: detailProperty } = detailId.value
  ? await useProperty(props.siteId, detailId.value)
  : { data: ref(null) }

const isDetail = computed(() => !!detailId.value && !!detailProperty.value)
</script>

<template>
  <!-- Detail View -->
  <section v-if="isDetail" class="ms-property-detail">
    <div class="ms-property-detail__header">
      <div>
        <h1 class="ms-property-detail__title">{{ detailProperty.title }}</h1>
        <p class="ms-property-detail__address">{{ detailProperty.address }}</p>
        <div class="ms-property-detail__price">{{ formatPrice(detailProperty.price) }}</div>
      </div>
    </div>
    <div class="ms-property-detail__stats">
      <div class="ms-property-detail__stat">
        <span class="ms-property-detail__stat-value">{{ detailProperty.bedrooms }}</span>
        <span class="ms-property-detail__stat-label">Bedrooms</span>
      </div>
      <div class="ms-property-detail__stat">
        <span class="ms-property-detail__stat-value">{{ detailProperty.bathrooms }}</span>
        <span class="ms-property-detail__stat-label">Bathrooms</span>
      </div>
      <div v-if="detailProperty.area_sqft" class="ms-property-detail__stat">
        <span class="ms-property-detail__stat-value">{{ detailProperty.area_sqft.toLocaleString() }}</span>
        <span class="ms-property-detail__stat-label">Sq Ft</span>
      </div>
    </div>
    <p v-if="detailProperty.description" class="ms-property-detail__description">{{ detailProperty.description }}</p>
  </section>

  <!-- Gallery View -->
  <section v-else class="ms-gallery">
    <h2 class="ms-gallery__title">{{ title }}</h2>
    <div class="ms-gallery__grid" :style="{ gridTemplateColumns: `repeat(${columns}, 1fr)` }">
      <NuxtLink
        v-for="p in properties"
        :key="p.id"
        :to="`${currentPath || '/listings'}/${p.id}`"
        class="ms-gallery__card"
      >
        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="ms-gallery__card-image" />
        <div v-else class="ms-gallery__card-image--placeholder">{{ (p.title || '?').charAt(0) }}</div>
        <div class="ms-gallery__card-body">
          <h3 class="ms-gallery__card-title">{{ p.title }}</h3>
          <p class="ms-gallery__card-address">{{ p.address }}</p>
          <div class="ms-gallery__card-price">{{ formatPrice(p.price) }}</div>
          <div class="ms-gallery__card-meta">
            <span>{{ p.bedrooms }} Beds</span>
            <span>{{ p.bathrooms }} Baths</span>
            <span v-if="p.area_sqft">{{ p.area_sqft.toLocaleString() }} Sqft</span>
          </div>
        </div>
      </NuxtLink>
      <div v-if="!properties.length" class="ms-gallery__empty">No properties available yet.</div>
    </div>
  </section>
</template>

<style scoped>
/* Gallery */
.ms-gallery { padding: 80px 24px; background-color: var(--bg-light, #f9fafb); }
.ms-gallery__title { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 48px; color: var(--text-color, #1f2937); letter-spacing: -0.025em; }
.ms-gallery__grid { display: grid; gap: 24px; max-width: var(--max-width, 1200px); margin: 0 auto; }
.ms-gallery__card { background-color: var(--bg-color, #fff); border-radius: var(--radius-lg, 12px); overflow: hidden; box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1)); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
.ms-gallery__card:hover { transform: translateY(-4px); box-shadow: var(--shadow-xl, 0 20px 25px -5px rgba(0,0,0,0.1)); color: inherit; }
.ms-gallery__card-image { width: 100%; height: 220px; object-fit: cover; background-color: #e5e7eb; }
.ms-gallery__card-image--placeholder { display: flex; align-items: center; justify-content: center; height: 220px; background: linear-gradient(135deg, var(--primary-color, #2563eb), var(--secondary-color, #1e40af)); color: var(--text-inverse, #fff); font-size: 2.5rem; font-weight: 700; opacity: 0.8; }
.ms-gallery__card-body { padding: 20px; }
.ms-gallery__card-title { font-size: 1.125rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color, #1f2937); }
.ms-gallery__card-address { font-size: 0.875rem; color: var(--text-light, #6b7280); margin-bottom: 12px; }
.ms-gallery__card-price { font-size: 1.25rem; font-weight: 700; color: var(--primary-color, #2563eb); }
.ms-gallery__card-meta { display: flex; gap: 16px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
.ms-gallery__card-meta span { font-size: 0.8125rem; color: var(--text-light, #6b7280); font-weight: 500; }
.ms-gallery__empty { text-align: center; padding: 60px 24px; color: var(--text-light, #6b7280); font-size: 1.125rem; grid-column: 1 / -1; }

/* Property Detail */
.ms-property-detail { max-width: var(--max-width, 1200px); margin: 0 auto; padding: 48px 24px; }
.ms-property-detail__header { margin-bottom: 32px; }
.ms-property-detail__title { font-size: 2rem; font-weight: 700; margin-bottom: 8px; color: var(--text-color, #1f2937); }
.ms-property-detail__address { color: var(--text-light, #6b7280); font-size: 1.0625rem; }
.ms-property-detail__price { font-size: 1.75rem; font-weight: 800; color: var(--primary-color, #2563eb); margin: 16px 0; }
.ms-property-detail__stats { display: flex; gap: 32px; padding: 24px 0; border-top: 1px solid var(--border-color, #e5e7eb); border-bottom: 1px solid var(--border-color, #e5e7eb); margin-bottom: 32px; }
.ms-property-detail__stat { text-align: center; }
.ms-property-detail__stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-color, #1f2937); display: block; }
.ms-property-detail__stat-label { font-size: 0.8125rem; color: var(--text-light, #6b7280); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500; }
.ms-property-detail__description { font-size: 1.0625rem; line-height: 1.8; color: var(--text-color, #1f2937); }

@media (max-width: 1024px) {
  .ms-gallery__grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 768px) {
  .ms-gallery { padding: 48px 16px; }
  .ms-gallery__title { font-size: 1.5rem; margin-bottom: 32px; }
  .ms-property-detail__stats { gap: 20px; flex-wrap: wrap; }
}
@media (max-width: 640px) {
  .ms-gallery__grid { grid-template-columns: 1fr !important; }
  .ms-property-detail__stats { gap: 16px; }
  .ms-property-detail__title { font-size: 1.5rem; }
}
</style>
