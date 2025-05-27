<template>
  <div>
    <v-row>
    
    </v-row>

    <v-row>
      <v-col cols="12" class="d-flex justify-end">
        <v-btn color="primary" class="mr-2" icon>
          <v-icon>mdi-calendar</v-icon>
        </v-btn>
        <v-btn color="primary" class="mr-2" icon>
          <v-icon>mdi-email</v-icon>
        </v-btn>
        <v-btn color="primary" class="mr-2" icon>
          <v-icon>mdi-information</v-icon>
        </v-btn>
        <v-btn color="primary">
          <v-icon left>mdi-plus</v-icon>
          Lägg till
        </v-btn>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card class="mb-4">
          <v-card-text>
            <v-row>
              <v-col cols="12" sm="2">
                <v-text-field
                  v-model="dateRange"
                  label="Datumintervall"
                  readonly
                  outlined
                  dense
                  hide-details
                  class="mb-2"
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="2">
                <v-select
                  v-model="selectedWorkplace"
                  :items="workplaces"
                  label="Alla arbetsplatser"
                  outlined
                  dense
                  hide-details
                  class="mb-2"
                ></v-select>
              </v-col>
              <v-col cols="12" sm="2">
                <v-select
                  v-model="selectedGroup"
                  :items="groups"
                  label="Alla grupper"
                  outlined
                  dense
                  hide-details
                  class="mb-2"
                ></v-select>
              </v-col>
              <v-col cols="12" sm="2">
                <v-select
                  v-model="selectedCostCenter"
                  :items="costCenters"
                  label="Alla kostnadsställen"
                  outlined
                  dense
                  hide-details
                  class="mb-2"
                ></v-select>
              </v-col>
              <v-col cols="12" sm="2">
                <v-btn text color="primary" class="mb-2">
                  Visa ej ändr
                </v-btn>
              </v-col>
              <v-col cols="12" sm="2">
                <v-select
                  v-model="selectedStatus"
                  :items="statuses"
                  label="All status"
                  outlined
                  dense
                  hide-details
                  class="mb-2"
                ></v-select>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="search"
                  label="Sök personal..."
                  outlined
                  dense
                  hide-details
                  append-icon="mdi-magnify"
                  class="mb-2"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Loading indicator for the table -->
    <v-row v-if="loading">
      <v-col cols="12">
        <v-card class="elevation-1 mb-4">
          <v-card-text class="text-center py-5">
            <v-progress-circular
              indeterminate
              color="primary"
              size="24"
              class="mr-2"
            ></v-progress-circular>
            <span>Loading employee time registrations...</span>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- No data message when there are no registrations -->
    <v-row v-else-if="Object.keys(groupedRegistrations).length === 0">
      <v-col cols="12">
        <v-card class="elevation-1 mb-4">
          <v-card-text class="text-center py-5">
            <v-icon large color="grey lighten-1" class="mb-2">mdi-calendar-blank</v-icon>
            <div>No time registrations found for the selected period</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Actual data table when data is loaded -->
    <v-row v-else v-for="(dayGroup, date) in groupedRegistrations" :key="date">
      <v-col cols="12">
        <div class="grey lighten-3 py-2 px-4 text-subtitle-1 font-weight-bold">
          {{ formatDateHeader(date) }}
        </div>
        <v-data-table
          :headers="headers"
          :items="dayGroup"
          hide-default-header
          hide-default-footer
          class="elevation-1"
          :items-per-page="-1"
        >
          <template v-slot:item.employee="{ item }">
            <div class="d-flex align-center">
              <v-avatar size="32" class="mr-2">
                <v-img :src="getAvatarUrl(item.user_id)"></v-img>
              </v-avatar>
              <div>
                <div>{{ item.user.name }}</div>
                <div class="text-caption grey--text">Timetjek</div>
              </div>
            </div>
          </template>
          <template v-slot:item.clock_in="{ item }">
            {{ formatTime(item.clock_in) }}
          </template>
          <template v-slot:item.clock_out="{ item }">
            {{ item.clock_out ? formatTime(item.clock_out) : '' }}
          </template>
          <template v-slot:item.total_hours="{ item }">
            <span v-if="item.total_hours" class="blue--text">
              {{ formatHours(item.total_hours) }}
            </span>
          </template>
          <template v-slot:item.location="{ item }">
            <div v-if="item.latitude && item.longitude" class="location-info">
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <v-icon small color="primary" class="mr-1" v-bind="attrs" v-on="on">mdi-map-marker</v-icon>
                </template>
                <span>Öppna plats i karta</span>
              </v-tooltip>
              <span class="text-caption">
                {{ formatCoordinates(item.latitude, item.longitude) }}
              </span>
            </div>
            <span v-else class="text-caption grey--text">Ingen platsdata</span>
          </template>
          <template v-slot:item.status="{ item }">
            <v-chip
              small
              :color="getStatusColor(item.status)"
              text-color="white"
              v-if="item.status"
            >
              {{ formatStatus(item.status) }}
            </v-chip>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn icon small color="primary">
              <v-icon small>mdi-pencil</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue'
import axios from 'axios'
import LoadingOverlay from '../components/LoadingOverlay.vue'
import { format, parseISO } from 'date-fns'
import { sv } from 'date-fns/locale'

