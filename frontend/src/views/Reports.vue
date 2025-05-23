<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Reports</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="6">
        <v-card class="mb-4">
          <v-card-title>
            <v-icon left>mdi-file-document</v-icon>
            Time Reports
          </v-card-title>
          <v-card-text>
            <p>Generate time reports for all staff members or individual employees.</p>
            
            <v-form ref="timeReportForm">
              <v-row>
                <v-col cols="12">
                  <v-select
                    v-model="timeReport.userId"
                    :items="users"
                    item-text="name"
                    item-value="id"
                    label="Employee"
                    prepend-icon="mdi-account"
                    clearable
                  >
                    <template v-slot:selection="{ item }">
                      {{ item.name }}
                    </template>
                    <template v-slot:item="{ item }">
                      {{ item.name }}
                    </template>
                  </v-select>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-menu
                    ref="startDateMenu"
                    v-model="startDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        v-model="timeReport.startDate"
                        label="Start Date"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="timeReport.startDate"
                      @input="startDateMenu = false"
                      no-title
                      scrollable
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-menu
                    ref="endDateMenu"
                    v-model="endDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        v-model="timeReport.endDate"
                        label="End Date"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="timeReport.endDate"
                      @input="endDateMenu = false"
                      no-title
                      scrollable
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12">
                  <v-radio-group v-model="timeReport.format" row>
                    <v-radio label="PDF" value="pdf"></v-radio>
                    <v-radio label="Excel" value="excel"></v-radio>
                  </v-radio-group>
                </v-col>
                
                <v-col cols="12">
                  <v-btn 
                    color="primary" 
                    :loading="isGeneratingTimeReport" 
                    @click="generateTimeReport"
                    :disabled="!isTimeReportFormValid"
                  >
                    Generate Report
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
            <v-icon left>mdi-account-group</v-icon>
            Staff Registry
          </v-card-title>
          <v-card-text>
            <p>Export a complete staff registry to send to the Tax Agency during workplace inspections.</p>
            
            <v-form ref="staffRegistryForm">
              <v-row>
                <v-col cols="12" sm="6">
                  <v-menu
                    ref="registryStartDateMenu"
                    v-model="registryStartDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        v-model="staffRegistry.startDate"
                        label="Start Date"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="staffRegistry.startDate"
                      @input="registryStartDateMenu = false"
                      no-title
                      scrollable
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-menu
                    ref="registryEndDateMenu"
                    v-model="registryEndDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        v-model="staffRegistry.endDate"
                        label="End Date"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="staffRegistry.endDate"
                      @input="registryEndDateMenu = false"
                      no-title
                      scrollable
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12">
                  <v-select
                    v-model="staffRegistry.location"
                    :items="locations"
                    item-text="name"
                    item-value="id"
                    label="Location"
                    prepend-icon="mdi-map-marker"
                  >
                    <template v-slot:selection="{ item }">
                      {{ item.name }}
                    </template>
                    <template v-slot:item="{ item }">
                      {{ item.name }}
                    </template>
                  </v-select>
                </v-col>
                
                <v-col cols="12">
                  <v-btn 
                    color="primary" 
                    :loading="isGeneratingStaffRegistry" 
                    @click="generateStaffRegistry"
                    :disabled="!isStaffRegistryFormValid"
                  >
                    Export Staff Registry
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title>
            <v-icon left>mdi-history</v-icon>
            Recent Reports
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="reportsHeaders"
              :items="recentReports"
              :loading="loading"
              class="elevation-1"
            >
              <template v-slot:item.created_at="{ item }">
                {{ formatDateTime(item.created_at) }}
              </template>
              <template v-slot:item.type="{ item }">
                <v-chip
                  :color="getReportTypeColor(item.type)"
                  dark
                >
                  {{ item.type }}
                </v-chip>
              </template>
              <template v-slot:item.actions="{ item }">
                <v-btn
                  small
                  color="primary"
                  @click="downloadReport(item)"
                >
                  Download
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import axios from 'axios'

interface User {
  id: number
  name: string
  email: string
  personal_id: string
}

interface Location {
  id: number
  name: string
  address: string
}

interface Report {
  id: number
  type: string
  format: string
  start_date: string
  end_date: string
  user_id: number | null
  location_id: number | null
  file_path: string
  created_at: string
}

