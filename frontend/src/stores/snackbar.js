// src/stores/snackbar.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSnackbarStore = defineStore('snackbar', () => {
  const show = ref(false)
  const message = ref('')
  const color = ref('info')
  const timeout = ref(3000)

  function showMessage(newMessage, newColor = 'info', newTimeout = 3000) {
    message.value = newMessage
    color.value = newColor
    timeout.value = newTimeout
    show.value = true
  }

  function showSuccess(newMessage, newTimeout = 3000) {
    showMessage(newMessage, 'success', newTimeout)
  }

  function showError(newMessage, newTimeout = 5000) {
    showMessage(newMessage, 'error', newTimeout)
  }

  function showWarning(newMessage, newTimeout = 4000) {
    showMessage(newMessage, 'warning', newTimeout)
  }

  function showInfo(newMessage, newTimeout = 3000) {
    showMessage(newMessage, 'info', newTimeout)
  }

  function hideMessage() {
    show.value = false
  }

  return {
    show,
    message,
    color,
    timeout,
    showMessage,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    hideMessage
  }
})