interface TimeRegistration {
  id: number
  user_id: number
  date: string
  clock_in: string
  clock_out: string | null
  total_hours: number | null
  status: string
  latitude: number | null
  longitude: number | null
  user: {
    id: number
    name: string
    email: string
    role: string
  }
}

export default defineComponent({
  name: 'AdminTimeRegistrations',
  components: {
    LoadingOverlay
  },
  setup() {
    const loading = ref(false)
    const timeRegistrations = ref<TimeRegistration[]>([])
    const search = ref('')
    const dateRange = ref('2023-10-01 - 2023-10-31')
    const selectedWorkplace = ref(null)
    const selectedGroup = ref(null)
    const selectedCostCenter = ref(null)
    const selectedStatus = ref(null)

    const workplaces = ['Huvudkontor', 'Filial Nord', 'Filial Syd']
    const groups = ['Utvecklare', 'Säljare', 'Administration']
    const costCenters = ['IT', 'Marknad', 'Ekonomi']
    const statuses = ['Alla', 'Godkänd', 'Avvisad', 'Väntande']

    const headers = [
      { text: 'Personal', value: 'employee', width: '20%' },
      { text: 'In', value: 'clock_in', width: '8%' },
      { text: 'Ut', value: 'clock_out', width: '8%' },
      { text: 'Rast', value: 'break', width: '8%' },
      { text: 'Arbetstid', value: 'total_hours', width: '10%' },
      { text: 'Plats', value: 'location', width: '15%' },
      { text: 'Schemadiff', value: 'schedule_diff', width: '8%' },
      { text: 'Info', value: 'info', width: '5%' },
      { text: 'Status', value: 'status', width: '8%' },
      { text: 'Hantera', value: 'actions', width: '5%' }
    ]

    const fetchTimeRegistrations = async () => {
      loading.value = true
      console.log('Fetching time registrations...');
      try {
        // Ensure the authorization header is set
        const token = localStorage.getItem('token')
        console.log('Token exists:', !!token);
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        }
        
        // Always use real data
        // Fetch real data from API using the dedicated admin endpoint
        console.log('Fetching real data from API...');
        // Use the dedicated admin endpoint that already includes user data
        const response = await axios.get('/api/admin/time-registrations');
        console.log('Admin time registrations response:', response);
        
        // The response already includes user data in the correct format
        if (Array.isArray(response.data)) {
          timeRegistrations.value = response.data;
        } else {
          // Handle potential different response formats
          timeRegistrations.value = response.data.data || [];
        }
        
        console.log('Loaded time registrations:', timeRegistrations.value);
      } catch (error) {
        console.error('API request failed:', error);
        timeRegistrations.value = [];
        console.log('No data loaded due to API error');
      } finally {
        loading.value = false
      }
    }

    const groupedRegistrations = computed(() => {
      const filtered = timeRegistrations.value.filter(reg => {
        if (search.value) {
          const searchLower = search.value.toLowerCase()
          return reg.user.name.toLowerCase().includes(searchLower)
        }
        return true
      })

      // Group by date
      const grouped: Record<string, TimeRegistration[]> = {}
      
      filtered.forEach(reg => {
        if (!grouped[reg.date]) {
          grouped[reg.date] = []
        }
        grouped[reg.date].push(reg)
      })
      
      // Sort dates in descending order
      return Object.fromEntries(
        Object.entries(grouped).sort((a, b) => {
          return new Date(b[0]).getTime() - new Date(a[0]).getTime()
        })
      )
    })

    const formatTime = (timeString: string) => {
      const [hours, minutes] = timeString.split(':')
      return `${hours}:${minutes}`
    }

    const formatHours = (hours: number) => {
      return `${Math.floor(hours)} tim ${Math.round((hours % 1) * 60)} min`
    }

    const formatDateHeader = (dateString: string) => {
      const date = parseISO(dateString)
      // Swedish date format: day month year (weekday)
      return format(date, 'EEEE d MMMM yyyy', { locale: sv }).toUpperCase()
    }
    
    const formatCoordinates = (latitude: number, longitude: number) => {
      if (!latitude || !longitude) return 'Ingen platsdata'
      
      // Format coordinates to 6 decimal places (approx. 10cm precision)
      const lat = latitude.toFixed(6)
      const lng = longitude.toFixed(6)
      
      return `${lat}, ${lng}`
    }

    const getStatusColor = (status: string) => {
      switch (status) {
        case 'approved': return 'success'
        case 'rejected': return 'error'
        case 'pending': return 'warning'
        default: return 'grey'
      }
    }

    const formatStatus = (status: string) => {
      switch (status) {
        case 'approved': return 'Godkänd'
        case 'rejected': return 'Avvisad'
        case 'pending': return 'Väntande'
        default: return status
      }
    }

    const getAvatarUrl = (userId: number) => {
      return `https://i.pravatar.cc/150?u=${userId}`
    }

    onMounted(() => {
      fetchTimeRegistrations()
    })

    return {
      loading,
      timeRegistrations,
      search,
      dateRange,
      selectedWorkplace,
      selectedGroup,
      selectedCostCenter,
      selectedStatus,
      workplaces,
      groups,
      costCenters,
      statuses,
      headers,
      groupedRegistrations,
      formatTime,
      formatHours,
      formatDateHeader,
      formatCoordinates,
      getStatusColor,
      formatStatus,
      getAvatarUrl
    }
  }
})
</script>

<style scoped>
.v-data-table ::v-deep th {
  background-color: #f5f5f5 !important;
}
</style>
