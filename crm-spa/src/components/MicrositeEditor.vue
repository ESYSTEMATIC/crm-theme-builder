<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { getSite, getDraft } from '../api/client.js'
import SectionEditor from './SectionEditor.vue'
import PreviewPublishBar from './PreviewPublishBar.vue'

const route = useRoute()
const siteId = computed(() => route.params.id)

// Loading & error state
const loading = ref(true)
const error = ref('')

// Site data from API
const site = ref(null)
const manifest = ref(null)

// Draft payload (editable)
const payload = reactive({
  settings: {
    brand: {
      primaryColor: '#2563eb',
      secondaryColor: '#1e40af',
      font: 'Inter, sans-serif',
    },
    seo: {
      titleSuffix: ' | My Website',
    },
  },
  routes: {},
})

// Track if payload has been changed since last save
const dirty = ref(false)
const originalPayloadSnapshot = ref('')

// UI state
const selectedRouteId = ref('')
const expandedSections = reactive({})
const showSettings = ref(false)

// Preview iframe state
const previewIframe = ref(null)
const previewLoading = ref(true)
const previewReady = ref(false)

// Debounce timer for postMessage
let postMessageTimer = null

// Computed: list of routes from manifest
const manifestRoutes = computed(() => {
  if (!manifest.value) return []
  return manifest.value.routes || []
})

// Computed: section types from manifest
const sectionTypes = computed(() => {
  if (!manifest.value) return {}
  return manifest.value.sectionTypes || {}
})

// Computed: sections for the currently selected page
const currentPageSections = computed(() => {
  if (!selectedRouteId.value || !payload.routes[selectedRouteId.value]) {
    return []
  }
  return payload.routes[selectedRouteId.value].sections || []
})

// Computed: page-level SEO for the currently selected page
const currentPageSeo = computed(() => {
  if (!selectedRouteId.value || !payload.routes[selectedRouteId.value]) {
    return { title: '' }
  }
  return payload.routes[selectedRouteId.value].seo || { title: '' }
})

// Watch for deep changes on payload to set dirty flag
watch(
  () => JSON.stringify(payload),
  (newVal) => {
    if (originalPayloadSnapshot.value && newVal !== originalPayloadSnapshot.value) {
      dirty.value = true
    }
  }
)

// Watch payload changes for live preview via postMessage
watch(
  () => JSON.stringify(payload),
  () => {
    debouncedPostMessage()
  }
)

// Watch route selection for live preview
watch(selectedRouteId, () => {
  sendPreviewUpdate()
})

onMounted(async () => {
  await loadData()
})

onBeforeUnmount(() => {
  if (postMessageTimer) clearTimeout(postMessageTimer)
})

async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const [siteResult, draftResult] = await Promise.all([
      getSite(siteId.value),
      getDraft(siteId.value),
    ])

    site.value = siteResult.data

    // Parse manifest
    const rawManifest = site.value?.theme?.manifest?.manifest_json
    if (rawManifest) {
      manifest.value =
        typeof rawManifest === 'string' ? JSON.parse(rawManifest) : rawManifest
    }

    // Load draft payload or build default
    const draftPayload = draftResult.data?.payload?.payload_json
    if (draftPayload) {
      const parsed =
        typeof draftPayload === 'string' ? JSON.parse(draftPayload) : draftPayload
      Object.assign(payload, parsed)
    } else {
      buildDefaultPayload()
    }

    // Set first route as selected
    if (manifestRoutes.value.length > 0) {
      selectedRouteId.value = manifestRoutes.value[0].id
    }

    // Take snapshot for dirty tracking
    originalPayloadSnapshot.value = JSON.stringify(payload)
    dirty.value = false

    // Initialize the live preview iframe
    await initPreview()
  } catch (err) {
    error.value =
      err.response?.data?.message || err.message || 'Failed to load site data'
  } finally {
    loading.value = false
  }
}

function buildDefaultPayload() {
  if (!manifest.value) return

  const routes = {}
  for (const routeDef of manifest.value.routes || []) {
    const routeSections = manifest.value.sections?.[routeDef.id] || []
    routes[routeDef.id] = {
      seo: { title: routeDef.label || routeDef.id },
      sections: routeSections.map((sectionType) => {
        const typeDef = manifest.value.sectionTypes?.[sectionType] || {}
        const defaultProps = {}
        for (const [key, propType] of Object.entries(typeDef.props || {})) {
          if (propType === 'string') defaultProps[key] = ''
          else if (propType === 'number') defaultProps[key] = 0
          else if (propType === 'array') defaultProps[key] = []
          else defaultProps[key] = ''
        }
        return {
          type: sectionType,
          visible: true,
          props: defaultProps,
        }
      }),
    }
  }

  payload.routes = routes
}

