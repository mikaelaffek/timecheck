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
                :items-per-page="50"
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
                  <v-btn icon small color="primary" @click="() => openEditDialog(item)">
                    <v-icon small>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon small color="error" @click="() => confirmDelete(item)">
                    <v-icon small>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </v-container>

  <!-- Use the separate TimeRegistrationEditDialog component -->
  <TimeRegistrationEditDialog
    v-model="editDialog"
    :item="editedItem"
    @saved="onTimeRegistrationSaved"
  />
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useApi } from '../composables/useApi'
import TimeRegistrationEditDialog from '../components/TimeRegistrationEditDialog.vue'

// Get the API hook with built-in error handling and notifications
const { loading: apiLoading, snackbar, get, post, put, del } = useApi()

// State
const loading = ref(false)
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
const editedIndex = ref(-1)
const editedItem = ref({})

// Delete dialog state
const deleteDialog = ref(false)
const itemToDelete = ref(null)

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

// Format time using our timezone plugin
const formatTime = (time) => {
  if (!time) return '-'
  // Use our timezone plugin
  try {
    // For just a time string, we need to add a date part
    const dateStr = '2000-01-01'
    return $tz(`${dateStr}T${time}`, 'HH:mm')
  } catch (e) {
    console.error('Error formatting time:', e)
    return time.substring(0, 5)
  }
}

// Format date using our timezone plugin
const formatDate = (dateString) => {
  if (!dateString) return '-'
  try {
    return $tz(dateString, 'YYYY-MM-DD')
  } catch (e) {
    console.error('Error formatting date:', e)
    return dateString
  }
}

// Get status color
const getStatusColor = (status) => {
  switch (status) {
    case 'approved': return 'success'
    case 'pending': return 'warning'
    case 'rejected': return 'error'
    default: return 'grey'
  }
}

// Format hours and minutes
const formatHoursAndMinutes = (totalHours) => {
  if (totalHours === undefined || totalHours === null) return '-'
  
  const hours = Math.floor(totalHours)
  const minutes = Math.round((totalHours - hours) * 60)
  
  return `${hours}h ${minutes}m`
}

// We're now using the error handler from the useApi composable

// Get avatar URL for user
const getAvatarUrl = (userId) => {
  return `https://i.pravatar.cc/150?u=${userId}`
}

// Update current time using our timezone plugin
const updateCurrentTime = () => {
  // Get current timestamp in ISO format
  const now = new Date().toISOString()
  // Format using our timezone plugin
  currentTime.value = $tz(now, 'HH:mm')
  currentDate.value = $tz(now, 'dddd, MMMM D, YYYY')
}

// Calculate statistics from the time registrations data
const calculateStats = (registrations) => {
  let todayHours = 0
  let weeklyHours = 0
  let monthlyHours = 0

  const now = new Date()
  const today = now.toDateString()
  
  const startOfWeek = new Date(now)
  startOfWeek.setDate(now.getDate() - now.getDay())
  startOfWeek.setHours(0, 0, 0, 0)

  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)

  registrations.forEach(reg => {
    // Skip if no total hours data available
    if (!reg.total_hours_decimal && !reg.total_hours) return

    const regDate = new Date(reg.date)
    // Use the decimal value for calculations if available, otherwise fallback to the old format
    const hoursValue = reg.total_hours_decimal !== undefined ? reg.total_hours_decimal : reg.total_hours
    
    // Check if the registration is from today
    if (regDate.toDateString() === today) {
      todayHours += Number(hoursValue)
    }
    
    // Check if the registration is from this week
    if (regDate >= startOfWeek) {
      weeklyHours += Number(hoursValue)
    }
    
    // Check if the registration is from this month
    if (regDate >= startOfMonth) {
      monthlyHours += Number(hoursValue)
    }
  })

  stats.value = {
    todayHours,
    weeklyHours,
    monthlyHours
  }
}

// Fetch recent time registrations
const fetchRecentRegistrations = async () => {
  loading.value = true
  try {
    const data = await get('/api/recent-time-registrations')
    if (data && Array.isArray(data)) {
      recentRegistrations.value = data
      // Calculate statistics based on the fetched data
      calculateStats(data)
    }
  } finally {
    loading.value = false
  }
}

