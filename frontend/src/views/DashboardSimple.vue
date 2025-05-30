<template>
  <v-container>
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
        <h1 class="text-h4 mb-4">Dashboard</h1>
      </v-col>
    </v-row>

    <v-row v-if="loading">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
        <p class="mt-2">Loading dashboard data...</p>
      </v-col>
    </v-row>

    <template v-else>
      <!-- Clock In/Out Card -->
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="mb-4">
            <v-card-title class="text-h5">
              <v-icon class="mr-2">mdi-clock-outline</v-icon>
              Time Tracking
            </v-card-title>
            <v-card-text>
              <div class="text-center mb-4">
                <h2 class="text-h3">{{ currentTime }}</h2>
                <p class="text-subtitle-1">{{ currentDate }}</p>
              </div>

              <div class="text-center">
                <v-btn 
                  v-if="!isClockedIn" 
                  color="success" 
                  size="large" 
                  block
                  @click="clockIn"
                  :loading="clockInLoading"
                >
                  <v-icon left>mdi-login</v-icon>
                  Clock In
                </v-btn>
                <v-btn 
                  v-else 
                  color="error" 
                  size="large" 
                  block
                  @click="clockOut"
                  :loading="clockOutLoading"
                >
                  <v-icon left>mdi-logout</v-icon>
                  Clock Out
                </v-btn>

                <p v-if="isClockedIn" class="mt-4">
                  You clocked in at <strong>{{ formatTime(lastClockIn) }}</strong>
                </p>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Statistics Card -->
        <v-col cols="12" md="6">
          <v-card class="mb-4">
            <v-card-title class="text-h5">
              <v-icon class="mr-2">mdi-chart-bar</v-icon>
              Statistics
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="6">
                  <div class="text-center">
                    <h3 class="text-h4">{{ formatHoursAndMinutes(stats.weeklyHours) }}</h3>
                    <p class="text-subtitle-2">Hours this week</p>
                  </div>
                </v-col>
                <v-col cols="6">
                  <div class="text-center">
                    <h3 class="text-h4">{{ formatHoursAndMinutes(stats.monthlyHours) }}</h3>
                    <p class="text-subtitle-2">Hours this month</p>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Recent Time Registrations -->
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-card-title class="text-h5">
              <v-icon class="mr-2">mdi-history</v-icon>
              Recent Time Registrations
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="headers"
                :items="recentRegistrations"
                :items-per-page="5"
                class="elevation-1"
              >
                <template v-slot:item.user="{ item }">
                  <div class="d-flex align-center">
                    <v-avatar size="32" class="mr-2">
                      <v-img :src="getAvatarUrl(item.user_id || authStore.user.id)"></v-img>
                    </v-avatar>
                    <span>{{ item.user_name || authStore.user.name }}</span>
                  </div>
                </template>
                <template v-slot:item.date="{ item }">
                  {{ formatDate(item.date) }}
                </template>
                <template v-slot:item.clock_in="{ item }">
                  {{ formatTime(item.clock_in) }}
                </template>
                <template v-slot:item.clock_out="{ item }">
                  {{ item.clock_out ? formatTime(item.clock_out) : '-' }}
                </template>
                <template v-slot:item.total_hours="{ item }">
                  {{ item.total_hours || '-' }}
                </template>
                <template v-slot:item.status="{ item }">
                  <v-chip
                    :color="getStatusColor(item.status)"
                    text-color="white"
                    small
                  >
                    {{ item.status }}
                  </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                  <v-btn icon small color="primary" @click="openEditDialog(item)">
                    <v-icon small>mdi-pencil</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </v-container>

  <!-- Edit Time Registration Dialog -->
  <v-dialog v-model="editDialog" max-width="500px">
    <v-card>
      <v-card-title>Edit Time Registration</v-card-title>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="editedItem.clock_in"
                label="Clock In Time"
                type="time"
                hint="Format: HH:MM"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="editedItem.clock_out"
                label="Clock Out Time"
                type="time"
                hint="Format: HH:MM"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="error" text @click="closeEditDialog">Cancel</v-btn>
        <v-btn color="primary" text @click="saveTimeRegistration">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import { ref, onMounted, computed, inject } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useApi } from '../composables/useApi'

