<script setup>
import BNavbar from './theme-b/BNavbar.vue'
import BBanner from './theme-b/BBanner.vue'
import BFeatures from './theme-b/BFeatures.vue'
import BCta from './theme-b/BCta.vue'
import BPropertyGrid from './theme-b/BPropertyGrid.vue'
import BSiteFooter from './theme-b/BSiteFooter.vue'

defineProps({
  sections: Array,
  settings: Object,
})
</script>

<template>
  <template v-for="(section, i) in sections" :key="i">
    <template v-if="section.visible !== false">
      <BNavbar v-if="section.type === 'navbar'" :props="section.props" :settings="settings" />
      <BBanner v-else-if="section.type === 'banner'" :props="section.props" />
      <BFeatures v-else-if="section.type === 'features'" :props="section.props" />
      <BCta v-else-if="section.type === 'cta'" :props="section.props" />
      <BPropertyGrid v-else-if="section.type === 'property-grid'" :props="section.props" />
      <BSiteFooter v-else-if="section.type === 'site-footer'" :props="section.props" />
    </template>
  </template>
</template>
