<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">{{ formatDateRangeHeader() }}</h1>
      </v-col>
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
              <v-col cols="12" sm="4">
                <v-menu
                  v-model="dateMenu"
                  :close-on-content-click="false"
                  :nudge-right="40"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="dateRangeDisplay"
                      label="Datumintervall"
                      prepend-icon="mdi-calendar"
                      readonly
                      outlined
                      dense
                      hide-details
                      class="mb-2"
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="dates"
                    range
                    locale="sv"
                    @change="onDateRangeChange"
                  ></v-date-picker>
                </v-menu>
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
            <v-btn icon small color="primary" @click.stop="openEditDialog(item)">
              <v-icon small>mdi-pencil</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
    <!-- Edit Time Registration Dialog -->
    <v-dialog v-model="editDialog" max-width="600px">
      <v-card>
        <v-card-title class="headline">Redigera tidsregistrering</v-card-title>
        <v-card-text>
          <v-container v-if="editedItem">
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.date"
                  label="Datum"
                  type="date"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.clock_in"
                  label="Stämpla in"
                  type="time"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.clock_out"
                  label="Stämpla ut"
                  type="time"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.latitude"
                  label="Latitud"
                  type="number"
                  step="0.000001"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.longitude"
                  label="Longitud"
                  type="number"
                  step="0.000001"
                  outlined
                  dense
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="editedItem.status"
                  :items="['pending', 'approved', 'rejected']"
                  label="Status"
                  outlined
                  dense
                ></v-select>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.notes"
                  label="Anteckningar"
                  outlined
                  dense
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeEditDialog">Avbryt</v-btn>
          <v-btn color="blue darken-1" text @click="saveTimeRegistration">Spara</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
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
    
    // Date range picker variables
    const dateMenu = ref(false)
    
    // Calculate dates for 3-day range (today and 2 days before)
    const today = new Date()
    const twoDaysAgo = new Date(today)
    twoDaysAgo.setDate(today.getDate() - 2)
    
    const dates = ref([
      twoDaysAgo.toISOString().substr(0, 10), // 2 days ago
      today.toISOString().substr(0, 10)       // Today
    ])
    const dateRangeDisplay = ref('')
    
    const selectedWorkplace = ref(null)
    const selectedGroup = ref(null)
    const selectedCostCenter = ref(null)
    const selectedStatus = ref(null)
    
    // Edit dialog variables
    const editDialog = ref(false)
    const editedIndex = ref(-1)
    const editedItem = ref(null)

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
        
        // Prepare date range parameters
        let params = {};
        if (dates.value && dates.value.length === 2) {
          params = {
            start_date: dates.value[0],
            end_date: dates.value[1]
          };
          console.log('Date range parameters:', params);
        } else {
          console.warn('Invalid date range:', dates.value);
        }
        
        // Always use real data
        // Fetch real data from API using the dedicated admin endpoint with date range
        console.log('Fetching real data from API with params:', params);
        // Use the dedicated admin endpoint that already includes user data
        const response = await axios.get('/api/admin/time-registrations', { params });
        console.log('Admin time registrations response:', response);
        
        // The response already includes user data in the correct format
        if (Array.isArray(response.data)) {
          timeRegistrations.value = response.data;
        } else {
          // Handle potential different response formats
          timeRegistrations.value = response.data.data || [];
        }
        
        // Log detailed information about the loaded data
        console.log('Loaded time registrations:', timeRegistrations.value);
        
        // Count registrations by date to see what dates we have data for
        const dateCount = {};
        timeRegistrations.value.forEach(reg => {
          if (!dateCount[reg.date]) {
            dateCount[reg.date] = 0;
          }
          dateCount[reg.date]++;
        });
        console.log('Time registrations by date:', dateCount);
        
        // Log if no time registrations were found
        if (timeRegistrations.value.length === 0) {
          console.log('No time registrations found for the selected date range');
        }
      } catch (error) {
        console.error('API request failed:', error);
        timeRegistrations.value = [];
        console.log('No data loaded due to API error');
      } finally {
        loading.value = false
      }
    }
    
    // Format date for display in the header
    const formatDateRangeHeader = () => {
      if (!dates.value || dates.value.length < 2) return '';
      
      const startDate = new Date(dates.value[0]);
      const endDate = new Date(dates.value[1]);
      
      // Format dates for Swedish locale
      const options = { weekday: 'short', day: 'numeric', month: 'short' };
      const start = startDate.toLocaleDateString('sv-SE', options);
      const end = endDate.toLocaleDateString('sv-SE', options);
      
      return `${start} - ${end}`;
    };
    
    // Update date range display when dates change
    const onDateRangeChange = () => {
      if (dates.value && dates.value.length === 2) {
        dateRangeDisplay.value = `${dates.value[0]} - ${dates.value[1]}`;
        dateMenu.value = false; // Close the date picker menu
        fetchTimeRegistrations(); // Reload data with new date range
      }
    };
    
    // Initialize date range display
    if (dates.value && dates.value.length === 2) {
      dateRangeDisplay.value = `${dates.value[0]} - ${dates.value[1]}`;
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
    
    const formatCoordinates = (latitude: any, longitude: any) => {
      if (!latitude || !longitude) return 'No location data'
      
      try {
        // Convert to numbers if they aren't already
        const latNum = typeof latitude === 'number' ? latitude : parseFloat(latitude)
        const lngNum = typeof longitude === 'number' ? longitude : parseFloat(longitude)
        
        // Check if conversion was successful
        if (isNaN(latNum) || isNaN(lngNum)) {
          return 'Invalid coordinates'
        }
        
        // Format coordinates to 6 decimal places (approx. 10cm precision)
        const lat = latNum.toFixed(6)
        const lng = lngNum.toFixed(6)
        
        return `${lat}, ${lng}`
      } catch (error) {
        console.error('Error formatting coordinates:', error)
        return 'Error formatting coordinates'
      }
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
    
    // Function to open the edit dialog
    const openEditDialog = (item) => {
      console.log('Opening edit dialog for item:', item)
      
      // Initialize editedItem if it's null
      if (!editedItem.value) {
        editedItem.value = {}
      }
      
      // Find the item in the timeRegistrations array
      const foundIndex = timeRegistrations.value.findIndex(reg => reg.id === item.id)
      editedIndex.value = foundIndex
      console.log('Found item at index:', editedIndex.value)
      
      // Create a copy of the item to avoid modifying the original directly
      if (foundIndex >= 0) {
        // Use the item from the timeRegistrations array
        editedItem.value = JSON.parse(JSON.stringify(timeRegistrations.value[foundIndex]))
      } else {
        // Use the item passed to the function
        editedItem.value = JSON.parse(JSON.stringify(item))
      }
      console.log('Edited item:', editedItem.value)
      
      // Format clock_in and clock_out for time input fields (HH:MM)
      if (editedItem.value.clock_in) {
        editedItem.value.clock_in = formatTime(editedItem.value.clock_in)
        console.log('Formatted clock_in:', editedItem.value.clock_in)
      }
      
      if (editedItem.value.clock_out) {
        editedItem.value.clock_out = formatTime(editedItem.value.clock_out)
        console.log('Formatted clock_out:', editedItem.value.clock_out)
      }
      
      // Ensure all required fields are present
      editedItem.value.date = editedItem.value.date || new Date().toISOString().substr(0, 10)
      editedItem.value.clock_in = editedItem.value.clock_in || '08:00'
      editedItem.value.status = editedItem.value.status || 'pending'
      
      editDialog.value = true
    }
    
    // Function to close the edit dialog
    const closeEditDialog = () => {
      editDialog.value = false
      // Wait for dialog to close before resetting values
      setTimeout(() => {
        editedItem.value = null
        editedIndex.value = -1
      }, 300)
    }
    
    // Function to save the edited time registration
    const saveTimeRegistration = async () => {
      if (!editedItem.value) return
      
      try {
        loading.value = true
        
        // Ensure the authorization header is set
        const token = localStorage.getItem('token')
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        }
        
        // Format the time values to match the backend expectations
        const formattedItem = {
          ...editedItem.value,
          clock_in: editedItem.value.clock_in + ':00', // Add seconds
          clock_out: editedItem.value.clock_out ? editedItem.value.clock_out + ':00' : null // Add seconds if exists
        }
        
        console.log('Saving time registration:', formattedItem)
        
        // Send the update to the backend
        const response = await axios.put(`/api/time-registrations/${editedItem.value.id}`, formattedItem)
        console.log('Update response:', response)
        
        // Update the local data
        if (editedIndex.value > -1) {
          Object.assign(timeRegistrations.value[editedIndex.value], response.data)
        }
        
        // Close the dialog
        closeEditDialog()
        
        // Refresh the data to ensure everything is up to date
        fetchTimeRegistrations()
      } catch (error) {
        console.error('Error updating time registration:', error)
        alert('Failed to update time registration. Please try again.')
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchTimeRegistrations()
    })

    return {
      loading,
      timeRegistrations,
      search,
      // Date range picker variables and functions
      dateMenu,
      dates,
      dateRangeDisplay,
      formatDateRangeHeader,
      onDateRangeChange,
      // Other filters
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
      getAvatarUrl,
      // Edit dialog variables and functions
      editDialog,
      editedItem,
      openEditDialog,
      closeEditDialog,
      saveTimeRegistration
    }
  }
})
</script>

<style scoped>
.v-data-table ::v-deep th {
  background-color: #f5f5f5 !important;
}
</style>
