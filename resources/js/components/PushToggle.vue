<template>
  <div class="p-4">
    <button
      @click="toggle"
      :class="buttonClass"
      :disabled="loading"
      class="px-4 py-2 rounded-md disabled:opacity-50"
    >
      {{ label }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { usePush } from '@/composables/usePush'

const { subscribed, loading, toggle, label, buttonClass } = usePush()
</script>
