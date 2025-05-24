<template>
  <div v-if="show" class="loading-overlay">
    <div class="loading-content">
      <v-progress-circular
        indeterminate
        color="primary"
        size="64"
      ></v-progress-circular>
      <div class="loading-text mt-4">{{ message || 'Loading...' }}</div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'LoadingOverlay',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    message: {
      type: String,
      default: ''
    }
  }
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  backdrop-filter: blur(3px);
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.9);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.loading-text {
  font-size: 1.2rem;
  font-weight: 500;
  color: #333;
}
</style>