async function initPreview() {
  previewLoading.value = true
  previewReady.value = false
  try {
    await nextTick()
    if (previewIframe.value) {
      const routeId = selectedRouteId.value || ''
      previewIframe.value.src = `/api/sites/${siteId.value}/preview-frame?routeId=${encodeURIComponent(routeId)}`
    }
  } catch (err) {
    console.error('Failed to load preview frame:', err)
    previewLoading.value = false
  }
}

function onIframeLoad() {
  previewLoading.value = false
  previewReady.value = true
  // Send initial update to sync the current editing state
  sendPreviewUpdate()
}

function sendPreviewUpdate() {
  if (!previewReady.value || !previewIframe.value) return
  const currentRoute = payload.routes[selectedRouteId.value]
  if (!currentRoute) return

  const message = {
    type: 'MICROSITE_PREVIEW_UPDATE',
    settings: JSON.parse(JSON.stringify(payload.settings)),
    route: JSON.parse(JSON.stringify(currentRoute)),
    routeId: selectedRouteId.value,
  }

  previewIframe.value.contentWindow.postMessage(message, '*')
}

function debouncedPostMessage() {
  if (postMessageTimer) clearTimeout(postMessageTimer)
  postMessageTimer = setTimeout(() => {
    sendPreviewUpdate()
  }, 150)
}

function selectRoute(routeId) {
  selectedRouteId.value = routeId
}

function toggleSection(index) {
  const key = `${selectedRouteId.value}-${index}`
  expandedSections[key] = !expandedSections[key]
}

function isSectionExpanded(index) {
  const key = `${selectedRouteId.value}-${index}`
  return !!expandedSections[key]
}

function toggleSectionVisibility(index) {
  if (!payload.routes[selectedRouteId.value]) return
  const section = payload.routes[selectedRouteId.value].sections[index]
  if (section) {
    section.visible = !section.visible
  }
}

function updateSectionProps(index, newProps) {
  if (!payload.routes[selectedRouteId.value]) return
  const section = payload.routes[selectedRouteId.value].sections[index]
  if (section) {
    section.props = newProps
  }
}

function updatePageSeoTitle(value) {
  if (!payload.routes[selectedRouteId.value]) return
  if (!payload.routes[selectedRouteId.value].seo) {
    payload.routes[selectedRouteId.value].seo = { title: '' }
  }
  payload.routes[selectedRouteId.value].seo.title = value
}

function getSectionLabel(sectionType) {
  const def = sectionTypes.value[sectionType]
  return def ? def.label : sectionType
}

function handleSaved() {
  originalPayloadSnapshot.value = JSON.stringify(payload)
  dirty.value = false
  // Rebuild preview after save so MinIO draft is fresh
  initPreview()
}

function handlePublished() {
  originalPayloadSnapshot.value = JSON.stringify(payload)
  dirty.value = false
}
</script>

