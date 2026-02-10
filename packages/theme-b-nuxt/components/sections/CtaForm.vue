<script setup>
const props = defineProps({
  props: Object,
  settings: Object,
  siteId: Number,
})

const heading = computed(() => props.props?.heading || 'Ready to find your dream home?')
const buttonLabel = computed(() => props.props?.buttonLabel || 'Get Started')

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
  <section class="tb-cta">
    <div class="tb-cta__inner">
      <h2 class="tb-cta__heading">{{ heading }}</h2>

      <div v-if="success" class="tb-cta__success">
        Thank you! We'll be in touch soon.
      </div>

      <form v-else class="tb-cta__form" @submit.prevent="handleSubmit">
        <input v-model="form.name" class="tb-input" type="text" placeholder="Your Name" required />
        <input v-model="form.email" class="tb-input" type="email" placeholder="Email Address" required />
        <input v-model="form.phone" class="tb-input tb-input--full" type="tel" placeholder="Phone (optional)" />
        <textarea v-model="form.message" class="tb-input tb-input--full" placeholder="Message" rows="3"></textarea>
        <button type="submit" class="tb-btn tb-cta__submit" :disabled="submitting">
          {{ submitting ? 'Sending...' : buttonLabel }}
        </button>
        <p v-if="error" class="tb-cta__error">{{ error }}</p>
      </form>
    </div>
  </section>
</template>

<style scoped>
.tb-cta { padding: 80px 24px; text-align: center; background: var(--tb-bg-dark, #0f172a); }
.tb-cta__inner { max-width: 600px; margin: 0 auto; }
.tb-cta__heading { font-size: 2rem; font-weight: 700; margin-bottom: 32px; color: var(--tb-text, #f1f5f9); }
.tb-cta__form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.tb-cta__form .tb-input--full { grid-column: 1 / -1; }
.tb-input { width: 100%; padding: 14px 18px; background: var(--tb-bg-card, #1e293b); border: 1px solid var(--tb-border, #475569); border-radius: 8px; color: var(--tb-text, #f1f5f9); font-family: inherit; font-size: 0.95rem; transition: border-color 0.2s; }
.tb-input:focus { outline: none; border-color: var(--tb-primary, #f97316); }
.tb-input::placeholder { color: var(--tb-text-muted, #94a3b8); }
textarea.tb-input { min-height: 100px; resize: vertical; }
.tb-cta__submit { grid-column: 1 / -1; margin-top: 8px; }
.tb-btn { display: inline-block; padding: 14px 36px; background: var(--tb-primary, #f97316); color: #fff; font-weight: 600; font-size: 1rem; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 24px rgba(249,115,22,0.3); font-family: inherit; transition: background 0.2s, transform 0.2s, box-shadow 0.2s; }
.tb-btn:hover { background: var(--tb-primary-dark, #ea580c); transform: translateY(-2px); box-shadow: 0 6px 32px rgba(249,115,22,0.4); }
.tb-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.tb-cta__success { padding: 16px; background: rgba(34,197,94,0.15); border: 1px solid #22c55e; border-radius: 8px; color: #22c55e; font-weight: 500; }
.tb-cta__error { grid-column: 1 / -1; color: #ef4444; font-size: 0.9rem; margin-top: 4px; }

@media (max-width: 768px) {
  .tb-cta__form { grid-template-columns: 1fr; }
  .tb-cta__form .tb-input--full { grid-column: auto; }
}
</style>
