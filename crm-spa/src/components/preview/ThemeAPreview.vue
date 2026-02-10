<script setup>
import AHeader from './theme-a/AHeader.vue'
import AHero from './theme-a/AHero.vue'
import AGallery from './theme-a/AGallery.vue'
import ALeadForm from './theme-a/ALeadForm.vue'
import AFooter from './theme-a/AFooter.vue'

defineProps({
  sections: Array,
  settings: Object,
})
</script>

<template>
  <template v-for="(section, i) in sections" :key="i">
    <template v-if="section.visible !== false">
      <AHeader v-if="section.type === 'header'" :props="section.props" :settings="settings" />
      <AHero v-else-if="section.type === 'hero'" :props="section.props" />
      <AGallery v-else-if="section.type === 'gallery'" :props="section.props" />
      <ALeadForm v-else-if="section.type === 'lead-form'" :props="section.props" />
      <AFooter v-else-if="section.type === 'footer'" :props="section.props" />
    </template>
  </template>
</template>
