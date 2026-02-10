<script setup>
import { ref } from 'vue'
import { saveDraft, createPreviewSession, publishSite } from '../api/client.js'

const props = defineProps({
  siteId: {
    type: [String, Number],
    required: true,
  },
  dirty: {
    type: Boolean,
    default: false,
  },
  payload: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['saved', 'published'])

const saving = ref(false)
const previewing = ref(false)
const publishing = ref(false)
const showPublishConfirm = ref(false)

const toastMessage = ref('')
const toastType = ref('success')
const toastVisible = ref(false)
let toastTimer = null

function showToast(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  toastVisible.value = true
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toastVisible.value = false
  }, 3000)
}

async function handleSave() {
  saving.value = true
  try {
    await saveDraft(props.siteId, props.payload)
    showToast('Draft saved successfully!', 'success')
    emit('saved')
  } catch (err) {
    showToast(
      err.response?.data?.message || err.message || 'Failed to save draft',
      'error'
    )
  } finally {
    saving.value = false
  }
}

async function handlePreview() {
  previewing.value = true
  try {
    const result = await createPreviewSession(props.siteId)
    const previewUrl = result.data.preview_url
    if (previewUrl) {
      window.open(previewUrl, '_blank')
      showToast('Preview opened in new tab', 'info')
    } else {
      showToast('No preview URL returned', 'error')
    }
  } catch (err) {
    showToast(
      err.response?.data?.message || err.message || 'Failed to create preview',
      'error'
    )
  } finally {
    previewing.value = false
  }
}

function confirmPublish() {
  showPublishConfirm.value = true
}

function cancelPublish() {
  showPublishConfirm.value = false
}

async function handlePublish() {
  showPublishConfirm.value = false
  publishing.value = true
  try {
    await publishSite(props.siteId)
    showToast('Site published successfully!', 'success')
    emit('published')
  } catch (err) {
    showToast(
      err.response?.data?.message || err.message || 'Failed to publish site',
      'error'
    )
  } finally {
    publishing.value = false
  }
}
</script>

<template>
  <div class="bar">
    <div class="bar__inner">
      <div class="bar__status">
        <span v-if="dirty" class="bar__indicator bar__indicator--dirty">Unsaved changes</span>
        <span v-else class="bar__indicator bar__indicator--clean">Up to date</span>
      </div>

      <div class="bar__actions">
        <button
          class="bar__btn bar__btn--save"
          :disabled="saving || !dirty"
          @click="handleSave"
        >
          <span v-if="saving" class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:#fff;"></span>
          {{ saving ? 'Saving...' : 'Save Draft' }}
        </button>

        <button
          class="bar__btn bar__btn--preview"
          :disabled="previewing"
          @click="handlePreview"
        >
          <span v-if="previewing" class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:#fff;"></span>
          {{ previewing ? 'Loading...' : 'Preview' }}
        </button>

        <button
          class="bar__btn bar__btn--publish"
          :disabled="publishing || dirty"
          @click="confirmPublish"
        >
          <span v-if="publishing" class="spinner" style="width:14px;height:14px;border-width:2px;border-top-color:#fff;"></span>
          {{ publishing ? 'Publishing...' : 'Publish' }}
        </button>
      </div>
    </div>

    <!-- Publish Confirmation Dialog -->
    <Teleport to="body">
      <div v-if="showPublishConfirm" class="confirm-overlay" @click.self="cancelPublish">
        <div class="confirm-dialog">
          <h3 class="confirm-dialog__title">Publish Site?</h3>
          <p class="confirm-dialog__message">
            This will make the current draft live. Visitors will see the published version immediately.
          </p>
          <div class="confirm-dialog__actions">
            <button class="bar__btn bar__btn--ghost" @click="cancelPublish">Cancel</button>
            <button class="bar__btn bar__btn--publish" @click="handlePublish">Yes, Publish</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <div
        v-if="toastVisible"
        class="toast"
        :class="`toast--${toastType}`"
      >
        {{ toastMessage }}
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.06);
  z-index: 90;
  padding: 0 24px;
}

.bar__inner {
  max-width: 1400px;
  margin: 0 auto;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.bar__status {
  display: flex;
  align-items: center;
}

.bar__indicator {
  font-size: 0.8125rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

.bar__indicator::before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.bar__indicator--dirty::before {
  background-color: #f59e0b;
}

.bar__indicator--dirty {
  color: #92400e;
}

.bar__indicator--clean::before {
  background-color: #10b981;
}

.bar__indicator--clean {
  color: #065f46;
}

.bar__actions {
  display: flex;
  gap: 10px;
}

.bar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 18px;
  font-size: 0.8125rem;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.15s ease, opacity 0.15s ease;
  color: #fff;
}

.bar__btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.bar__btn--save {
  background-color: #2563eb;
}

.bar__btn--save:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.bar__btn--preview {
  background-color: #059669;
}

.bar__btn--preview:hover:not(:disabled) {
  background-color: #047857;
}

.bar__btn--publish {
  background-color: #d97706;
}

.bar__btn--publish:hover:not(:disabled) {
  background-color: #b45309;
}

.bar__btn--ghost {
  background-color: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
}

.bar__btn--ghost:hover:not(:disabled) {
  background-color: #f9fafb;
}

/* Confirm Dialog */
.confirm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.confirm-dialog {
  background: #fff;
  border-radius: 16px;
  padding: 28px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: dialogIn 0.2s ease;
}

@keyframes dialogIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-dialog__title {
  font-size: 1.0625rem;
  font-weight: 600;
  margin-bottom: 8px;
  color: #1a1a2e;
}

.confirm-dialog__message {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 24px;
  line-height: 1.5;
}

.confirm-dialog__actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
