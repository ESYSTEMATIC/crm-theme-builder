<script setup>
import { computed } from 'vue'
import ThemeAPreview from './ThemeAPreview.vue'
import ThemeBPreview from './ThemeBPreview.vue'

const props = defineProps({
  themeKey: String,
  sections: Array,
  settings: Object,
  routeId: String,
})

const previewStyle = computed(() => {
  const brand = props.settings?.brand || {}
  if (props.themeKey?.startsWith('theme-a')) {
    return {
      '--primary-color': brand.primaryColor || '#2563eb',
      '--secondary-color': brand.secondaryColor || '#1e40af',
      '--font-family': brand.font || 'Inter, sans-serif',
      '--text-color': '#1f2937',
      '--text-light': '#6b7280',
      '--bg-color': '#ffffff',
      '--bg-light': '#f9fafb',
      '--border-color': '#e5e7eb',
      fontFamily: brand.font || 'Inter, sans-serif',
      backgroundColor: '#ffffff',
      color: '#1f2937',
    }
  }
  return {
    '--tb-primary': brand.primaryColor || '#f97316',
    '--tb-primary-dark': brand.secondaryColor || '#ea580c',
    '--tb-bg-dark': '#0f172a',
    '--tb-bg-card': '#1e293b',
    '--tb-bg-surface': '#334155',
    '--tb-text': '#f1f5f9',
    '--tb-text-muted': '#94a3b8',
    '--tb-border': '#475569',
    '--tb-font': brand.font || 'Poppins, sans-serif',
    fontFamily: brand.font || 'Poppins, sans-serif',
    backgroundColor: '#0f172a',
    color: '#f1f5f9',
  }
})

const isThemeA = computed(() => props.themeKey?.startsWith('theme-a'))
const isThemeB = computed(() => props.themeKey?.startsWith('theme-b'))
</script>

<template>
  <div class="preview-viewport" :style="previewStyle">
    <ThemeAPreview
      v-if="isThemeA"
      :sections="sections"
      :settings="settings"
    />
    <ThemeBPreview
      v-else-if="isThemeB"
      :sections="sections"
      :settings="settings"
    />
    <div v-else class="preview-unknown">
      Unknown theme: {{ themeKey }}
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap');

.preview-viewport {
  width: 100%;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

.preview-viewport * {
  box-sizing: border-box;
}

.preview-unknown {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #9ca3af;
  font-size: 0.875rem;
}
</style>
