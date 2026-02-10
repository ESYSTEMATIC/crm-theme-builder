<script setup>
import { computed } from 'vue'

const props = defineProps({
  sectionType: {
    type: String,
    required: true,
  },
  props: {
    type: Object,
    required: true,
  },
  sectionTypes: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update'])

const typeDef = computed(() => {
  return props.sectionTypes[props.sectionType] || { label: props.sectionType, props: {} }
})

const propDefs = computed(() => {
  return typeDef.value.props || {}
})

function updateProp(key, value) {
  const updated = { ...props.props, [key]: value }
  emit('update', updated)
}

function updateArrayProp(key, rawValue) {
  try {
    const parsed = JSON.parse(rawValue)
    const updated = { ...props.props, [key]: parsed }
    emit('update', updated)
  } catch {
    // Don't emit if JSON is invalid -- user is still typing
  }
}

function getArrayDisplayValue(value) {
  if (value === undefined || value === null) return '[]'
  if (typeof value === 'string') return value
  try {
    return JSON.stringify(value, null, 2)
  } catch {
    return '[]'
  }
}

function getPropType(typeName) {
  if (!typeName) return 'string'
  const lower = String(typeName).toLowerCase()
  if (lower === 'number') return 'number'
  if (lower === 'array') return 'array'
  return 'string'
}
</script>

<template>
  <div class="section-editor">
    <div
      v-for="(typeName, key) in propDefs"
      :key="key"
      class="section-editor__field"
    >
      <label :for="`prop-${sectionType}-${key}`" class="section-editor__label">
        {{ key }}
        <span class="section-editor__type-badge">{{ typeName }}</span>
      </label>

      <!-- String input -->
      <input
        v-if="getPropType(typeName) === 'string'"
        :id="`prop-${sectionType}-${key}`"
        type="text"
        :value="props.props[key] || ''"
        @input="updateProp(key, $event.target.value)"
        :placeholder="`Enter ${key}...`"
        class="section-editor__input"
      />

      <!-- Number input -->
      <input
        v-else-if="getPropType(typeName) === 'number'"
        :id="`prop-${sectionType}-${key}`"
        type="number"
        :value="props.props[key] || 0"
        @input="updateProp(key, Number($event.target.value))"
        :placeholder="`Enter ${key}...`"
        class="section-editor__input"
      />

      <!-- Array input (JSON textarea) -->
      <div v-else-if="getPropType(typeName) === 'array'" class="section-editor__array-field">
        <textarea
          :id="`prop-${sectionType}-${key}`"
          :value="getArrayDisplayValue(props.props[key])"
          @input="updateArrayProp(key, $event.target.value)"
          rows="5"
          class="section-editor__textarea"
          :placeholder="`Enter JSON array for ${key}...`"
          spellcheck="false"
        ></textarea>
        <p class="section-editor__hint">Enter valid JSON array. Changes save when JSON is valid.</p>
      </div>
    </div>

    <p v-if="Object.keys(propDefs).length === 0" class="section-editor__empty">
      No editable properties for this section type.
    </p>
  </div>
</template>

<style scoped>
.section-editor {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-editor__field {
  display: flex;
  flex-direction: column;
}

.section-editor__label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
}

.section-editor__type-badge {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #9ca3af;
  background: #f3f4f6;
  padding: 1px 6px;
  border-radius: 3px;
  font-family: 'SF Mono', 'Fira Code', monospace;
}

.section-editor__input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  width: 100%;
  background: #fff;
}

.section-editor__input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.section-editor__textarea {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-family: 'SF Mono', 'Fira Code', monospace;
  outline: none;
  resize: vertical;
  min-height: 80px;
  width: 100%;
  background: #fff;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.section-editor__textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.section-editor__hint {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 4px;
}

.section-editor__array-field {
  display: flex;
  flex-direction: column;
}

.section-editor__empty {
  font-size: 0.8125rem;
  color: #9ca3af;
  font-style: italic;
  padding: 8px 0;
}
</style>
