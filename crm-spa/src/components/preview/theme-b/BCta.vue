<script setup>
defineProps({ props: Object })
</script>

<template>
  <section class="tb-cta">
    <div class="tb-cta__inner">
      <h2 v-if="props?.heading" class="tb-cta__heading">{{ props.heading }}</h2>
      <form class="tb-cta__form" @submit.prevent>
        <input class="tb-input" type="text" placeholder="Your Name" disabled />
        <input class="tb-input" type="email" placeholder="Email Address" disabled />
        <input class="tb-input" type="tel" placeholder="Phone (optional)" disabled />
        <textarea class="tb-input tb-input--full" placeholder="Message" rows="3" disabled></textarea>
        <button type="button" class="tb-btn tb-cta__submit" disabled>
          {{ props?.buttonLabel || 'Submit' }}
        </button>
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
.tb-input { width: 100%; padding: 14px 18px; background: var(--tb-bg-card, #1e293b); border: 1px solid var(--tb-border, #475569); border-radius: 8px; color: var(--tb-text, #f1f5f9); font-family: inherit; font-size: 0.95rem; }
.tb-input::placeholder { color: var(--tb-text-muted, #94a3b8); }
textarea.tb-input { min-height: 100px; resize: vertical; }
.tb-cta__submit { grid-column: 1 / -1; margin-top: 8px; }
.tb-btn { display: inline-block; padding: 14px 36px; background: var(--tb-primary, #f97316); color: #fff; font-weight: 600; font-size: 1rem; border: none; border-radius: 50px; cursor: default; box-shadow: 0 4px 24px rgba(249,115,22,0.3); font-family: inherit; opacity: 0.85; }
</style>
