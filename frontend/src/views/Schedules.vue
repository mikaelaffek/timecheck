<template>
  <div>
    <loading-overlay :show="loading" message="Loading schedule data..."></loading-overlay>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">My Schedule</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <div>
              <v-btn icon @click="previousWeek">
                <v-icon>mdi-chevron-left</v-icon>
              </v-btn>
              <span class="mx-2">{{ formattedDateRange }}</span>
              <v-btn icon @click="nextWeek">
                <v-icon>mdi-chevron-right</v-icon>
              </v-btn>
            </div>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="showAddScheduleDialog = true">
              <v-icon left>mdi-plus</v-icon>
              Add Schedule
            </v-btn>
          </v-card-title>

          <v-divider></v-divider>

          <v-data-table
            :headers="headers"
            :items="weekSchedules"
            :loading="loading"
            hide-default-footer
            class="elevation-0"
          >
            <template v-slot:item.date="{ item }">
              {{ formatDate(item.date) }}
            </template>
            <template v-slot:item.day="{ item }">
              {{ getDayName(item.date) }}
            </template>
            <template v-slot:item.time="{ item }">
              {{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}
            </template>
            <template v-slot:item.total_hours="{ item }">
              {{ item.total_hours }}
            </template>
            <template v-slot:item.location="{ item }">
              {{ item.location ? item.location.name : '-' }}
            </template>
            <template v-slot:item.actions="{ item }">
              <v-btn icon small class="mr-2" @click="editSchedule(item)">
                <v-icon small>mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon small @click="deleteSchedule(item)">
                <v-icon small>mdi-delete</v-icon>
              </v-btn>
            </template>
            <template v-slot:no-data>
              <div class="text-center pa-4">
                <p>No schedules found for this week.</p>
                <v-btn color="primary" @click="showAddScheduleDialog = true">
                  Add Schedule
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- Add/Edit Schedule Dialog -->
    <v-dialog v-model="showAddScheduleDialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ editMode ? 'Edit Schedule' : 'Add Schedule' }}</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="valid">
            <v-row>
              <v-col cols="12">
                <v-menu
                  ref="dateMenu"
                  v-model="dateMenu"
                  :close-on-content-click="false"
                  transition="scale-transition"
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="scheduleForm.date"
                      label="Date"
                      prepend-icon="mdi-calendar"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                      :rules="[rules.required]"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="scheduleForm.date"
                    @input="dateMenu = false"
                  ></v-date-picker>
                </v-menu>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="scheduleForm.start_time"
                  label="Start Time"
                  type="time"
                  prepend-icon="mdi-clock-start"
                  :rules="[rules.required]"
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="scheduleForm.end_time"
                  label="End Time"
                  type="time"
                  prepend-icon="mdi-clock-end"
                  :rules="[rules.required]"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="scheduleForm.location_id"
                  :items="locations"
                  item-text="name"
                  item-value="id"
                  label="Location"
                  prepend-icon="mdi-map-marker"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-checkbox
                  v-model="scheduleForm.is_recurring"
                  label="Recurring Schedule"
                ></v-checkbox>
              </v-col>
              <v-col cols="12" v-if="scheduleForm.is_recurring">
                <v-select
                  v-model="scheduleForm.recurrence_pattern"
                  :items="recurrencePatterns"
                  label="Recurrence Pattern"
                  prepend-icon="mdi-repeat"
                ></v-select>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" text @click="showAddScheduleDialog = false">
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            :disabled="!valid || saving"
            @click="saveSchedule"
          >
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Confirm Delete
        </v-card-title>
        <v-card-text>
          Are you sure you want to delete this schedule?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" text @click="showDeleteDialog = false">
            Cancel
          </v-btn>
          <v-btn
            color="error"
            :loading="deleting"
            :disabled="deleting"
            @click="confirmDeleteSchedule"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import axios from 'axios'
import LoadingOverlay from '../components/LoadingOverlay.vue'
import { format, addWeeks, subWeeks, startOfWeek, endOfWeek, parseISO } from 'date-fns'

interface Schedule {
  id: number
  user_id: number
  date: string
  start_time: string
  end_time: string
  total_hours: number
  location_id: number | null
  location?: {
    id: number
    name: string
  }
  is_recurring: boolean
  recurrence_pattern: string | null
}

interface Location {
  id: number
  name: string
  address: string
}

export default defineComponent({
  name: 'Schedules',
  components: {
    LoadingOverlay
  },
  setup() {
    const loading = ref(false)
    const saving = ref(false)
    const deleting = ref(false)
    const valid = ref(false)
    const form = ref(null)
    const showAddScheduleDialog = ref(false)
    const showDeleteDialog = ref(false)
    const dateMenu = ref(false)
    const editMode = ref(false)
    const selectedScheduleId = ref<number | null>(null)
    
    const weekSchedules = ref<Schedule[]>([])
    const locations = ref<Location[]>([])
    const currentWeekStart = ref(startOfWeek(new Date(), { weekStartsOn: 1 }))
    
    const scheduleForm = ref({
      date: format(new Date(), 'yyyy-MM-dd'),
      start_time: '09:00',
      end_time: '17:00',
      location_id: null as number | null,
      is_recurring: false,
      recurrence_pattern: 'weekly'
    })
    
    const recurrencePatterns = [
      { text: 'Weekly', value: 'weekly' },
      { text: 'Bi-weekly', value: 'biweekly' },
      { text: 'Monthly', value: 'monthly' }
    ]
    
    const rules = {
      required: (v: string) => !!v || 'This field is required'
    }
    
    const headers = [
      { text: 'Date', value: 'date', width: '15%' },
      { text: 'Day', value: 'day', width: '15%' },
      { text: 'Time', value: 'time', width: '20%' },
      { text: 'Total Hours', value: 'total_hours', width: '15%' },
      { text: 'Location', value: 'location', width: '20%' },
      { text: 'Actions', value: 'actions', width: '15%', sortable: false }
    ]
    
    const formattedDateRange = computed(() => {
      const start = format(currentWeekStart.value, 'MMM d, yyyy')
      const end = format(endOfWeek(currentWeekStart.value, { weekStartsOn: 1 }), 'MMM d, yyyy')
      return `${start} - ${end}`
    })
    
    const fetchSchedules = async () => {
      loading.value = true
      try {
        const startDate = format(currentWeekStart.value, 'yyyy-MM-dd')
        const endDate = format(endOfWeek(currentWeekStart.value, { weekStartsOn: 1 }), 'yyyy-MM-dd')
        
        const response = await axios.get(`/api/schedules?start_date=${startDate}&end_date=${endDate}`)
        weekSchedules.value = response.data
      } catch (error) {
        console.error('Error fetching schedules:', error)
      } finally {
        loading.value = false
      }
    }
    
    const fetchLocations = async () => {
      try {
        const response = await axios.get('/api/locations')
        
        // Check if the response data is an array
        if (Array.isArray(response.data)) {
          locations.value = response.data
        } else if (response.data && typeof response.data === 'object') {
          // If it's an object with a data property (common Laravel API format)
          if (Array.isArray(response.data.data)) {
            locations.value = response.data.data
          } else {
            // If it's just a JSON object, convert it to an array
            locations.value = Object.values(response.data)
          }
        } else {
          // Fallback to mock data if the response format is unexpected
          console.warn('Unexpected location data format, using mock data')
          locations.value = [
            { id: 1, name: 'Main Office', address: 'Storgatan 1, 111 23 Stockholm' },
            { id: 2, name: 'North Branch', address: 'Kungsgatan 65, 753 21 Uppsala' },
            { id: 3, name: 'South Branch', address: 'Slottorget 2, 211 34 Malmö' },
            { id: 4, name: 'West Branch', address: 'Kungsportsavenyen 25, 411 36 Göteborg' }
          ]
        }
      } catch (error) {
        console.error('Error fetching locations:', error)
        // Provide mock data as fallback
        locations.value = [
          { id: 1, name: 'Main Office', address: 'Storgatan 1, 111 23 Stockholm' },
          { id: 2, name: 'North Branch', address: 'Kungsgatan 65, 753 21 Uppsala' },
          { id: 3, name: 'South Branch', address: 'Slottorget 2, 211 34 Malmö' },
          { id: 4, name: 'West Branch', address: 'Kungsportsavenyen 25, 411 36 Göteborg' }
        ]
      }
    }
    
    const previousWeek = () => {
      currentWeekStart.value = subWeeks(currentWeekStart.value, 1)
      fetchSchedules()
    }
    
    const nextWeek = () => {
      currentWeekStart.value = addWeeks(currentWeekStart.value, 1)
      fetchSchedules()
    }
    
    const formatDate = (dateString: string) => {
      return format(parseISO(dateString), 'MMM d, yyyy')
    }
    
    const getDayName = (dateString: string) => {
      return format(parseISO(dateString), 'EEEE')
    }
    
    const formatTime = (timeString: string) => {
      if (!timeString) return '-'
      const [hours, minutes] = timeString.split(':')
      return `${hours}:${minutes}`
    }
    
    const resetForm = () => {
      scheduleForm.value = {
        date: format(new Date(), 'yyyy-MM-dd'),
        start_time: '09:00',
        end_time: '17:00',
        location_id: null,
        is_recurring: false,
        recurrence_pattern: 'weekly'
      }
      editMode.value = false
      selectedScheduleId.value = null
    }
    
    const editSchedule = (schedule: Schedule) => {
      editMode.value = true
      selectedScheduleId.value = schedule.id
      
      scheduleForm.value = {
        date: schedule.date,
        start_time: schedule.start_time.substring(0, 5),
        end_time: schedule.end_time.substring(0, 5),
        location_id: schedule.location_id,
        is_recurring: schedule.is_recurring,
        recurrence_pattern: schedule.recurrence_pattern || 'weekly'
      }
      
      showAddScheduleDialog.value = true
    }
    
    const saveSchedule = async () => {
      if (!valid.value) return
      
      saving.value = true
      
      try {
        const scheduleData = {
          date: scheduleForm.value.date,
          start_time: `${scheduleForm.value.start_time}:00`,
          end_time: `${scheduleForm.value.end_time}:00`,
          location_id: scheduleForm.value.location_id,
          is_recurring: scheduleForm.value.is_recurring,
          recurrence_pattern: scheduleForm.value.is_recurring ? scheduleForm.value.recurrence_pattern : null
        }
        
        if (editMode.value && selectedScheduleId.value) {
          await axios.put(`/api/schedules/${selectedScheduleId.value}`, scheduleData)
        } else {
          await axios.post('/api/schedules', scheduleData)
        }
        
        showAddScheduleDialog.value = false
        resetForm()
        fetchSchedules()
      } catch (error) {
        console.error('Error saving schedule:', error)
      } finally {
        saving.value = false
      }
    }
    
    const deleteSchedule = (schedule: Schedule) => {
      selectedScheduleId.value = schedule.id
      showDeleteDialog.value = true
    }
    
    const confirmDeleteSchedule = async () => {
      if (!selectedScheduleId.value) return
      
      deleting.value = true
      
      try {
        await axios.delete(`/api/schedules/${selectedScheduleId.value}`)
        showDeleteDialog.value = false
        fetchSchedules()
      } catch (error) {
        console.error('Error deleting schedule:', error)
      } finally {
        deleting.value = false
      }
    }
    
    onMounted(() => {
      fetchSchedules()
      fetchLocations()
    })
    
    return {
      loading,
      saving,
      deleting,
      valid,
      form,
      showAddScheduleDialog,
      showDeleteDialog,
      dateMenu,
      editMode,
      weekSchedules,
      locations,
      scheduleForm,
      recurrencePatterns,
      headers,
      rules,
      formattedDateRange,
      previousWeek,
      nextWeek,
      formatDate,
      getDayName,
      formatTime,
      editSchedule,
      saveSchedule,
      deleteSchedule,
      confirmDeleteSchedule
    }
  }
})
</script>

<style scoped>
.v-data-table ::v-deep th {
  font-weight: bold;
}
</style>
