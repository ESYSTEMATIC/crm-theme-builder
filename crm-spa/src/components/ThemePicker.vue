<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getThemes, createSite } from '../api/client.js'

const router = useRouter()

const themes = ref([])
const loading = ref(true)
const error = ref('')

const selectedTheme = ref(null)
const showCreateForm = ref(false)
const tenantId = ref('')
const slug = ref('')
const creating = ref(false)
const createError = ref('')

onMounted(async () => {
  try {
    const result = await getThemes()
    themes.value = result.data || []
  } catch (err) {
    error.value =
      err.response?.data?.message || err.message || 'Failed to load themes'
  } finally {
    loading.value = false
  }
})

function selectTheme(theme) {
  selectedTheme.value = theme
  showCreateForm.value = true
  createError.value = ''
  tenantId.value = ''
  slug.value = ''
}

function cancelCreate() {
  showCreateForm.value = false
  selectedTheme.value = null
  createError.value = ''
}

async function handleCreate() {
  if (!tenantId.value.trim() || !slug.value.trim()) {
    createError.value = 'Tenant ID and slug are required.'
    return
  }

  creating.value = true
  createError.value = ''

  try {
    const result = await createSite({
      tenant_id: tenantId.value.trim(),
      theme_key: selectedTheme.value.key,
      slug: slug.value.trim(),
    })
    const siteId = result.data.id
    router.push(`/sites/${siteId}`)
  } catch (err) {
    createError.value =
      err.response?.data?.message || err.message || 'Failed to create site'
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <div class="theme-picker">
    <div class="theme-picker__header">
      <h2 class="theme-picker__title">Choose a Theme</h2>
      <p class="theme-picker__subtitle">
        Select a theme to get started with your microsite.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="theme-picker__status">
      <div class="spinner spinner--lg"></div>
      <p>Loading themes...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="theme-picker__status theme-picker__status--error">
      <p>{{ error }}</p>
      <button class="btn btn--primary" @click="loading = true; error = ''; $nextTick(() => { getThemes().then(r => { themes = r.data || []; loading = false }).catch(e => { error = e.message; loading = false }) })">
        Retry
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="themes.length === 0" class="theme-picker__status">
      <p>No themes available. Please check back later.</p>
    </div>

    <!-- Theme Grid -->
    <div v-else class="theme-grid">
      <div
        v-for="theme in themes"
        :key="theme.id"
        class="theme-card"
        :class="{ 'theme-card--selected': selectedTheme?.id === theme.id }"
      >
        <div class="theme-card__preview">
          <div class="theme-card__preview-icon">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
              <rect x="4" y="4" width="40" height="40" rx="4" stroke="currentColor" stroke-width="2" />
              <rect x="4" y="4" width="40" height="10" rx="4" fill="currentColor" opacity="0.15" />
              <rect x="8" y="18" width="16" height="8" rx="2" fill="currentColor" opacity="0.1" />
              <rect x="8" y="30" width="32" height="3" rx="1" fill="currentColor" opacity="0.1" />
              <rect x="8" y="36" width="24" height="3" rx="1" fill="currentColor" opacity="0.08" />
            </svg>
          </div>
        </div>
        <div class="theme-card__body">
          <h3 class="theme-card__name">{{ theme.name }}</h3>
          <div class="theme-card__meta">
            <span class="theme-card__key">{{ theme.key }}</span>
            <span class="theme-card__version">v{{ theme.version }}</span>
          </div>
          <span
            v-if="theme.is_active"
            class="theme-card__badge theme-card__badge--active"
          >
            Active
          </span>
          <span v-else class="theme-card__badge theme-card__badge--inactive">
            Inactive
          </span>
        </div>
        <div class="theme-card__footer">
          <button
            class="btn btn--primary btn--full"
            @click="selectTheme(theme)"
            :disabled="!theme.is_active"
          >
            {{ selectedTheme?.id === theme.id ? 'Selected' : 'Select' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Form Overlay -->
    <Teleport to="body">
      <div v-if="showCreateForm" class="modal-overlay" @click.self="cancelCreate">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">Create New Site</h3>
            <button class="modal__close" @click="cancelCreate">&times;</button>
          </div>
          <div class="modal__body">
            <p class="modal__theme-info">
              Theme: <strong>{{ selectedTheme?.name }}</strong>
              <span class="theme-card__key">{{ selectedTheme?.key }}</span>
            </p>

            <div class="form-group">
              <label for="tenant-id">Tenant ID</label>
              <input
                id="tenant-id"
                v-model="tenantId"
                type="text"
                placeholder="e.g. tenant-abc-123"
                @keyup.enter="handleCreate"
              />
            </div>

            <div class="form-group">
              <label for="slug">Site Slug</label>
              <input
                id="slug"
                v-model="slug"
                type="text"
                placeholder="e.g. my-awesome-site"
                @keyup.enter="handleCreate"
              />
            </div>

            <div v-if="createError" class="form-error">
              {{ createError }}
            </div>
          </div>
          <div class="modal__footer">
            <button class="btn btn--ghost" @click="cancelCreate" :disabled="creating">
              Cancel
            </button>
            <button class="btn btn--primary" @click="handleCreate" :disabled="creating">
              <span v-if="creating" class="spinner" style="width:14px;height:14px;border-width:2px;"></span>
              {{ creating ? 'Creating...' : 'Create Site' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.theme-picker {
  max-width: 1100px;
  margin: 0 auto;
  padding: 40px 24px;
}

.theme-picker__header {
  text-align: center;
  margin-bottom: 40px;
}

.theme-picker__title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.theme-picker__subtitle {
  font-size: 1rem;
  color: #6b7280;
}

.theme-picker__status {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 60px 20px;
  color: #6b7280;
}

.theme-picker__status--error {
  color: #dc2626;
}

/* Theme Grid */
.theme-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}

.theme-card {
  background: #fff;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.theme-card:hover {
  border-color: #93c5fd;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

.theme-card--selected {
  border-color: #2563eb;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.15);
}

.theme-card__preview {
  background: linear-gradient(135deg, #f0f4ff 0%, #e8ecf8 100%);
  height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7fad;
}

.theme-card__preview-icon {
  opacity: 0.7;
}

.theme-card__body {
  padding: 16px 20px 8px;
}

.theme-card__name {
  font-size: 1.0625rem;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.theme-card__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.theme-card__key {
  font-size: 0.75rem;
  font-family: 'SF Mono', 'Fira Code', monospace;
  background: #f3f4f6;
  color: #6b7280;
  padding: 2px 8px;
  border-radius: 4px;
}

.theme-card__version {
  font-size: 0.75rem;
  color: #9ca3af;
}

.theme-card__badge {
  display: inline-block;
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 2px 8px;
  border-radius: 4px;
}

.theme-card__badge--active {
  background: #d1fae5;
  color: #065f46;
}

.theme-card__badge--inactive {
  background: #fee2e2;
  color: #991b1b;
}

.theme-card__footer {
  padding: 12px 20px 20px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: background-color 0.15s ease, opacity 0.15s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--primary {
  background-color: #2563eb;
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn--ghost {
  background-color: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
}

.btn--ghost:hover:not(:disabled) {
  background-color: #f9fafb;
}

.btn--full {
  width: 100%;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: modalIn 0.2s ease;
}

@keyframes modalIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 0;
}

.modal__title {
  font-size: 1.125rem;
  font-weight: 600;
}

.modal__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.modal__close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal__body {
  padding: 20px 24px;
}

.modal__theme-info {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 0 24px 24px;
}

.form-group {
  margin-bottom: 16px;
}

.form-error {
  background: #fef2f2;
  color: #dc2626;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.8125rem;
  margin-top: 4px;
}
</style>
