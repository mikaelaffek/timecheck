// src/utils/notification.js
import { useSnackbarStore } from '@/stores/snackbar'

/**
 * Utility functions for displaying notifications
 */
export const notification = {
  /**
   * Show a success notification
   * @param {string} message - The message to display
   * @param {number} timeout - Duration in milliseconds
   */
  success(message, timeout = 3000) {
    const snackbar = useSnackbarStore()
    snackbar.showSuccess(message, timeout)
  },

  /**
   * Show an error notification
   * @param {string} message - The error message to display
   * @param {number} timeout - Duration in milliseconds
   */
  error(message, timeout = 5000) {
    const snackbar = useSnackbarStore()
    snackbar.showError(message, timeout)
  },

  /**
   * Show a warning notification
   * @param {string} message - The warning message to display
   * @param {number} timeout - Duration in milliseconds
   */
  warning(message, timeout = 4000) {
    const snackbar = useSnackbarStore()
    snackbar.showWarning(message, timeout)
  },

  /**
   * Show an info notification
   * @param {string} message - The info message to display
   * @param {number} timeout - Duration in milliseconds
   */
  info(message, timeout = 3000) {
    const snackbar = useSnackbarStore()
    snackbar.showInfo(message, timeout)
  }
}
