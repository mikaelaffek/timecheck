<template>
  <v-container class="dashboard">
    <!-- Welcome Section -->
    <v-row class="mb-6">
      <v-col cols="12">
        <v-card class="welcome-card" elevation="2">
          <v-card-text class="pa-6">
            <div class="d-flex align-center">
              <div>
                <h1 class="text-h4 font-weight-bold mb-2">Welcome back!</h1>
                <p class="text-subtitle-1 grey--text text--darken-1">{{ greeting }}</p>
              </div>
              <v-spacer></v-spacer>
              <v-chip
                :color="isClockedIn ? 'success' : 'error'"
                text-color="white"
                size="large"
                class="status-chip"
              >
                <v-icon start size="small">{{ isClockedIn ? 'mdi-clock-check-outline' : 'mdi-clock-outline' }}</v-icon>
                {{ currentStatus }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" md="3">
        <v-card class="stat-card" elevation="2" color="primary" dark>
          <v-card-text class="pa-4">
            <div class="d-flex flex-column">
              <div class="text-overline">THIS WEEK</div>
              <div class="text-h4 font-weight-bold">{{ weeklyHours }} hrs</div>
              <div class="text-caption">Hours worked</div>
            </div>
            <v-icon class="stat-icon" size="x-large">mdi-chart-timeline-variant</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text class="pa-4">
            <div class="d-flex flex-column">
              <div class="text-overline">TODAY</div>
              <div class="text-h4 font-weight-bold">{{ todayHours }}</div>
              <div class="text-caption">Hours worked today</div>
            </div>
            <v-icon class="stat-icon" size="x-large" color="primary">mdi-clock-time-four-outline</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text class="pa-4">
            <div class="d-flex flex-column">
              <div class="text-overline">SCHEDULE</div>
              <div class="text-h4 font-weight-bold">{{ upcomingShifts }}</div>
              <div class="text-caption">Upcoming shifts</div>
            </div>
            <v-icon class="stat-icon" size="x-large" color="primary">mdi-calendar-clock</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text class="pa-4">
            <div class="d-flex flex-column">
              <div class="text-overline">OVERTIME</div>
              <div class="text-h4 font-weight-bold">{{ overtimeHours }} hrs</div>
              <div class="text-caption">Overtime this month</div>
            </div>
            <v-icon class="stat-icon" size="x-large" color="primary">mdi-timer-sand</v-icon>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Clock In/Out and Schedule Section -->
    <v-row class="mb-6">
      <v-col cols="12" md="6">
        <v-card class="h-100" elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="mr-2">mdi-clock-check</v-icon>
            Time Tracking
          </v-card-title>
          <v-card-text>
            <div v-if="isClockingIn" class="d-flex justify-center align-center pa-4">
              <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
              <span class="ml-2">Processing...</span>
            </div>
            <div v-else>
              <div class="d-flex align-center mb-4">
                <v-icon size="large" :color="isClockedIn ? 'success' : 'grey'" class="mr-3">
                  {{ isClockedIn ? 'mdi-clock-check' : 'mdi-clock-outline' }}
                </v-icon>
                <div>
                  <div class="text-h6">{{ currentStatus }}</div>
                  <div v-if="lastClockIn" class="text-caption">
                    Last clock-in: {{ formatDateTime(lastClockIn) }}
                  </div>
                </div>
              </div>
              
              <v-btn 
                :color="isClockedIn ? 'error' : 'success'" 
                :loading="isClockingIn" 
                @click="toggleClockInOut"
                block
                size="large"
                class="mt-4"
                elevation="2"
              >
                <v-icon start>{{ isClockedIn ? 'mdi-clock-out' : 'mdi-clock-in' }}</v-icon>
                {{ clockInButtonText }}
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card class="h-100" elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="mr-2">mdi-calendar</v-icon>
            Today's Schedule
          </v-card-title>
          <v-card-text>
            <div v-if="todaySchedule" class="schedule-info">
              <div class="d-flex align-center mb-4">
                <v-avatar color="primary" size="48" class="mr-3">
                  <v-icon color="white">mdi-calendar-check</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h6">{{ formatDate(todaySchedule.date) }}</div>
                  <div class="text-subtitle-2 grey--text text--darken-1">
                    {{ formatTime(todaySchedule.start_time) }} - {{ formatTime(todaySchedule.end_time) }}
                  </div>
                </div>
              </div>
              
              <v-list>
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-clock-start</v-icon>
                  </template>
                  <v-list-item-content>
                    <v-list-item-title>Start Time</v-list-item-title>
                    <v-list-item-subtitle>{{ formatTime(todaySchedule.start_time) }}</v-list-item-subtitle>
                  </v-list-item-content>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-clock-end</v-icon>
                  </template>
                  <v-list-item-content>
                    <v-list-item-title>End Time</v-list-item-title>
                    <v-list-item-subtitle>{{ formatTime(todaySchedule.end_time) }}</v-list-item-subtitle>
                  </v-list-item-content>
                </v-list-item>
                
                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-timer-outline</v-icon>
                  </template>
                  <v-list-item-content>
                    <v-list-item-title>Total Hours</v-list-item-title>
                    <v-list-item-subtitle>{{ todaySchedule.total_hours }} hours</v-list-item-subtitle>
                  </v-list-item-content>
                </v-list-item>
              </v-list>
            </div>
            <v-alert v-else type="info" outlined class="mt-2">
              No schedule found for today.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Time Registrations -->
    <v-row>
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="mr-2">mdi-history</v-icon>
            Recent Time Registrations
            <v-spacer></v-spacer>
            <v-btn
              color="primary"
              text
              to="/time-registrations"
              class="text-none"
            >
              View All
              <v-icon end>mdi-chevron-right</v-icon>
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="recentRegistrations"
              :loading="loading"
              class="elevation-0"
              :items-per-page="5"
              :footer-props="{
                'items-per-page-options': [5, 10, 15],
                'items-per-page-text': 'Rows per page:'
              }"
            >
              <template v-slot:item.date="{ item }">
                {{ formatDate(item.date) }}
              </template>
              <template v-slot:item.clock_in="{ item }">
                {{ formatTime(item.clock_in) }}
              </template>
              <template v-slot:item.clock_out="{ item }">
                {{ item.clock_out ? formatTime(item.clock_out) : 'Active' }}
              </template>
              <template v-slot:item.total_hours="{ item }">
                {{ item.total_hours ? item.total_hours : '-' }}
              </template>
              <template v-slot:item.status="{ item }">
                <v-chip
                  small
                  :color="getStatusColor(item.status)"
                  text-color="white"
                  class="status-chip"
                >
                  {{ item.status }}
                </v-chip>
              </template>
              <template v-slot:item.actions="{ item }">
                <v-btn
                  small
                  color="primary"
                  text
                  @click="editTimeRegistration(item)"
                >
                  <v-icon small left>mdi-pencil</v-icon>
                  Edit
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