<template>
  <div class="editor">
    <!-- Loading -->
    <div v-if="loading" class="editor__loading">
      <div class="spinner spinner--lg"></div>
      <p>Loading site editor...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="editor__error">
      <div class="editor__error-box">
        <p>{{ error }}</p>
        <button class="editor__btn editor__btn--primary" @click="loadData">
          Retry
        </button>
      </div>
    </div>

    <!-- Editor Layout -->
    <template v-else>
      <!-- Site Info Bar -->
      <div class="editor__info-bar">
        <div class="editor__info-bar-inner">
          <div class="editor__info-details">
            <h2 class="editor__site-name">{{ site?.slug || 'Untitled' }}</h2>
            <div class="editor__site-meta">
              <span class="editor__meta-chip">
                Theme: {{ site?.theme?.name || manifest?.name || 'Unknown' }}
              </span>
              <span class="editor__meta-chip">
                ID: {{ siteId }}
              </span>
              <span v-if="site?.published_version_id" class="editor__meta-chip editor__meta-chip--published">
                Published
              </span>
              <span v-else class="editor__meta-chip editor__meta-chip--draft">
                Draft Only
              </span>
            </div>
          </div>
          <button
            class="editor__btn editor__btn--settings"
            :class="{ 'editor__btn--active': showSettings }"
            @click="showSettings = !showSettings"
          >
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M6.5 1L7.2 3.1C7.6 3.3 8 3.5 8.3 3.8L10.4 3.1L11.9 5.7L10.3 7.3C10.3 7.5 10.4 7.8 10.4 8C10.4 8.2 10.3 8.5 10.3 8.7L11.9 10.3L10.4 12.9L8.3 12.2C8 12.5 7.6 12.7 7.2 12.9L6.5 15H3.5L2.8 12.9C2.4 12.7 2 12.5 1.7 12.2L-0.4 12.9L-1.9 10.3L-0.3 8.7C-0.3 8.5 -0.4 8.2 -0.4 8C-0.4 7.8 -0.3 7.5 -0.3 7.3L-1.9 5.7L-0.4 3.1L1.7 3.8C2 3.5 2.4 3.3 2.8 3.1L3.5 1H6.5Z" transform="translate(3 0)" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" fill="none"/>
              <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.2" fill="none"/>
            </svg>
            Settings
          </button>
        </div>
      </div>

      <!-- Split Workspace: Editor Panel + Preview -->
      <div class="editor__workspace">
        <!-- Left: Editing Panel -->
        <aside class="editor__edit-panel">
          <!-- Page Tabs -->
          <div class="editor__page-tabs">
            <button
              v-for="routeDef in manifestRoutes"
              :key="routeDef.id"
              class="editor__page-tab"
              :class="{ 'editor__page-tab--active': selectedRouteId === routeDef.id }"
              @click="selectRoute(routeDef.id)"
            >
              {{ routeDef.label }}
            </button>
          </div>

          <!-- Scrollable content area -->
          <div class="editor__edit-scroll">
            <!-- Settings Panel (collapsible) -->
            <div v-if="showSettings" class="editor__settings-panel">
              <div class="editor__panel-header">
                <h3 class="editor__panel-title">Global Settings</h3>
                <button class="editor__panel-close" @click="showSettings = false">&times;</button>
              </div>

              <div class="editor__settings-body">
                <div class="editor__settings-group">
                  <h4 class="editor__settings-group-title">Brand</h4>

                  <div class="editor__field">
                    <label for="setting-primary-color">Primary Color</label>
                    <div class="editor__color-field">
                      <input
                        id="setting-primary-color"
                        type="color"
                        :value="payload.settings.brand.primaryColor"
                        @input="payload.settings.brand.primaryColor = $event.target.value"
                        class="editor__color-input"
                      />
                      <input
                        type="text"
                        :value="payload.settings.brand.primaryColor"
                        @input="payload.settings.brand.primaryColor = $event.target.value"
                        class="editor__color-text"
                      />
                    </div>
                  </div>

                  <div class="editor__field">
                    <label for="setting-secondary-color">Secondary Color</label>
                    <div class="editor__color-field">
                      <input
                        id="setting-secondary-color"
                        type="color"
                        :value="payload.settings.brand.secondaryColor"
                        @input="payload.settings.brand.secondaryColor = $event.target.value"
                        class="editor__color-input"
                      />
                      <input
                        type="text"
                        :value="payload.settings.brand.secondaryColor"
                        @input="payload.settings.brand.secondaryColor = $event.target.value"
                        class="editor__color-text"
                      />
                    </div>
                  </div>

                  <div class="editor__field">
                    <label for="setting-font">Font Family</label>
                    <input
                      id="setting-font"
                      type="text"
                      :value="payload.settings.brand.font"
                      @input="payload.settings.brand.font = $event.target.value"
                      placeholder="e.g. Inter, sans-serif"
                    />
                  </div>
                </div>

                <div class="editor__settings-group">
                  <h4 class="editor__settings-group-title">SEO</h4>

                  <div class="editor__field">
                    <label for="setting-title-suffix">Title Suffix</label>
                    <input
                      id="setting-title-suffix"
                      type="text"
                      :value="payload.settings.seo.titleSuffix"
                      @input="payload.settings.seo.titleSuffix = $event.target.value"
                      placeholder="e.g.  | My Website"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Page Content -->
            <div v-if="selectedRouteId" class="editor__page-content">
              <div class="editor__page-header">
                <h3 class="editor__page-title">
                  {{ manifestRoutes.find((r) => r.id === selectedRouteId)?.label || selectedRouteId }}
                </h3>
                <span class="editor__page-route">
                  {{ manifestRoutes.find((r) => r.id === selectedRouteId)?.path || '' }}
                </span>
              </div>

              <!-- Page SEO -->
              <div class="editor__page-seo">
                <label for="page-seo-title">Page Title</label>
                <input
                  id="page-seo-title"
                  type="text"
                  :value="currentPageSeo.title"
                  @input="updatePageSeoTitle($event.target.value)"
                  placeholder="Page title for SEO"
                />
              </div>

              <!-- Sections List -->
              <div class="editor__sections">
                <h4 class="editor__sections-heading">Sections</h4>

                <div v-if="currentPageSections.length === 0" class="editor__sections-empty">
                  No sections defined for this page.
                </div>

                <div
                  v-for="(section, index) in currentPageSections"
                  :key="`${selectedRouteId}-${index}`"
                  class="editor__section-card"
                  :class="{ 'editor__section-card--hidden': !section.visible }"
                >
                  <div class="editor__section-header" @click="toggleSection(index)">
                    <div class="editor__section-info">
                      <span
                        class="editor__section-expand"
                        :class="{ 'editor__section-expand--open': isSectionExpanded(index) }"
                      >
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                          <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </span>
                      <span class="editor__section-label">
                        {{ getSectionLabel(section.type) }}
                      </span>
                      <span class="editor__section-type-badge">{{ section.type }}</span>
                    </div>

                    <div class="editor__section-actions" @click.stop>
                      <label class="editor__visibility-toggle">
                        <input
                          type="checkbox"
                          :checked="section.visible"
                          @change="toggleSectionVisibility(index)"
                        />
                        <span class="editor__visibility-label">
                          {{ section.visible ? 'Visible' : 'Hidden' }}
                        </span>
                      </label>
                    </div>
                  </div>

                  <div v-if="isSectionExpanded(index)" class="editor__section-body">
                    <SectionEditor
                      :section-type="section.type"
                      :props="section.props"
                      :section-types="sectionTypes"
                      @update="updateSectionProps(index, $event)"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="editor__no-page">
              <p>Select a page to start editing.</p>
            </div>
          </div>
        </aside>

        <!-- Right: Live Preview -->
        <div class="editor__preview-panel">
          <div v-if="previewLoading" class="editor__preview-loading">
            <div class="spinner spinner--lg"></div>
            <p>Loading preview...</p>
          </div>
          <iframe
            ref="previewIframe"
            class="editor__preview-iframe"
            @load="onIframeLoad"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
          ></iframe>
        </div>
      </div>

      <!-- Bottom Bar -->
      <PreviewPublishBar
        :site-id="siteId"
        :dirty="dirty"
        :payload="payload"
        @saved="handleSaved"
        @published="handlePublished"
      />
    </template>
  </div>
