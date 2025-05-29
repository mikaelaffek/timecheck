import { App } from 'vue'
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'

dayjs.extend(utc)
dayjs.extend(timezone)

// Detect user's local timezone
const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone
dayjs.tz.setDefault(userTimezone)

/**
 * Format a UTC/ISO string into the user's local timezone
 */
function formatDate(date: string | Date, format = 'YYYY-MM-DD HH:mm'): string {
  return dayjs.utc(date).tz().format(format)
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $tz: typeof formatDate
  }
}

export default {
  install(app: App) {
    app.config.globalProperties.$tz = formatDate
    app.provide('formatDate', formatDate)
  }
}