// Check if user is clocked in
const checkClockInStatus = async () => {
  try {
    // Use the correct endpoint for checking clock-in status
    const response = await get('/api/check-clock-in-status')
    console.log('Clock-in status response:', response)
    
    // Handle the response from the API
    // The API returns: { status: { clocked_in, time_registration, clock_in_time, duration } }
    if (response && response.status) {
      // Direct access to status object from the API
      isClockedIn.value = response.status.clocked_in || false
      lastClockIn.value = response.status.clock_in_time || null
      console.log('Clock-in status set to:', isClockedIn.value)
    } else if (response && response.data && response.data.status) {
      // Fallback for when the response is wrapped in a data object
      isClockedIn.value = response.data.status.clocked_in || false
      lastClockIn.value = response.data.status.clock_in_time || null
      console.log('Clock-in status set to (from data wrapper):', isClockedIn.value)
    }
  } catch (error) {
    // Fallback to checking recent registrations if the API call fails
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
  if (isClockedIn.value) {
    // Show confirmation dialog for clocking out first
    if (!confirm('You are already clocked in. Would you like to clock out and start a new session?')) {
      return
    }
    
    // Clock out first
    await clockOut(true)
    
    // Wait a moment before clocking in again
    setTimeout(() => {
      performClockIn()
    }, 500)
  } else {
    await performClockIn()
  }
}

// Perform the actual clock in
const performClockIn = async () => {
  clockInLoading.value = true
  try {
    const data = await post('/api/time-registrations/clock-in', {}, {
      showSuccessNotification: true,
      successMessage: 'You have successfully clocked in'
    })
    
    if (data) {
      isClockedIn.value = true
      lastClockIn.value = data.clock_in
      await fetchRecentRegistrations()
    }
  } finally {
    clockInLoading.value = false
  }
}

// Clock out
const clockOut = async (skipConfirm = false) => {
  if (!isClockedIn.value) {
    snackbar.value.text = 'You are not currently clocked in'
    snackbar.value.color = 'warning'
    snackbar.value.show = true
    return
  }
  
  if (!skipConfirm && !confirm('Are you sure you want to clock out?')) {
    return
  }
  
  clockOutLoading.value = true
  try {
    const data = await post('/api/time-registrations/clock-out', {}, {
      showSuccessNotification: !skipConfirm,
      successMessage: 'You have successfully clocked out'
    })
    
    if (data) {
      isClockedIn.value = false
      lastClockIn.value = null
      await fetchRecentRegistrations()
    }
  } finally {
    clockOutLoading.value = false
  }
}

// Confirm delete dialog
const confirmDelete = (item) => {
  itemToDelete.value = item;
  deleteDialog.value = true;
};

// Delete the selected time registration
const deleteTimeRegistration = async () => {
  if (!itemToDelete.value) return;
  try {
    const success = await del(`/api/time-registrations/${itemToDelete.value.id}`, {
      showSuccessNotification: true,
      successMessage: 'Time registration deleted successfully'
    });
    
    if (success) {
      // Refresh the recent registrations
      await fetchRecentRegistrations();
    }
    
    deleteDialog.value = false;
    itemToDelete.value = null;
  } catch (error) {
    // Close the dialog even on error
    deleteDialog.value = false;
    itemToDelete.value = null;
  }
};

// Function to open the edit dialog
const openEditDialog = (item) => {
  // Create a copy of the item to avoid modifying the original directly
  editedItem.value = JSON.parse(JSON.stringify(item))
  
  // Open the dialog
  editDialog.value = true
}

// Handle the saved event from the TimeRegistrationEditDialog component
const onTimeRegistrationSaved = async (updatedItem, success = true) => {
  // Only process if the save was successful
  if (success) {
    // Find the item in the recentRegistrations array
    const index = recentRegistrations.value.findIndex(reg => reg.id === updatedItem.id)
    
    // Update the local data if found
    if (index > -1) {
      Object.assign(recentRegistrations.value[index], updatedItem)
    }
    
    // Refresh the data to ensure everything is up to date
    await fetchRecentRegistrations()
  }
  // If not successful (e.g., overlap error), don't update or refresh
}

// Initialize
onMounted(async () => {
  // Update time immediately
  updateCurrentTime()
  
  // Update time every minute
  setInterval(updateCurrentTime, 60000)
  
  // Fetch data in parallel
  await Promise.all([
    fetchRecentRegistrations(),
    checkClockInStatus()
  ])
})

// Return all variables and functions to be used in the template
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
  formatTime,
  formatDate,
  getStatusColor,
  formatHoursAndMinutes,
  getAvatarUrl,
  clockIn,
  clockOut,
  authStore,
  // Snackbar notification
  snackbar,
  // Edit dialog
  editDialog,
  editedItem,
  openEditDialog,
  onTimeRegistrationSaved,
  deleteDialog,
  itemToDelete,
  confirmDelete,
  deleteTimeRegistration
}
</script>