</template>

<style scoped>
.editor {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding-bottom: 56px; /* space for the fixed bottom bar */
}

/* Loading & Error */
.editor__loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  color: #6b7280;
  padding: 60px;
}

.editor__error {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;
}

.editor__error-box {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  padding: 24px 32px;
  text-align: center;
  color: #dc2626;
}

.editor__error-box p {
  margin-bottom: 16px;
}

/* Info Bar */
.editor__info-bar {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 24px;
  flex-shrink: 0;
}

.editor__info-bar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
}

.editor__info-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.editor__site-name {
  font-size: 1.0625rem;
  font-weight: 600;
  color: #1a1a2e;
}

.editor__site-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.editor__meta-chip {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #6b7280;
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 4px;
}

.editor__meta-chip--published {
  background: #d1fae5;
  color: #065f46;
}

.editor__meta-chip--draft {
  background: #fef3c7;
  color: #92400e;
}

/* Buttons */
.editor__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.editor__btn--primary {
  background: #2563eb;
  color: #fff;
}

.editor__btn--primary:hover {
  background: #1d4ed8;
}

.editor__btn--settings {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
}

.editor__btn--settings:hover {
  background: #e5e7eb;
}

.editor__btn--active {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}

/* ==========================================
   Split Workspace Layout
   ========================================== */

.editor__workspace {
  flex: 1;
  display: flex;
  overflow: hidden;
}

