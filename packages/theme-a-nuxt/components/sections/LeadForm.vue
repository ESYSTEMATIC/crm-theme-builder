<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
  siteId: Number,
})

const headline = computed(() => props.props?.headline || 'Get in Touch')
const submitLabel = computed(() => props.props?.submitLabel || 'Send Message')

const form = reactive({
  name: '',
  email: '',
  phone: '',
  message: '',
})

const submitting = ref(false)
const success = ref(false)
const error = ref('')

async function handleSubmit() {
  if (!form.name || !form.email) return

  submitting.value = true
  error.value = ''

  try {
    await $fetch('/api/leads', {
      method: 'POST',
      body: {
        site_id: props.siteId,
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        message: form.message || null,
      },
    })
    success.value = true
    form.name = ''
    form.email = ''
    form.phone = ''
    form.message = ''
  } catch (e) {
    error.value = 'Something went wrong. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="ms-lead-form">
    <div class="ms-lead-form__inner">
      <h2 class="ms-lead-form__headline">{{ headline }}</h2>

      <div v-if="success" class="ms-lead-form__message ms-lead-form__message--success">
        Thank you! We'll be in touch soon.
      </div>

      <form v-else class="ms-lead-form__form" @submit.prevent="handleSubmit">
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Name</label>
          <input v-model="form.name" class="ms-lead-form__input" type="text" placeholder="Your full name" required />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Email</label>
          <input v-model="form.email" class="ms-lead-form__input" type="email" placeholder="your@email.com" required />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Phone</label>
          <input v-model="form.phone" class="ms-lead-form__input" type="tel" placeholder="(555) 123-4567" />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Message</label>
          <textarea v-model="form.message" class="ms-lead-form__textarea" placeholder="How can we help you?" rows="4"></textarea>
        </div>
        <button type="submit" class="ms-lead-form__submit" :disabled="submitting">
          {{ submitting ? 'Sending...' : submitLabel }}
        </button>
        <div v-if="error" class="ms-lead-form__message ms-lead-form__message--error">{{ error }}</div>
      </form>
    </div>
  </section>
</template>

<style scoped>
.ms-lead-form { padding: 80px 24px; background-color: var(--bg-light, #f9fafb); }
.ms-lead-form__inner { max-width: 560px; margin: 0 auto; }
.ms-lead-form__headline { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 32px; color: var(--text-color, #1f2937); letter-spacing: -0.025em; }
.ms-lead-form__form { display: flex; flex-direction: column; gap: 16px; }
.ms-lead-form__field { display: flex; flex-direction: column; gap: 6px; }
.ms-lead-form__label { font-size: 0.875rem; font-weight: 600; color: var(--text-color, #1f2937); }
.ms-lead-form__input, .ms-lead-form__textarea { width: 100%; padding: 12px 16px; font-size: 1rem; font-family: inherit; color: var(--text-color, #1f2937); background-color: var(--bg-color, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: var(--radius-md, 8px); transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
.ms-lead-form__input:focus, .ms-lead-form__textarea:focus { border-color: var(--primary-color, #2563eb); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
.ms-lead-form__textarea { min-height: 120px; resize: vertical; }
.ms-lead-form__submit { display: inline-flex; align-items: center; justify-content: center; padding: 14px 32px; font-size: 1rem; font-weight: 600; font-family: inherit; color: var(--text-inverse, #fff); background-color: var(--primary-color, #2563eb); border: none; border-radius: var(--radius-md, 8px); cursor: pointer; transition: background-color 0.2s, transform 0.2s; margin-top: 8px; }
.ms-lead-form__submit:hover { background-color: var(--secondary-color, #1e40af); transform: translateY(-1px); }
.ms-lead-form__submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.ms-lead-form__message { text-align: center; padding: 16px; border-radius: var(--radius-md, 8px); font-size: 0.9375rem; font-weight: 500; margin-top: 8px; }
.ms-lead-form__message--success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.ms-lead-form__message--error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

@media (max-width: 768px) {
  .ms-lead-form { padding: 48px 16px; }
  .ms-lead-form__headline { font-size: 1.5rem; }
}
</style>