export default defineComponent({
  name: 'Reports',
  setup() {
    const loading = ref(false)
    const isGeneratingTimeReport = ref(false)
    const isGeneratingStaffRegistry = ref(false)
    const startDateMenu = ref(false)
    const endDateMenu = ref(false)
    const registryStartDateMenu = ref(false)
    const registryEndDateMenu = ref(false)
    
    const users = ref<User[]>([])
    const locations = ref<Location[]>([])
    const recentReports = ref<Report[]>([])
    
    const timeReport = ref({
      userId: null as number | null,
      startDate: new Date().toISOString().substr(0, 10),
      endDate: new Date().toISOString().substr(0, 10),
      format: 'pdf'
    })
    
    const staffRegistry = ref({
      startDate: new Date().toISOString().substr(0, 10),
      endDate: new Date().toISOString().substr(0, 10),
      location: null as number | null
    })
    
    const reportsHeaders = [
      { text: 'Date', value: 'created_at' },
      { text: 'Type', value: 'type' },
      { text: 'Format', value: 'format' },
      { text: 'Period', value: 'period' },
      { text: 'Actions', value: 'actions', sortable: false }
    ]
    
    const isTimeReportFormValid = computed(() => {
      return timeReport.value.startDate && timeReport.value.endDate
    })
    
    const isStaffRegistryFormValid = computed(() => {
      return (
        staffRegistry.value.startDate && 
        staffRegistry.value.endDate && 
        staffRegistry.value.location
      )
    })
    
    const formatDateTime = (dateTimeString: string) => {
      const date = new Date(dateTimeString)
      return date.toLocaleString()
    }
    
    const getReportTypeColor = (type: string) => {
      switch (type) {
        case 'time_report':
          return 'primary'
        case 'staff_registry':
          return 'success'
        default:
          return 'grey'
      }
    }
    
    const fetchUsers = async () => {
      try {
        const response = await axios.get('/api/users')
        users.value = response.data
      } catch (error) {
        console.error('Error fetching users:', error)
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
    
    const fetchRecentReports = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/reports/recent')
        recentReports.value = response.data
      } catch (error) {
        console.error('Error fetching recent reports:', error)
      } finally {
        loading.value = false
      }
    }
    
    const generateTimeReport = async () => {
      if (!isTimeReportFormValid.value) return
      
      isGeneratingTimeReport.value = true
      try {
        const response = await axios.post('/api/reports/time', {
          user_id: timeReport.value.userId,
          start_date: timeReport.value.startDate,
          end_date: timeReport.value.endDate,
          format: timeReport.value.format
        }, {
          responseType: 'blob'
        })
        
        // Create a download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        
        // Set the filename
        const filename = `time_report_${timeReport.value.startDate}_${timeReport.value.endDate}.${timeReport.value.format}`
        link.setAttribute('download', filename)
        
        // Trigger the download
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        
        // Refresh the reports list
        await fetchRecentReports()
      } catch (error) {
        console.error('Error generating time report:', error)
      } finally {
        isGeneratingTimeReport.value = false
      }
    }
    
    const generateStaffRegistry = async () => {
      if (!isStaffRegistryFormValid.value) return
      
      isGeneratingStaffRegistry.value = true
      try {
        const response = await axios.post('/api/reports/staff-registry', {
          start_date: staffRegistry.value.startDate,
          end_date: staffRegistry.value.endDate,
          location_id: staffRegistry.value.location
        }, {
          responseType: 'blob'
        })
        
        // Create a download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        
        // Set the filename
        const filename = `staff_registry_${staffRegistry.value.startDate}_${staffRegistry.value.endDate}.pdf`
        link.setAttribute('download', filename)
        
        // Trigger the download
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        
        // Refresh the reports list
        await fetchRecentReports()
      } catch (error) {
        console.error('Error generating staff registry:', error)
      } finally {
        isGeneratingStaffRegistry.value = false
      }
    }
    
    const downloadReport = (report: Report) => {
      // In a real application, this would download the report from the server
      window.open(`/api/reports/download/${report.id}`, '_blank')
    }
    
    onMounted(async () => {
      await Promise.all([
        fetchUsers(),
        fetchLocations(),
        fetchRecentReports()
      ])
    })
    
    return {
      loading,
      isGeneratingTimeReport,
      isGeneratingStaffRegistry,
      startDateMenu,
      endDateMenu,
      registryStartDateMenu,
      registryEndDateMenu,
      users,
      locations,
      recentReports,
      timeReport,
      staffRegistry,
      reportsHeaders,
      isTimeReportFormValid,
      isStaffRegistryFormValid,
      formatDateTime,
      getReportTypeColor,
      generateTimeReport,
      generateStaffRegistry,
      downloadReport
    }
  }
})
</script>
