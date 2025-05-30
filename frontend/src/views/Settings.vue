<template>
  <div>
    <!-- Snackbar for notifications -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
    
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Settings</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="6">
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-account</v-icon>
            User Profile
          </v-card-title>
          <v-card-text>
            <v-form ref="profileForm" v-model="profileValid">
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="profile.name"
                    label="Name"
                    prepend-icon="mdi-account"
                    :rules="[rules.required]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-text-field
                    v-model="profile.email"
                    label="Email"
                    prepend-icon="mdi-email"
                    :rules="[rules.required, rules.email]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-text-field
                    v-model="profile.personalId"
                    label="Personal ID"
                    prepend-icon="mdi-card-account-details"
                    :rules="[rules.required]"
                    required
                    disabled
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-btn 
                    color="primary" 
                    :loading="isSavingProfile" 
                    :disabled="!profileValid || isSavingProfile"
                    @click="saveProfile"
                  >
                    Save Profile
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
        
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-lock</v-icon>
            Change Password
          </v-card-title>
          <v-card-text>
            <v-form ref="passwordForm" v-model="passwordValid">
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="password.current"
                    label="Current Password"
                    prepend-icon="mdi-lock"
                    type="password"
                    :rules="[rules.required]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-text-field
                    v-model="password.new"
                    label="New Password"
                    prepend-icon="mdi-lock-plus"
                    type="password"
                    :rules="[rules.required, rules.minLength]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-text-field
                    v-model="password.confirm"
                    label="Confirm New Password"
                    prepend-icon="mdi-lock-check"
                    type="password"
                    :rules="[rules.required, rules.passwordMatch]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12">
                  <v-btn 
                    color="primary" 
                    :loading="isChangingPassword" 
                    :disabled="!passwordValid || isChangingPassword"
                    @click="changePassword"
                  >
                    Change Password
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-cog</v-icon>
            Application Settings
          </v-card-title>
          <v-card-text>
            <v-form ref="settingsForm">
              <v-row>
                <v-col cols="12">
                  <v-switch
                    v-model="settings.enableNotifications"
                    label="Enable Notifications"
                    color="primary"
                  ></v-switch>
                </v-col>
                
                <v-col cols="12">
                  <v-switch
                    v-model="settings.autoClockOut"
                    label="Auto Clock-out at End of Shift"
                    color="primary"
                  ></v-switch>
                </v-col>
                
                <v-col cols="12">
                  <v-select
                    v-model="settings.defaultView"
                    :items="viewOptions"
                    label="Default View"
                    prepend-icon="mdi-view-dashboard"
                  ></v-select>
                </v-col>
                
                <v-col cols="12">
                  <v-select
                    v-model="settings.timeFormat"
                    :items="timeFormatOptions"
                    label="Time Format"
                    prepend-icon="mdi-clock"
                  ></v-select>
                </v-col>
                
                <v-col cols="12">
                  <v-btn 
                    color="primary" 
                    :loading="isSavingSettings" 
                    @click="saveSettings"
                  >
                    Save Settings
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { useApi } from '../composables/useApi'

// OvertimeRule interface removed