export default {
  name: 'DashboardSimple',
  setup() {
    // Initialize our API composable
    const { loading: apiLoading, snackbar, get, post, put, del } = useApi()
    
    // Inject the formatDate function from the timezone plugin
    const tzFormatDate = inject('formatDate')
    
    // State
    const loading = ref(true)
    const clockInLoading = ref(false)
    const clockOutLoading = ref(false)
    const isClockedIn = ref(false)
    const lastClockIn = ref(null)
    const recentRegistrations = ref([])
    const currentTime = ref('')
    const currentDate = ref('')
    const stats = ref({
      todayHours: 0,
      weeklyHours: 0,
      monthlyHours: 0
    })
    
    // Edit dialog state
    const editDialog = ref(false)
    const editedItem = ref({})

    // Get auth store
    const authStore = useAuthStore()

    // Table headers
    const headers = [
      { title: 'User', key: 'user', sortable: false },
      { title: 'Date', key: 'date' },
      { title: 'Clock In', key: 'clock_in' },
      { title: 'Clock Out', key: 'clock_out' },
      { title: 'Hours', key: 'total_hours' },
      { title: 'Status', key: 'status' },
      { title: 'Actions', key: 'actions', sortable: false }
    ]

    // Format time (HH:MM:SS to HH:MM)
    const formatTime = (time) => {
      if (!time) return '-'
      // If already in HH:MM format, return as is
      if (time.length === 5) return time
      // Otherwise parse and format
      try {
        const date = new Date(`2000-01-01T${time}`)
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      } catch (e) {
        console.error('Error formatting time:', e)
        return time.substring(0, 5)
      }
    }

    // Format date (YYYY-MM-DD to DD/MM/YYYY)
    const formatDate = (dateString) => {
      if (!dateString) return '-'
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString()
      } catch (e) {
        console.error('Error formatting date:', e)
        return dateString
      }
    }

    // Get status color
    const getStatusColor = (status) => {
      switch (status) {
        case 'approved': return 'success'
        case 'rejected': return 'error'
        case 'pending': return 'warning'
        default: return 'grey'
      }
    }

    // Format hours and minutes
    const formatHoursAndMinutes = (totalHours) => {
      if (!totalHours) return '-'
      
      const hours = Math.floor(totalHours)
      const minutes = Math.round((totalHours - hours) * 60)
      
      return `${hours}h ${minutes}m`
    }

    // Get avatar URL for user
    const getAvatarUrl = (userId) => {
      return `https://i.pravatar.cc/150?u=${userId}`
    }

    // Update current time
    const updateCurrentTime = () => {
      const now = new Date()
      // Use the timezone plugin's formatDate function for consistent time formatting
      if (tzFormatDate) {
        currentTime.value = tzFormatDate(now, 'HH:mm')
        currentDate.value = tzFormatDate(now, 'dddd, MMMM D, YYYY')
      } else {
        // Fallback to native methods if the plugin is not available
        currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        currentDate.value = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
      }
    }

    // Calculate statistics
    const calculateStatistics = () => {
      if (!recentRegistrations.value.length) {
        stats.value = { weeklyHours: 0, monthlyHours: 0 }
        return
      }
      
      const now = new Date()
      const startOfWeek = new Date(now)
      startOfWeek.setDate(now.getDate() - now.getDay()) // Sunday as first day
      startOfWeek.setHours(0, 0, 0, 0)
      
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
      
      let weeklyHours = 0
      let monthlyHours = 0
      
      recentRegistrations.value.forEach(reg => {
        const regDate = new Date(reg.date)
        const hours = parseFloat(reg.total_hours || 0)
        
        if (regDate >= startOfMonth) {
          monthlyHours += hours
        }
        
        if (regDate >= startOfWeek) {
          weeklyHours += hours
        }
      })
      
      stats.value = {
        weeklyHours: Math.round(weeklyHours * 10) / 10,
        monthlyHours: Math.round(monthlyHours * 10) / 10
      }
    }

    // Fetch recent time registrations
    const fetchRecentRegistrations = async () => {
      loading.value = true
      try {
        const data = await get('/api/recent-time-registrations')
        if (data && Array.isArray(data)) {
          recentRegistrations.value = data
          // Calculate statistics
          calculateStatistics()
        }
      } finally {
        loading.value = false
      }
    }
    
    // Check if user is clocked in
    const checkClockInStatus = async () => {
      try {
        const data = await get('/api/check-clock-in-status')
        if (data) {
          isClockedIn.value = data.is_clocked_in || data.clocked_in
          lastClockIn.value = data.last_clock_in || (data.time_registration ? data.time_registration.clock_in : null)
        }
      } catch (error) {
        console.error('Error checking clock-in status, falling back to recent registrations check')
        const today = new Date().toISOString().split('T')[0]
        
        const todayRegistration = recentRegistrations.value.find(reg => {
          const regDate = new Date(reg.date).toISOString().split('T')[0]
          return regDate === today && (!reg.clock_out || reg.clock_out === null || reg.clock_out === '')
        })
        
        if (todayRegistration) {
          isClockedIn.value = true
          lastClockIn.value = todayRegistration.clock_in
        } else {
          isClockedIn.value = false
          lastClockIn.value = null
        }
      }
    }

    // Clock in
    const clockIn = async () => {
      clockInLoading.value = true
      try {
        const data = await post('/api/time-registrations/clock-in', {}, {
          showSuccessNotification: true,
          successMessage: 'Successfully clocked in'
        })
        
        if (data) {
          // Update state
          isClockedIn.value = true
          
          // Check if the response contains time_registration object
          if (data.time_registration && data.time_registration.clock_in) {
            lastClockIn.value = data.time_registration.clock_in
          } else if (data.clock_in) {
            lastClockIn.value = data.clock_in
          }
          
          // Refresh data
          await fetchRecentRegistrations()
        }
      } finally {
        clockInLoading.value = false
      }
    }

    // Clock out
    const clockOut = async () => {
      clockOutLoading.value = true
      try {
        const data = await post('/api/time-registrations/clock-out', {}, {
          showSuccessNotification: true,
          successMessage: 'Successfully clocked out'
        })
        
        if (data) {
          // Update state
          isClockedIn.value = false
          
          // Refresh data
          await fetchRecentRegistrations()
        }
      } finally {
        clockOutLoading.value = false
      }
    }

    // Function to open the edit dialog
    const openEditDialog = (item) => {
      // Create a copy of the item to avoid modifying the original directly
      editedItem.value = JSON.parse(JSON.stringify(item))
      
      // Format clock_in and clock_out for time input fields (HH:MM)
      if (editedItem.value.clock_in) {
        editedItem.value.clock_in = formatTime(editedItem.value.clock_in)
      }
      
      if (editedItem.value.clock_out) {
        editedItem.value.clock_out = formatTime(editedItem.value.clock_out)
      }
      
      // Open the dialog
      editDialog.value = true
    }
    
    // Function to close the edit dialog
    const closeEditDialog = () => {
      editDialog.value = false
      editedItem.value = {}
    }
    
    // Function to save the edited time registration
    const saveTimeRegistration = async () => {
      try {
        // Format the time values to match the backend expectations
        const formattedItem = {
          ...editedItem.value,
          clock_in: editedItem.value.clock_in + ':00', // Add seconds
          clock_out: editedItem.value.clock_out ? editedItem.value.clock_out + ':00' : null // Add seconds if exists
        }
        
        // Use our API composable to make the request
        const data = await put(`/api/time-registrations/${editedItem.value.id}`, formattedItem, {
          showSuccessNotification: true,
          successMessage: 'Time registration updated successfully'
        })
        
        if (data) {
          // Close the dialog
          closeEditDialog()
          
          // Refresh the data to ensure everything is up to date
          await fetchRecentRegistrations()
        }
      } catch (error) {
        // Error handling is now done by the API composable
        if (error.response && error.response.status === 422) {
          // For validation errors, show a specific message
          snackbar.value.text = error.response.data.message || 'Cannot save: This time overlaps with another registration.'
          snackbar.value.color = 'warning'
          snackbar.value.show = true
        }
        // Refresh the data to ensure we're showing the current state
        await fetchRecentRegistrations()
      }
    }

    // Initialize
    onMounted(async () => {
      // Update time immediately
      updateCurrentTime()
      
      // Update time every minute
      setInterval(updateCurrentTime, 60000)
      
      // Fetch data
      await fetchRecentRegistrations()
    })

    return {
      loading,
      clockInLoading,
      clockOutLoading,
      currentTime,
      currentDate,
      isClockedIn,
      lastClockIn,
      recentRegistrations,
      stats,
      headers,
      snackbar,
      tzFormatDate,
      formatTime,
      formatDate,
      getStatusColor,
      formatHoursAndMinutes,
      getAvatarUrl,
      clockIn,
      clockOut,
      openEditDialog,
      closeEditDialog,
      saveTimeRegistration,
      editDialog,
      editedItem,
      authStore
    }
  }
}
</script>