/* Left: Editing Panel */
.editor__edit-panel {
  width: 420px;
  min-width: 360px;
  background: #fff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

/* Page Tabs */
.editor__page-tabs {
  display: flex;
  gap: 2px;
  padding: 8px 12px;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
  flex-shrink: 0;
  background: #f9fafb;
}

.editor__page-tab {
  padding: 6px 14px;
  font-size: 0.8125rem;
  font-weight: 500;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background: transparent;
  color: #6b7280;
  white-space: nowrap;
  transition: background-color 0.1s ease, color 0.1s ease;
}

.editor__page-tab:hover {
  background: #e5e7eb;
  color: #374151;
}

.editor__page-tab--active {
  background: #2563eb;
  color: #fff;
}

.editor__page-tab--active:hover {
  background: #1d4ed8;
  color: #fff;
}

/* Scrollable edit content */
.editor__edit-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

/* Settings Panel */
.editor__settings-panel {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  margin-bottom: 16px;
  overflow: hidden;
}

.editor__panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
}

.editor__panel-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1a1a2e;
}

.editor__panel-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #9ca3af;
  cursor: pointer;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  padding: 0;
}

.editor__panel-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.editor__settings-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.editor__settings-group-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.editor__field {
  margin-bottom: 12px;
}

.editor__field label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #4b5563;
  margin-bottom: 6px;
}

.editor__color-field {
  display: flex;
  gap: 8px;
  align-items: center;
}

.editor__color-input {
  width: 36px;
  height: 32px;
  padding: 2px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  background: none;
}

.editor__color-text {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-family: 'SF Mono', 'Fira Code', monospace;
}

.editor__color-text:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

/* Page Content */
.editor__page-content {
  /* No max-width needed - panel constrains it */
}

.editor__page-header {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 14px;
}

.editor__page-title {
  font-size: 1.0625rem;
  font-weight: 600;
  color: #1a1a2e;
}

.editor__page-route {
  font-size: 0.75rem;
  color: #9ca3af;
  font-family: 'SF Mono', 'Fira Code', monospace;
}

/* Page SEO */
.editor__page-seo {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 16px;
}

.editor__page-seo label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #4b5563;
  margin-bottom: 6px;
}

.editor__page-seo input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.8125rem;
}

.editor__page-seo input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

/* Sections */
.editor__sections {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.editor__sections-heading {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #9ca3af;
  margin-bottom: 2px;
}

.editor__sections-empty {
  background: #f9fafb;
  border: 1px dashed #d1d5db;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  color: #9ca3af;
  font-size: 0.8125rem;
}

/* Section Card */
.editor__section-card {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  transition: border-color 0.15s ease;
}

.editor__section-card:hover {
  border-color: #d1d5db;
}

.editor__section-card--hidden {
  opacity: 0.55;
}

.editor__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.1s ease;
}

.editor__section-header:hover {
  background: #f3f4f6;
}

.editor__section-info {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.editor__section-expand {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  color: #9ca3af;
  transition: transform 0.15s ease;
  flex-shrink: 0;
}

.editor__section-expand--open {
  transform: rotate(90deg);
}

.editor__section-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #1a1a2e;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.editor__section-type-badge {
  font-size: 0.625rem;
  font-family: 'SF Mono', 'Fira Code', monospace;
  background: #e5e7eb;
  color: #9ca3af;
  padding: 1px 5px;
  border-radius: 3px;
  flex-shrink: 0;
}

.editor__section-actions {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.editor__visibility-toggle {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  font-size: 0.6875rem;
}

.editor__visibility-toggle input[type='checkbox'] {
  width: 14px;
  height: 14px;
  accent-color: #2563eb;
  cursor: pointer;
}

.editor__visibility-label {
  color: #6b7280;
  font-weight: 500;
}

.editor__section-body {
  padding: 0 12px 12px;
  border-top: 1px solid #e5e7eb;
  padding-top: 12px;
}

/* No page selected */
.editor__no-page {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  font-size: 0.875rem;
}

/* ==========================================
   Right: Live Preview Panel
   ========================================== */

.editor__preview-panel {
  flex: 1;
  position: relative;
  background: #e5e7eb;
  display: flex;
  align-items: stretch;
}

.editor__preview-loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: #f5f7fa;
  color: #6b7280;
  font-size: 0.875rem;
  z-index: 5;
}

.editor__preview-iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: #fff;
}
</style>
