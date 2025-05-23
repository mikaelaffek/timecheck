<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Dashboard</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="6">
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-clock</v-icon>
            Current Status
          </v-card-title>
          <v-card-text>
            <div v-if="isClockingIn">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
              <span class="ml-2">Processing...</span>
            </div>
            <div v-else>
              <p class="text-h6">
                {{ currentStatus }}
              </p>
              <p v-if="lastClockIn">
                Last clock-in: {{ formatDateTime(lastClockIn) }}
              </p>
              <v-btn 
                color="primary" 
                :loading="isClockingIn" 
                @click="toggleClockInOut"
                class="mt-4"
              >
                {{ clockInButtonText }}
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-calendar</v-icon>
            Today's Schedule
          </v-card-title>
          <v-card-text>
            <div v-if="todaySchedule">
              <p><strong>Start:</strong> {{ formatTime(todaySchedule.start_time) }}</p>
              <p><strong>End:</strong> {{ formatTime(todaySchedule.end_time) }}</p>
              <p><strong>Total Hours:</strong> {{ todaySchedule.total_hours }}</p>
            </div>
            <div v-else>
              <p>No schedule for today</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <v-icon left>mdi-history</v-icon>
            Recent Time Registrations
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="recentRegistrations"
              :loading="loading"
              class="elevation-1"
            >
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
                {{ item.total_hours ? item.total_hours : '-' }}
              </template>
              <template v-slot:item.actions="{ item }">
                <v-icon small class="mr-2" @click="editTimeRegistration(item)">
                  mdi-pencil
                </v-icon>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
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
  id: number
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

    const headers = [
      { title: 'Date', key: 'date' },
      { title: 'Clock In', key: 'clock_in' },
      { title: 'Clock Out', key: 'clock_out' },
      { title: 'Total Hours', key: 'total_hours' },
      { title: 'Status', key: 'status' },
      { title: 'Actions', key: 'actions', sortable: false }
    ]

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

    const fetchRecentRegistrations = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/time-registrations/recent')
        recentRegistrations.value = response.data
        
        // Check if user is currently clocked in
        const today = new Date().toISOString().split('T')[0]
        const todayRegistration = recentRegistrations.value.find(
          reg => reg.date === today && reg.clock_out === null
        )
        
        if (todayRegistration) {
          isClockedIn.value = true
          lastClockIn.value = todayRegistration.clock_in
        }
      } catch (error) {
        console.error('Error fetching recent registrations:', error)
      } finally {
        loading.value = false
      }
    }

    const fetchTodaySchedule = async () => {
      try {
        const today = new Date().toISOString().split('T')[0]
        const response = await axios.get(`/api/schedules/date/${today}`)
        todaySchedule.value = response.data
      } catch (error) {
        console.error('Error fetching today schedule:', error)
        todaySchedule.value = null
      }
    }

    const toggleClockInOut = async () => {
      isClockingIn.value = true
      try {
        if (isClockedIn.value) {
          // Clock out
          await axios.post('/api/time-registrations/clock-out')
          isClockedIn.value = false
          lastClockIn.value = null
        } else {
          // Clock in
          const response = await axios.post('/api/time-registrations/clock-in')
          isClockedIn.value = true
          lastClockIn.value = response.data.clock_in
        }
        // Refresh the data
        await fetchRecentRegistrations()
      } catch (error) {
        console.error('Error toggling clock in/out:', error)
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
      formatDate,
      formatTime,
      formatDateTime,
      toggleClockInOut,
      editTimeRegistration
    }
  }
})
</script>
