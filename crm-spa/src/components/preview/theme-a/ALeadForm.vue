<script setup>
defineProps({ props: Object })
</script>

<template>
  <section class="ms-lead-form">
    <div class="ms-lead-form__inner">
      <h2 v-if="props?.headline" class="ms-lead-form__headline">{{ props.headline }}</h2>
      <form class="ms-lead-form__form" @submit.prevent>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Name</label>
          <input class="ms-lead-form__input" type="text" placeholder="Your full name" disabled />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Email</label>
          <input class="ms-lead-form__input" type="email" placeholder="your@email.com" disabled />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Phone</label>
          <input class="ms-lead-form__input" type="tel" placeholder="(555) 123-4567" disabled />
        </div>
        <div class="ms-lead-form__field">
          <label class="ms-lead-form__label">Message</label>
          <textarea class="ms-lead-form__textarea" placeholder="How can we help you?" rows="4" disabled></textarea>
        </div>
        <button type="button" class="ms-lead-form__submit" disabled>
          {{ props?.submitLabel || 'Submit' }}
        </button>
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
.ms-lead-form__input, .ms-lead-form__textarea { width: 100%; padding: 12px 16px; font-size: 1rem; color: var(--text-color, #1f2937); background-color: var(--bg-color, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; outline: none; font-family: inherit; }
.ms-lead-form__textarea { min-height: 120px; resize: vertical; }
.ms-lead-form__submit { display: inline-flex; align-items: center; justify-content: center; padding: 14px 32px; font-size: 1rem; font-weight: 600; color: #fff; background-color: var(--primary-color, #2563eb); border: none; border-radius: 8px; cursor: default; margin-top: 8px; font-family: inherit; opacity: 0.85; }
</style>
