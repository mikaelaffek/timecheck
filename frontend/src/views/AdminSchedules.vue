<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">All Employee Schedules</h1>
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
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search employees"
              single-line
              hide-details
              class="mx-4"
              style="max-width: 300px"
            ></v-text-field>
            <v-btn color="primary" @click="showAddScheduleDialog = true">
              <v-icon left>mdi-plus</v-icon>
              Add Schedule
            </v-btn>
          </v-card-title>

          <v-divider></v-divider>

          <v-data-table
            :headers="headers"
            :items="filteredSchedules"
            :loading="loading"
            :search="search"
            class="elevation-0"
          >
            <template v-slot:item.employee="{ item }">
              <div class="d-flex align-center">
                <v-avatar size="32" class="mr-2">
                  <v-img :src="getAvatarUrl(item.user_id)"></v-img>
                </v-avatar>
                <div>
                  <div>{{ item.user ? item.user.name : `User ${item.user_id}` }}</div>
                  <div class="text-caption grey--text">{{ item.user ? item.user.role : '' }}</div>
                </div>
              </div>
            </template>
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
            <template v-slot:item.recurring="{ item }">
              <v-chip
                x-small
                :color="item.is_recurring ? 'primary' : 'grey'"
                text-color="white"
                v-if="item.is_recurring"
              >
                {{ item.recurrence_pattern }}
              </v-chip>
              <span v-else>-</span>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-btn icon small class="mr-2" @click="editSchedule(item)">
                <v-icon small>mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon small @click="deleteSchedule(item)">
                <v-icon small>mdi-delete</v-icon>
              </v-btn>
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
                <v-select
                  v-model="scheduleForm.user_id"
                  :items="employees"
                  item-text="name"
                  item-value="id"
                  label="Employee"
                  prepend-icon="mdi-account"
                  :rules="[rules.required]"
                ></v-select>
              </v-col>
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
import { format, addWeeks, subWeeks, startOfWeek, endOfWeek, parseISO } from 'date-fns'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface Schedule {
  id: number
  user_id: number
  user?: User
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
  name: 'AdminSchedules',
  setup() {
    const loading = ref(false)
    const saving = ref(false)
    const deleting = ref(false)
    const valid = ref(false)
    const form = ref(null)
    const search = ref('')
    const showAddScheduleDialog = ref(false)
    const showDeleteDialog = ref(false)
    const dateMenu = ref(false)
    const editMode = ref(false)
    const selectedScheduleId = ref<number | null>(null)
    
    const schedules = ref<Schedule[]>([])
    const employees = ref<User[]>([])
    const locations = ref<Location[]>([])
    const currentWeekStart = ref(startOfWeek(new Date(), { weekStartsOn: 1 }))
    
    const scheduleForm = ref({
      user_id: null as number | null,
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
      required: (v: any) => !!v || 'This field is required'
    }
    
    const headers = [
      { text: 'Employee', value: 'employee', width: '20%' },
      { text: 'Date', value: 'date', width: '12%' },
      { text: 'Day', value: 'day', width: '12%' },
      { text: 'Time', value: 'time', width: '15%' },
      { text: 'Hours', value: 'total_hours', width: '8%' },
      { text: 'Location', value: 'location', width: '15%' },
      { text: 'Recurring', value: 'recurring', width: '10%' },
      { text: 'Actions', value: 'actions', width: '8%', sortable: false }
    ]
    
    const formattedDateRange = computed(() => {
      const start = format(currentWeekStart.value, 'MMM d, yyyy')
      const end = format(endOfWeek(currentWeekStart.value, { weekStartsOn: 1 }), 'MMM d, yyyy')
      return `${start} - ${end}`
    })
    
    const filteredSchedules = computed(() => {
      if (!search.value) return schedules.value
      
      const searchLower = search.value.toLowerCase()
      return schedules.value.filter(schedule => {
        const userName = schedule.user?.name?.toLowerCase() || ''
        return userName.includes(searchLower)
      })
    })
    
    const fetchSchedules = async () => {
      loading.value = true
      try {
        const startDate = format(currentWeekStart.value, 'yyyy-MM-dd')
        const endDate = format(endOfWeek(currentWeekStart.value, { weekStartsOn: 1 }), 'yyyy-MM-dd')
        
        const response = await axios.get(`/api/schedules?start_date=${startDate}&end_date=${endDate}`)
        
        // Get user details for each schedule
        const schedulesWithUsers = await Promise.all(
          response.data.map(async (schedule: Schedule) => {
            try {
              const userResponse = await axios.get(`/api/users/${schedule.user_id}`)
              return {
                ...schedule,
                user: userResponse.data
              }
            } catch (error) {
              console.error(`Error fetching user ${schedule.user_id}:`, error)
              return schedule
            }
          })
        )
        
        schedules.value = schedulesWithUsers
      } catch (error) {
        console.error('Error fetching schedules:', error)
      } finally {
        loading.value = false
      }
    }
    
    const fetchEmployees = async () => {
      try {
        const response = await axios.get('/api/users')
        employees.value = response.data
      } catch (error) {
        console.error('Error fetching employees:', error)
      }
    }
    
    const fetchLocations = async () => {
      try {
        const response = await axios.get('/api/locations')
        locations.value = response.data
      } catch (error) {
        console.error('Error fetching locations:', error)
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
    
    const getAvatarUrl = (userId: number) => {
      return `https://i.pravatar.cc/150?u=${userId}`
    }
    
    const resetForm = () => {
      scheduleForm.value = {
        user_id: null,
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
        user_id: schedule.user_id,
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
          user_id: scheduleForm.value.user_id,
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
      fetchEmployees()
      fetchLocations()
    })
    
    return {
      loading,
      saving,
      deleting,
      valid,
      form,
      search,
      showAddScheduleDialog,
      showDeleteDialog,
      dateMenu,
      editMode,
      schedules,
      employees,
      locations,
      scheduleForm,
      recurrencePatterns,
      headers,
      rules,
      formattedDateRange,
      filteredSchedules,
      previousWeek,
      nextWeek,
      formatDate,
      getDayName,
      formatTime,
      getAvatarUrl,
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
