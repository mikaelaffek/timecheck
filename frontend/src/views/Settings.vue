<template>
  <div>
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
        
        <v-card v-if="isAdmin" class="mb-4">
          <v-card-title>
            <v-icon left>mdi-cash-multiple</v-icon>
            Overtime Rules
          </v-card-title>
          <v-card-text>
            <p>Configure overtime rules for your organization.</p>
            
            <v-data-table
              :headers="overtimeHeaders"
              :items="overtimeRules"
              :loading="loading"
              class="elevation-1 mb-4"
            >
              <template v-slot:item.actions="{ item }">
                <v-icon small class="mr-2" @click="editOvertimeRule(item)">
                  mdi-pencil
                </v-icon>
                <v-icon small @click="deleteOvertimeRule(item)">
                  mdi-delete
                </v-icon>
              </template>
            </v-data-table>
            
            <v-btn color="success" @click="addOvertimeRule">
              <v-icon left>mdi-plus</v-icon>
              Add Overtime Rule
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Overtime Rule Dialog -->
    <v-dialog v-model="overtimeDialog" max-width="500px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ formTitle }}</span>
        </v-card-title>
        <v-card-text>
          <v-container>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="editedRule.name"
                  label="Rule Name"
                  :rules="[rules.required]"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedRule.multiplier"
                  label="Rate Multiplier"
                  type="number"
                  step="0.1"
                  min="1"
                  :rules="[rules.required]"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="editedRule.type"
                  :items="ruleTypes"
                  label="Rule Type"
                  :rules="[rules.required]"
                  required
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedRule.description"
                  label="Description"
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" text @click="closeOvertimeDialog">
            Cancel
          </v-btn>
          <v-btn color="primary" text @click="saveOvertimeRule">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Confirm Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Confirm Delete
        </v-card-title>
        <v-card-text>
          Are you sure you want to delete this overtime rule?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" text @click="deleteDialog = false">
            Cancel
          </v-btn>
          <v-btn color="error" text @click="confirmDeleteRule">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

interface OvertimeRule {
  id: number
  name: string
  type: string
  multiplier: number
  description: string | null
}

