<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">User Preferences</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8" lg="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon left color="primary" class="mr-2">mdi-tune</v-icon>
            Application Settings
          </v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="savePreferences">
              <v-switch
                v-model="preferences.enable_notifications"
                label="Enable Email Notifications"
                color="primary"
              ></v-switch>

              <v-switch
                v-model="preferences.enable_sms"
                label="Enable SMS Notifications"
                color="primary"
              ></v-switch>

              <v-select
                v-model="preferences.default_view"
                :items="viewOptions"
                label="Default Dashboard View"
                prepend-icon="mdi-view-dashboard"
              ></v-select>

              <v-select
                v-model="preferences.theme"
                :items="themeOptions"
                label="Application Theme"
                prepend-icon="mdi-palette"
              ></v-select>

              <v-select
                v-model="preferences.language"
                :items="languageOptions"
                label="Language"
                prepend-icon="mdi-translate"
              ></v-select>

              <v-alert
                v-if="error"
                type="error"
                dismissible
                class="mt-3"
              >
                {{ error }}
              </v-alert>

              <v-alert
                v-if="success"
                type="success"
                dismissible
                class="mt-3"
              >
                {{ success }}
              </v-alert>
            </v-form>
          </v-card-text>
          <v-card-actions class="px-4 pb-4">
            <v-spacer></v-spacer>
            <v-btn
              color="primary"
              :loading="loading"
              :disabled="loading"
              @click="savePreferences"
            >
              Save Preferences
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, reactive } from 'vue'
import axios from 'axios'

interface Preferences {
  enable_notifications: boolean
  enable_sms: boolean
  default_view: string
  theme: string
  language: string
}

export default defineComponent({
  name: 'Preferences',
  setup() {
    const valid = ref(true)
    const loading = ref(false)
    const error = ref('')
    const success = ref('')
    
    const preferences = reactive<Preferences>({
      enable_notifications: true,
      enable_sms: false,
      default_view: 'day',
      theme: 'light',
      language: 'en'
    })

    const viewOptions = [
      { title: 'Day View', value: 'day' },
      { title: 'Week View', value: 'week' },
      { title: 'Month View', value: 'month' }
    ]

    const themeOptions = [
      { title: 'Light', value: 'light' },
      { title: 'Dark', value: 'dark' },
      { title: 'System Default', value: 'system' }
    ]

    const languageOptions = [
      { title: 'English', value: 'en' },
      { title: 'Danish', value: 'da' },
      { title: 'Swedish', value: 'sv' },
      { title: 'Norwegian', value: 'no' }
    ]

    const fetchPreferences = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/user/settings')
        const settings = response.data
        
        // Map backend settings to our local preferences object
        preferences.enable_notifications = settings.notifications_enabled || false
        preferences.enable_sms = settings.sms_enabled || false
        preferences.default_view = settings.default_view || 'day'
        preferences.theme = settings.theme || 'light'
        preferences.language = settings.language || 'en'
      } catch (err) {
        error.value = 'Failed to load preferences'
        console.error('Error fetching preferences:', err)
      } finally {
        loading.value = false
      }
    }

    const savePreferences = async () => {
      loading.value = true
      error.value = ''
      success.value = ''
      
      try {
        // Map our local preferences to the backend settings format
        const settings = {
          notifications_enabled: preferences.enable_notifications,
          sms_enabled: preferences.enable_sms,
          default_view: preferences.default_view,
          theme: preferences.theme,
          language: preferences.language
        }
        
        await axios.put('/api/user/settings', settings)
        success.value = 'Preferences saved successfully'
      } catch (err) {
        error.value = 'Failed to save preferences'
        console.error('Error saving preferences:', err)
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchPreferences()
    })

    return {
      valid,
      loading,
      error,
      success,
      preferences,
      viewOptions,
      themeOptions,
      languageOptions,
      savePreferences
    }
  }
})
</script>
