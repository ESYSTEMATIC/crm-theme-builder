<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push('/')
}
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <div class="app-header__inner">
        <h1 class="app-header__title" @click="goHome">Microsite Builder</h1>
        <span class="app-header__badge">CRM</span>
      </div>
    </header>
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-header {
  background-color: #1a1a2e;
  color: #fff;
  padding: 0 24px;
  height: 56px;
  display: flex;
  align-items: center;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  z-index: 100;
}

.app-header__inner {
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
}

.app-header__title {
  font-size: 1.125rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  cursor: pointer;
  user-select: none;
}

.app-header__title:hover {
  opacity: 0.85;
}

.app-header__badge {
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background-color: #2563eb;
  color: #fff;
  padding: 2px 8px;
  border-radius: 4px;
}

.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}
</style>