export default defineComponent({
  name: 'Settings',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    const loading = ref(false)
    const isSavingProfile = ref(false)
    const isChangingPassword = ref(false)
    const isSavingSettings = ref(false)
    const overtimeDialog = ref(false)
    const deleteDialog = ref(false)
    
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
    
    const overtimeRules = ref<OvertimeRule[]>([])
    const editedIndex = ref(-1)
    const editedRule = ref<OvertimeRule>({
      id: 0,
      name: '',
      type: '',
      multiplier: 1.5,
      description: null
    })
    const ruleToDelete = ref<OvertimeRule | null>(null)
    
    const ruleTypes = [
      { text: 'Weekday Evening', value: 'weekday_evening' },
      { text: 'Weekend', value: 'weekend' },
      { text: 'Holiday', value: 'holiday' },
      { text: 'Night Shift', value: 'night_shift' }
    ]
    
    const overtimeHeaders = [
      { text: 'Name', value: 'name' },
      { text: 'Type', value: 'type' },
      { text: 'Multiplier', value: 'multiplier' },
      { text: 'Actions', value: 'actions', sortable: false }
    ]
    
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
    
    const formTitle = computed(() => {
      return editedIndex.value === -1 ? 'New Overtime Rule' : 'Edit Overtime Rule'
    })
    
    const fetchUserProfile = async () => {
      try {
        const response = await axios.get('/api/user')
        profile.value.name = response.data.name
        profile.value.email = response.data.email
        profile.value.personalId = response.data.personal_id
      } catch (error) {
        console.error('Error fetching user profile:', error)
      }
    }
    
    const fetchUserSettings = async () => {
      try {
        const response = await axios.get('/api/user/settings')
        settings.value = { ...settings.value, ...response.data }
      } catch (error) {
        console.error('Error fetching user settings:', error)
      }
    }
    
    const fetchOvertimeRules = async () => {
      if (!isAdmin.value) return
      
      loading.value = true
      try {
        const response = await axios.get('/api/overtime-rules')
        overtimeRules.value = response.data
      } catch (error) {
        console.error('Error fetching overtime rules:', error)
      } finally {
        loading.value = false
      }
    }
    
    const saveProfile = async () => {
      isSavingProfile.value = true
      try {
        await axios.put('/api/user/profile', {
          name: profile.value.name,
          email: profile.value.email
        })
        // Update the user in the auth store
        await authStore.checkAuth()
      } catch (error) {
        console.error('Error saving profile:', error)
      } finally {
        isSavingProfile.value = false
      }
    }
    
    const changePassword = async () => {
      isChangingPassword.value = true
      try {
        await axios.put('/api/user/password', {
          current_password: password.value.current,
          new_password: password.value.new,
          new_password_confirmation: password.value.confirm
        })
        
        // Reset the form
        password.value.current = ''
        password.value.new = ''
        password.value.confirm = ''
      } catch (error) {
        console.error('Error changing password:', error)
      } finally {
        isChangingPassword.value = false
      }
    }
    
    const saveSettings = async () => {
      isSavingSettings.value = true
      try {
        await axios.put('/api/user/settings', settings.value)
      } catch (error) {
        console.error('Error saving settings:', error)
      } finally {
        isSavingSettings.value = false
      }
    }
    
    const editOvertimeRule = (item: OvertimeRule) => {
      editedIndex.value = overtimeRules.value.indexOf(item)
      editedRule.value = { ...item }
      overtimeDialog.value = true
    }
    
    const deleteOvertimeRule = (item: OvertimeRule) => {
      ruleToDelete.value = item
      deleteDialog.value = true
    }
    
    const closeOvertimeDialog = () => {
      overtimeDialog.value = false
      editedIndex.value = -1
      editedRule.value = {
        id: 0,
        name: '',
        type: '',
        multiplier: 1.5,
        description: null
      }
    }
    
    const addOvertimeRule = () => {
      editedIndex.value = -1
      editedRule.value = {
        id: 0,
        name: '',
        type: '',
        multiplier: 1.5,
        description: null
      }
      overtimeDialog.value = true
    }
    
    const saveOvertimeRule = async () => {
      try {
        if (editedIndex.value > -1) {
          // Update existing rule
          await axios.put(`/api/overtime-rules/${editedRule.value.id}`, editedRule.value)
        } else {
          // Create new rule
          await axios.post('/api/overtime-rules', editedRule.value)
        }
        
        // Refresh the rules
        await fetchOvertimeRules()
        closeOvertimeDialog()
      } catch (error) {
        console.error('Error saving overtime rule:', error)
      }
    }
    
    const confirmDeleteRule = async () => {
      if (!ruleToDelete.value) return
      
      try {
        await axios.delete(`/api/overtime-rules/${ruleToDelete.value.id}`)
        // Refresh the rules
        await fetchOvertimeRules()
      } catch (error) {
        console.error('Error deleting overtime rule:', error)
      } finally {
        deleteDialog.value = false
        ruleToDelete.value = null
      }
    }
    
    onMounted(async () => {
      await Promise.all([
        fetchUserProfile(),
        fetchUserSettings(),
        fetchOvertimeRules()
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
      overtimeRules,
      editedRule,
      ruleTypes,
      overtimeHeaders,
      rules,
      isAdmin,
      formTitle,
      overtimeDialog,
      deleteDialog,
      saveProfile,
      changePassword,
      saveSettings,
      editOvertimeRule,
      deleteOvertimeRule,
      closeOvertimeDialog,
      addOvertimeRule,
      saveOvertimeRule,
      confirmDeleteRule
    }
  }
})
</script>
