import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import { setupGlobalErrorHandlers } from './utils/errorHandler'
import { initializeErrorHandlers, logError } from './utils/errorLogger'

// Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Material Design Icons
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

// Configure axios to use the backend URL
axios.defaults.baseURL = 'http://localhost:8000'

// Add CORS headers to all requests
axios.defaults.headers.common['Access-Control-Allow-Origin'] = '*'

// Restore token from localStorage if it exists
const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// Add request interceptor to ensure token is set for all requests
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// Add response interceptor to handle errors globally
axios.interceptors.response.use(
  response => {
    return response
  },
  error => {
    // Use our enhanced error logger
    logError({
      url: error.config?.url,
      method: error.config?.method,
      status: error.response?.status,
      statusText: error.response?.statusText,
      data: error.response?.data || 'No response data',
      message: error.message,
      stack: error.stack
    }, 'Axios Request')
    
    return Promise.reject(error)
  }
)

// Setup global error handlers
setupGlobalErrorHandlers()

// Initialize enhanced error handlers
initializeErrorHandlers()

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#4ADE80', // Light green from the image
          secondary: '#424242', // Dark gray for secondary elements
          accent: '#34D399', // Light green accent
          error: '#FF5252',
          info: '#2196F3',
          success: '#4ADE80',
          warning: '#FFC107',
          background: '#F5F5F5', // Light gray background
          surface: '#FFFFFF',
        },
      },
    },
  },
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

app.mount('#app')