export default defineComponent({
  name: 'Settings',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    
    // Initialize our API composable
    const { loading: apiLoading, snackbar, get, post, put, del } = useApi()
    
    const loading = ref(false)
    const isSavingProfile = ref(false)
    const isChangingPassword = ref(false)
    const isSavingSettings = ref(false)
    
    // Using snackbar from the API composable
    
    // Fix for navigation issues
    const handleNavigation = (path: string) => {
      console.log('Navigation requested to:', path)
      // Force navigation using replace to avoid history issues
      router.replace(path).catch(err => {
        console.error('Navigation error:', err)
      })
    }
    
    const profileForm = ref(null)
    const passwordForm = ref(null)
    const settingsForm = ref(null)
    
    const profileValid = ref(false)
    const passwordValid = ref(false)
    
    const profile = ref({
      name: '',
      email: '',
      personalId: ''
    })
    
    const password = ref({
      current: '',
      new: '',
      confirm: ''
    })
    
    const settings = ref({
      enableNotifications: true,
      autoClockOut: false,
      defaultView: 'dashboard',
      timeFormat: '24h'
    })
    
    const viewOptions = [
      { text: 'Dashboard', value: 'dashboard' },
      { text: 'Time Registrations', value: 'time-registrations' },
      { text: 'Reports', value: 'reports' }
    ]
    
    const timeFormatOptions = [
      { text: '24-hour (14:30)', value: '24h' },
      { text: '12-hour (2:30 PM)', value: '12h' }
    ]
    
    // Overtime Rules related variables removed
    
    const rules = {
      required: (v: string) => !!v || 'This field is required',
      email: (v: string) => {
        const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        return pattern.test(v) || 'Invalid email address'
      },
      minLength: (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
      passwordMatch: (v: string) => v === password.value.new || 'Passwords do not match'
    }
    
    const isAdmin = computed(() => {
      return authStore.user?.role === 'admin'
    })
    
    // Overtime Rules related computed properties removed
    
    const fetchUserProfile = async () => {
      try {
        const data = await get('/api/user')
        if (data) {
          profile.value.name = data.name
          profile.value.email = data.email
          profile.value.personalId = data.personal_id
        }
      } catch (error) {
        // Error handling is now done by the API composable
      }
    }
    
    const fetchUserSettings = async () => {
      try {
        const data = await get('/api/user/settings')
        if (data) {
          settings.value = { ...settings.value, ...data }
        }
      } catch (error) {
        // Error handling is now done by the API composable
      }
    }
    
    // fetchOvertimeRules function removed
    
    const saveProfile = async () => {
      // Validate the form before submitting
      if (!profileForm.value || !profileForm.value.validate()) {
        return
      }
      
      isSavingProfile.value = true
      try {
        const data = await put('/api/user/profile', {
          name: profile.value.name,
          email: profile.value.email
        }, {
          showSuccessNotification: true,
          successMessage: 'Profile updated successfully'
        })
        
        if (data) {
          // Update the user in the auth store
          await authStore.checkAuth()
          
          // Reset form validation
          if (profileForm.value) {
            profileForm.value.resetValidation()
          }
        }
      } catch (error) {
        // Error handling is now done by the API composable
      } finally {
        isSavingProfile.value = false
      }
    }
    
    const changePassword = async () => {
      // Validate the form before submitting
      if (!passwordForm.value || !passwordForm.value.validate()) {
        return
      }
      
      isChangingPassword.value = true
      try {
        const data = await put('/api/user/password', {
          current_password: password.value.current,
          new_password: password.value.new,
          new_password_confirmation: password.value.confirm
        }, {
          showSuccessNotification: true,
          successMessage: 'Password changed successfully'
        })
        
        if (data) {
          // Reset the form
          password.value.current = ''
          password.value.new = ''
          password.value.confirm = ''
          
          // Reset form validation
          if (passwordForm.value) {
            passwordForm.value.resetValidation()
          }
        }
      } catch (error) {
        // Handle validation errors specifically
        if (error.response && error.response.status === 422) {
          const errors = error.response.data.errors
          
          // Check for specific field errors
          if (errors && errors.current_password) {
            snackbar.value.text = errors.current_password[0] || 'Current password is incorrect'
            snackbar.value.color = 'error'
            snackbar.value.show = true
          }
        }
      } finally {
        isChangingPassword.value = false
      }
    }
    
    const saveSettings = async () => {
      isSavingSettings.value = true
      try {
        const data = await put('/api/user/settings', settings.value, {
          showSuccessNotification: true,
          successMessage: 'Settings saved successfully'
        })
      } catch (error) {
        // Error handling is now done by the API composable
      } finally {
        isSavingSettings.value = false
      }
    }
    
    // Overtime Rules related functions removed (editOvertimeRule, deleteOvertimeRule, closeOvertimeDialog, addOvertimeRule, saveOvertimeRule, confirmDeleteRule)
    
    onMounted(async () => {
      await Promise.all([
        fetchUserProfile(),
        fetchUserSettings()
      ])
    })
    
    return {
      loading,
      isSavingProfile,
      isChangingPassword,
      isSavingSettings,
      profileForm,
      passwordForm,
      settingsForm,
      profileValid,
      passwordValid,
      profile,
      password,
      settings,
      viewOptions,
      timeFormatOptions,
      rules,
      isAdmin,
      snackbar,
      saveProfile,
      changePassword,
      saveSettings
    }
  }
})
</script>