interface TimeRegistration {
  id: number
  date: string
  clock_in: string
  clock_out: string | null
  total_hours: number | null
  status: string
}

interface Schedule {
  date: string
  start_time: string
  end_time: string
  total_hours: number
}

export default defineComponent({
  name: 'Dashboard',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const isClockingIn = ref(false)
    const isClockedIn = ref(false)
    const lastClockIn = ref<string | null>(null)
    const recentRegistrations = ref<TimeRegistration[]>([])
    const todaySchedule = ref<Schedule | null>(null)
    const weeklyHours = ref('0.0')
    const todayHours = ref('0.0 hrs')
    const upcomingShifts = ref('0')
    const overtimeHours = ref('0.0')

    const headers = [
      { title: 'Date', key: 'date' },
      { title: 'Clock In', key: 'clock_in' },
      { title: 'Clock Out', key: 'clock_out' },
      { title: 'Total Hours', key: 'total_hours' },
      { title: 'Status', key: 'status' },
      { title: 'Actions', key: 'actions', sortable: false }
    ]

    const greeting = computed(() => {
      const hour = new Date().getHours()
      if (hour < 12) return 'Good morning! Ready for a productive day?'
      if (hour < 18) return 'Good afternoon! Hope your day is going well.'
      return 'Good evening! Wrapping up for the day?'
    })

    const currentStatus = computed(() => {
      return isClockedIn.value ? 'Currently clocked in' : 'Not clocked in'
    })

    const clockInButtonText = computed(() => {
      return isClockedIn.value ? 'Clock Out' : 'Clock In'
    })

    const formatDate = (dateString: string) => {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }

    const formatTime = (timeString: string) => {
      const date = new Date(`2000-01-01T${timeString}`)
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }

    const formatDateTime = (dateTimeString: string) => {
      const date = new Date(dateTimeString)
      return date.toLocaleString()
    }

    const getStatusColor = (status: string) => {
      switch (status.toLowerCase()) {
        case 'approved':
          return 'success'
        case 'pending':
          return 'warning'
        case 'rejected':
          return 'error'
        default:
          return 'grey'
      }
    }

    const fetchRecentRegistrations = async () => {
      loading.value = true
      try {
        console.log('Fetching recent time registrations...')
        
        // Ensure the authorization header is set
        const token = localStorage.getItem('token')
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        }
        
        const response = await axios.get('/api/time-registrations/recent')
        console.log('Recent registrations response:', response.data)
        recentRegistrations.value = response.data
        
        // Check if user is currently clocked in
        const today = new Date().toISOString().split('T')[0]
        const todayRegistration = recentRegistrations.value.find(
          reg => {
            // Check if the date matches today (might be in different formats)
            const regDate = new Date(reg.date).toISOString().split('T')[0]
            return regDate === today && reg.clock_out === null
          }
        )
        
        if (todayRegistration) {
          console.log('Found active clock-in for today:', todayRegistration)
          isClockedIn.value = true
          lastClockIn.value = todayRegistration.clock_in
        } else {
          console.log('No active clock-in found for today')
          isClockedIn.value = false
          lastClockIn.value = null
        }

        // Calculate statistics
        calculateStatistics()
      } catch (error) {
        console.error('Error fetching recent registrations:', error)
        if (error.response) {
          console.error('Response status:', error.response.status)
          console.error('Response data:', error.response.data)
        }
      } finally {
        loading.value = false
      }
    }

    const calculateStatistics = () => {
      // Calculate weekly hours
      const now = new Date()
      const startOfWeek = new Date(now)
      startOfWeek.setDate(now.getDate() - now.getDay() + (now.getDay() === 0 ? -6 : 1)) // Monday of current week
      startOfWeek.setHours(0, 0, 0, 0)
      
      let weeklyTotal = 0
      let todayTotal = 0
      
      recentRegistrations.value.forEach(reg => {
        const regDate = new Date(reg.date)
        
        // Weekly hours
        if (regDate >= startOfWeek && reg.total_hours) {
          weeklyTotal += reg.total_hours
        }
        
        // Today's hours
        const today = new Date().toISOString().split('T')[0]
        const regDateStr = regDate.toISOString().split('T')[0]
        
        if (regDateStr === today && reg.total_hours) {
          todayTotal += reg.total_hours
        }
      })
      
      weeklyHours.value = weeklyTotal.toFixed(1)
      todayHours.value = todayTotal > 0 ? 
        `${todayTotal.toFixed(1)} hrs` : 
        isClockedIn.value ? 'In progress' : 'Not started'
      
      // Set placeholder values for other stats
      // In a real app, these would come from API calls
      upcomingShifts.value = '3'
      overtimeHours.value = '2.5'
    }

    const fetchTodaySchedule = async () => {
      try {
        console.log('Fetching schedule for today...')
        const today = new Date().toISOString().split('T')[0]
        console.log('Today date:', today)
        
        const response = await axios.get(`/api/schedules/date/${today}`)
        console.log('Today schedule response:', response.data)
        todaySchedule.value = response.data
      } catch (error) {
        console.error('Error fetching today schedule:', error)
        if (error.response) {
          console.error('Response status:', error.response.status)
          console.error('Response data:', error.response.data)
          
          // Check if this is the "No schedule found" message
          if (error.response.status === 404 && 
              error.response.data.message === 'No schedule found for this date') {
            console.log('No schedule found for today')
          }
        }
        todaySchedule.value = null
      }
    }

    const toggleClockInOut = async () => {
      isClockingIn.value = true
      try {
        // Ensure the authorization header is set
        const token = localStorage.getItem('token')
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        }
        
        if (isClockedIn.value) {
          // Clock out
          console.log('Attempting to clock out...')
          const response = await axios.post('/api/time-registrations/clock-out')
          console.log('Clock out response:', response.data)
          isClockedIn.value = false
          lastClockIn.value = null
        } else {
          // Clock in
          console.log('Attempting to clock in...')
          try {
            const response = await axios.post('/api/time-registrations/clock-in')
            console.log('Clock in response:', response.data)
            isClockedIn.value = true
            lastClockIn.value = response.data.clock_in
          } catch (clockInError) {
            // Handle the case where user is already clocked in
            if (clockInError.response && clockInError.response.status === 422 && 
                clockInError.response.data.message === 'You are already clocked in') {
              console.log('User is already clocked in, updating UI state')
              isClockedIn.value = true
              if (clockInError.response.data.time_registration) {
                lastClockIn.value = clockInError.response.data.time_registration.clock_in
              }
            } else {
              // Re-throw if it's not the expected error
              throw clockInError
            }
          }
        }
        // Refresh the data
        await fetchRecentRegistrations()
      } catch (error) {
        console.error('Error toggling clock in/out:', error)
        if (error.response) {
          console.error('Response status:', error.response.status)
          console.error('Response data:', error.response.data)
        }
      } finally {
        isClockingIn.value = false
      }
    }

    const editTimeRegistration = (item: TimeRegistration) => {
      router.push(`/time-registrations/edit/${item.id}`)
    }

    onMounted(async () => {
      await Promise.all([
        fetchRecentRegistrations(),
        fetchTodaySchedule()
      ])
    })

    return {
      loading,
      isClockingIn,
      isClockedIn,
      lastClockIn,
      recentRegistrations,
      todaySchedule,
      headers,
      currentStatus,
      clockInButtonText,
      greeting,
      weeklyHours,
      todayHours,
      upcomingShifts,
      overtimeHours,
      formatDate,
      formatTime,
      formatDateTime,
      getStatusColor,
      toggleClockInOut,
      editTimeRegistration
    }
  }
})
</script>

<style scoped>
.dashboard {
  padding-bottom: 24px;
}

.welcome-card {
  background: linear-gradient(135deg, #4ADE80 0%, #34D399 100%);
  color: white;
}

.welcome-card .grey--text {
  color: rgba(255, 255, 255, 0.8) !important;
}

.status-chip {
  font-weight: 500;
}

.stat-card {
  position: relative;
  overflow: hidden;
}

.stat-icon {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0.2;
  font-size: 48px;
}

.schedule-info {
  height: 100%;
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .stat-card {
    margin-bottom: 16px;
  }
}
</style>
