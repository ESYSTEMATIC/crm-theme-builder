<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
  siteId: Number,
  currentPath: String,
})

const heading = computed(() => props.props?.heading || 'Available Properties')

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
  <section v-if="isDetail" class="tb-detail">
    <img
      v-if="detailProperty.image_url"
      :src="detailProperty.image_url"
      :alt="detailProperty.title"
      class="tb-detail__img"
    />
    <div class="tb-detail__header">
      <div>
        <h1 class="tb-detail__title">{{ detailProperty.title }}</h1>
        <p class="tb-detail__address">{{ detailProperty.address }}</p>
      </div>
      <div class="tb-detail__price">{{ formatPrice(detailProperty.price) }}</div>
    </div>
    <div class="tb-detail__stats">
      <div class="tb-detail__stat">
        <div class="tb-detail__stat-value">{{ detailProperty.bedrooms }}</div>
        <div class="tb-detail__stat-label">Bedrooms</div>
      </div>
      <div class="tb-detail__stat">
        <div class="tb-detail__stat-value">{{ detailProperty.bathrooms }}</div>
        <div class="tb-detail__stat-label">Bathrooms</div>
      </div>
      <div v-if="detailProperty.area_sqft" class="tb-detail__stat">
        <div class="tb-detail__stat-value">{{ detailProperty.area_sqft.toLocaleString() }}</div>
        <div class="tb-detail__stat-label">Sq Ft</div>
      </div>
    </div>
    <p v-if="detailProperty.description" class="tb-detail__desc">{{ detailProperty.description }}</p>
  </section>

  <!-- Grid View -->
  <section v-else class="tb-pgrid">
    <div class="tb-pgrid__inner">
      <h2 class="tb-pgrid__heading">{{ heading }}</h2>
      <div v-if="properties.length" class="tb-pgrid__grid">
        <NuxtLink
          v-for="p in properties"
          :key="p.id"
          :to="`${currentPath || '/listings'}/${p.id}`"
          class="tb-pcard"
        >
          <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="tb-pcard__img" />
          <div v-else class="tb-pcard__img-placeholder">{{ p.title }}</div>
          <div class="tb-pcard__body">
            <div class="tb-pcard__price">{{ formatPrice(p.price) }}</div>
            <div class="tb-pcard__title">{{ p.title }}</div>
            <div class="tb-pcard__address">{{ p.address }}</div>
            <div class="tb-pcard__meta">
              <span>&#x1F6CF;&#xFE0F; {{ p.bedrooms }} bd</span>
              <span>&#x1F6BF; {{ p.bathrooms }} ba</span>
              <span v-if="p.area_sqft">&#x1F4D0; {{ p.area_sqft.toLocaleString() }} sqft</span>
            </div>
          </div>
        </NuxtLink>
      </div>
      <div v-else class="tb-pgrid__empty">No properties available yet.</div>
    </div>
  </section>
</template>

<style scoped>
/* Grid */
.tb-pgrid { padding: 80px 24px; background: var(--tb-bg-dark, #0f172a); }
.tb-pgrid__inner { max-width: var(--tb-max-width, 1200px); margin: 0 auto; }
.tb-pgrid__heading { text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 48px; color: var(--tb-text, #f1f5f9); }
.tb-pgrid__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.tb-pgrid__empty { text-align: center; padding: 48px; color: var(--tb-text-muted, #94a3b8); font-size: 1.1rem; }

/* Property Card */
.tb-pcard { background: var(--tb-bg-card, #1e293b); border: 1px solid var(--tb-border, #475569); border-radius: var(--tb-radius, 12px); overflow: hidden; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
.tb-pcard:hover { transform: translateY(-6px); border-color: var(--tb-primary, #f97316); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
.tb-pcard__img { width: 100%; height: 220px; object-fit: cover; }
.tb-pcard__img-placeholder { width: 100%; height: 220px; background: var(--tb-bg-surface, #334155); display: flex; align-items: center; justify-content: center; color: var(--tb-text-muted, #94a3b8); font-size: 0.9rem; }
.tb-pcard__body { padding: 20px; }
.tb-pcard__price { font-size: 1.4rem; font-weight: 700; color: var(--tb-primary, #f97316); margin-bottom: 4px; }
.tb-pcard__title { font-size: 1.05rem; font-weight: 600; margin-bottom: 4px; color: var(--tb-text, #f1f5f9); }
.tb-pcard__address { font-size: 0.85rem; color: var(--tb-text-muted, #94a3b8); margin-bottom: 12px; }
.tb-pcard__meta { display: flex; gap: 16px; font-size: 0.85rem; color: var(--tb-text-muted, #94a3b8); }
.tb-pcard__meta span { display: flex; align-items: center; gap: 4px; }

/* Detail */
.tb-detail { max-width: var(--tb-max-width, 1200px); margin: 0 auto; padding: 40px 24px; }
.tb-detail__img { width: 100%; max-height: 500px; object-fit: cover; border-radius: var(--tb-radius, 12px); margin-bottom: 32px; }
.tb-detail__header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
.tb-detail__price { font-size: 2rem; font-weight: 800; color: var(--tb-primary, #f97316); }
.tb-detail__title { font-size: 1.5rem; font-weight: 600; color: var(--tb-text, #f1f5f9); }
.tb-detail__address { color: var(--tb-text-muted, #94a3b8); margin-top: 4px; }
.tb-detail__stats { display: flex; gap: 24px; padding: 20px 0; border-top: 1px solid var(--tb-border, #475569); border-bottom: 1px solid var(--tb-border, #475569); margin-bottom: 24px; }
.tb-detail__stat { text-align: center; }
.tb-detail__stat-value { font-size: 1.5rem; font-weight: 700; color: var(--tb-text, #f1f5f9); }
.tb-detail__stat-label { font-size: 0.8rem; color: var(--tb-text-muted, #94a3b8); text-transform: uppercase; letter-spacing: 0.5px; }
.tb-detail__desc { color: var(--tb-text-muted, #94a3b8); line-height: 1.8; font-size: 1.05rem; }

@media (max-width: 768px) {
  .tb-detail__header { flex-direction: column; }
  .tb-detail__stats { flex-wrap: wrap; gap: 16px; }
}

@media (max-width: 480px) {
  .tb-pgrid__grid { grid-template-columns: 1fr; }
}
</style>
