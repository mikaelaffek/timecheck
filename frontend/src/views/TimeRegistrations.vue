<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Time Registrations</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              class="mr-4"
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-menu
              ref="dateMenu"
              v-model="dateMenu"
              :close-on-content-click="false"
              transition="scale-transition"
              offset-y
              min-width="auto"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="dateFilter"
                  label="Date Filter"
                  prepend-icon="mdi-calendar"
                  readonly
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="dateFilter"
                @input="dateMenu = false"
                no-title
                scrollable
              ></v-date-picker>
            </v-menu>
            <v-btn color="primary" @click="clearFilters" class="ml-2">
              Clear Filters
            </v-btn>
          </v-card-title>
          
          <v-data-table
            :headers="headers"
            :items="filteredRegistrations"
            :search="search"
            :loading="loading"
            sort-by="date"
            sort-desc
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
              {{ item.total_hours ? item.total_hours.toFixed(2) : '-' }}
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" @click="editTimeRegistration(item)">
                mdi-pencil
              </v-icon>
              <v-icon small @click="showLocationOnMap(item)">
                mdi-map-marker
              </v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- Edit Dialog -->
    <v-dialog v-model="editDialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">Edit Time Registration</span>
        </v-card-title>
        <v-card-text>
          <v-container>
            <v-row>
              <v-col cols="12">
                <v-menu
                  ref="dateEditMenu"
                  v-model="dateEditMenu"
                  :close-on-content-click="false"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="editedItem.date"
                      label="Date"
                      prepend-icon="mdi-calendar"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="editedItem.date"
                    @input="dateEditMenu = false"
                    no-title
                    scrollable
                  ></v-date-picker>
                </v-menu>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.clock_in_time"
                  label="Clock In Time"
                  type="time"
                  prepend-icon="mdi-clock-in"
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="editedItem.clock_out_time"
                  label="Clock Out Time"
                  type="time"
                  prepend-icon="mdi-clock-out"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.latitude"
                  label="Latitude"
                  type="number"
                  step="0.000001"
                  prepend-icon="mdi-latitude"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.longitude"
                  label="Longitude"
                  type="number"
                  step="0.000001"
                  prepend-icon="mdi-longitude"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editedItem.notes"
                  label="Notes"
                  prepend-icon="mdi-note"
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" text @click="editDialog = false">
            Cancel
          </v-btn>
          <v-btn color="primary" text @click="saveTimeRegistration">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Map Dialog -->
    <v-dialog v-model="mapDialog" max-width="800px">
      <v-card>
        <v-card-title>
          <span class="text-h5">Location</span>
        </v-card-title>
        <v-card-text>
          <div v-if="selectedLocation" class="mb-4">
            <p><strong>Date:</strong> {{ formatDate(selectedLocation.date) }}</p>
            <p><strong>Time:</strong> {{ formatTime(selectedLocation.clock_in) }}</p>
            <p><strong>Coordinates:</strong> {{ selectedLocation.latitude }}, {{ selectedLocation.longitude }}</p>
          </div>
          <div id="map" style="height: 400px; width: 100%;">
            <!-- Map will be rendered here -->
            <p class="text-center pa-4">Map would be displayed here with the location marker</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" text @click="mapDialog = false">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

interface TimeRegistration {
  id: number
  date: string
  clock_in: string
  clock_out: string | null
  latitude: number | null
  longitude: number | null
  notes: string | null
  total_hours: number | null
  status: string
}

interface EditedTimeRegistration {
  id: number
  date: string
  clock_in_time: string
  clock_out_time: string
  latitude: number | null
  longitude: number | null
  notes: string | null
}

export default defineComponent({
  name: 'TimeRegistrations',
  setup() {
    const loading = ref(false)
    const search = ref('')
    const dateFilter = ref('')
    const dateMenu = ref(false)
    const dateEditMenu = ref(false)
    const editDialog = ref(false)
    const mapDialog = ref(false)
    const timeRegistrations = ref<TimeRegistration[]>([])
    const selectedLocation = ref<TimeRegistration | null>(null)
    
    const defaultEditedItem: EditedTimeRegistration = {
      id: 0,
      date: '',
      clock_in_time: '',
      clock_out_time: '',
      latitude: null,
      longitude: null,
      notes: null
    }
    
    const editedItem = ref<EditedTimeRegistration>({...defaultEditedItem})
    
    const headers = [
      { text: 'Date', value: 'date' },
      { text: 'Clock In', value: 'clock_in' },
      { text: 'Clock Out', value: 'clock_out' },
      { text: 'Total Hours', value: 'total_hours' },
      { text: 'Status', value: 'status' },
      { text: 'Actions', value: 'actions', sortable: false }
    ]
    
    const filteredRegistrations = computed(() => {
      if (!dateFilter.value) return timeRegistrations.value
      
      return timeRegistrations.value.filter(reg => reg.date === dateFilter.value)
    })
    
    const formatDate = (dateString: string) => {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
    
    const formatTime = (timeString: string) => {
      const date = new Date(`2000-01-01T${timeString}`)
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    
    const fetchTimeRegistrations = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/time-registrations')
        timeRegistrations.value = response.data
      } catch (error) {
        console.error('Error fetching time registrations:', error)
      } finally {
        loading.value = false
      }
    }
    
    const clearFilters = () => {
      search.value = ''
      dateFilter.value = ''
    }
    
    const editTimeRegistration = (item: TimeRegistration) => {
      editedItem.value = {
        id: item.id,
        date: item.date,
        clock_in_time: item.clock_in.substring(0, 5), // HH:MM format
        clock_out_time: item.clock_out ? item.clock_out.substring(0, 5) : '',
        latitude: item.latitude,
        longitude: item.longitude,
        notes: item.notes
      }
      editDialog.value = true
    }
    
    const saveTimeRegistration = async () => {
      loading.value = true
      try {
        await axios.put(`/api/time-registrations/${editedItem.value.id}`, {
          date: editedItem.value.date,
          clock_in: `${editedItem.value.clock_in_time}:00`,
          clock_out: editedItem.value.clock_out_time ? `${editedItem.value.clock_out_time}:00` : null,
          latitude: editedItem.value.latitude,
          longitude: editedItem.value.longitude,
          notes: editedItem.value.notes
        })
        
        // Refresh the data
        await fetchTimeRegistrations()
        editDialog.value = false
      } catch (error) {
        console.error('Error saving time registration:', error)
      } finally {
        loading.value = false
      }
    }
    
    const showLocationOnMap = (item: TimeRegistration) => {
      selectedLocation.value = item
      mapDialog.value = true
      
      // In a real implementation, we would initialize a map here
      // using the coordinates from the selected item
    }
    
    onMounted(() => {
      fetchTimeRegistrations()
    })
    
    return {
      loading,
      search,
      dateFilter,
      dateMenu,
      dateEditMenu,
      editDialog,
      mapDialog,
      timeRegistrations,
      filteredRegistrations,
      headers,
      editedItem,
      selectedLocation,
      formatDate,
      formatTime,
      clearFilters,
      editTimeRegistration,
      saveTimeRegistration,
      showLocationOnMap
    }
  }
})
</script>
