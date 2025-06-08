<template>
  <v-container>
    <!-- Snackbar for notifications -->
    <v-snackbar
      v-model="serviceSnackbar.show"
      :color="serviceSnackbar.color"
      :timeout="serviceSnackbar.timeout"
    >
      {{ serviceSnackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" @click="serviceSnackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Dashboard</h1>
      </v-col>
    </v-row>

    <v-row v-if="pageLoading">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
        <p class="mt-2">Loading dashboard data...</p>
      </v-col>
    </v-row>

    <template v-else>
      <v-row>
        <v-col cols="12" md="6">
          <TimeTrackingCard
            :current-time="currentTime"
            :current-date="currentDate"
            :is-clocked-in="isClockedIn"
            :last-clock-in="lastClockIn"
            :clock-in-loading="clockInLoading"
            :clock-out-loading="clockOutLoading"
            @clock-in="handleClockIn"
            @clock-out="handleClockOut"
          />
        </v-col>

        <v-col cols="12" md="6">
          <StatisticsCard :stats="stats" />
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <RecentRegistrationsTable
            :headers="headers"
            :recent-registrations="recentRegistrations"
            :auth-user="authStore.user"
            @edit-item="handleEditItem"
          />
        </v-col>
      </v-row>
    </template>
  </v-container>

  <EditTimeRegistrationDialog
    v-model:dialogVisible="editDialog"
    :edited-item-data="editedItem"
    @save-registration="saveEditedTimeRegistration"
    @close-dialog="closeEditDialog"
  />
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { useAuthStore } from '../stores/auth';
import '../../assets/css/dashboardSimple.css';
import { useTimeTrackingService } from '../services/dashboardSimple/timeTrackingService';
import { formatTime } from '../utils/formatters.js';

import TimeTrackingCard from '../components/dashboardSimple/TimeTrackingCard.vue';
import StatisticsCard from '../components/dashboardSimple/StatisticsCard.vue';
import RecentRegistrationsTable from '../components/dashboardSimple/RecentRegistrationsTable.vue';
import EditTimeRegistrationDialog from '../components/dashboardSimple/EditTimeRegistrationDialog.vue';

// Service
const {
  loading: apiLoading,
  snackbar: serviceSnackbar, // Renamed to avoid conflict with potential local snackbar
  fetchRecentRegistrations,
  checkClockInStatus,
  clockIn: serviceClockIn,
  clockOut: serviceClockOut,
  updateTimeRegistration,
  calculateStatistics
} = useTimeTrackingService();

// Inject the formatDate function from the timezone plugin
const tzFormatDate = inject('formatDate');

// State
const pageLoading = ref(true); // For initial page load and full data refresh
const clockInLoading = ref(false);
const clockOutLoading = ref(false);
const isClockedIn = ref(false);
const lastClockIn = ref(null);
const recentRegistrations = ref([]);
const currentTime = ref('');
const currentDate = ref('');
const stats = ref({
  weeklyHours: 0,
  monthlyHours: 0
});

// Edit dialog state
const editDialog = ref(false);
const editedItem = ref({});

// Get auth store
const authStore = useAuthStore();

// Table headers
const headers = [
  { title: 'User', key: 'user', sortable: false },
  { title: 'Date', key: 'date' },
  { title: 'Clock In', key: 'clock_in' },
  { title: 'Clock Out', key: 'clock_out' },
  { title: 'Hours', key: 'total_hours' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
];

// Update current time
const updateCurrentTime = () => {
  const now = new Date();
  if (tzFormatDate) {
    currentTime.value = tzFormatDate(now, 'HH:mm');
    currentDate.value = tzFormatDate(now, 'dddd, MMMM D, YYYY');
  } else {
    currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    currentDate.value = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  }
};

const localCheckClockInStatus = async () => {
  try {
    const data = await checkClockInStatus();
    if (data) {
      isClockedIn.value = data.is_clocked_in || data.clocked_in;
      lastClockIn.value = data.last_clock_in || (data.time_registration ? data.time_registration.clock_in : null);
    }
  } catch (error) {
    console.error('Error checking clock-in status from service, falling back to recent registrations check:', error);
    const today = new Date().toISOString().split('T')[0];
    const todayRegistration = recentRegistrations.value.find(reg => {
      const regDate = new Date(reg.date).toISOString().split('T')[0];
      return regDate === today && (!reg.clock_out || reg.clock_out === null || reg.clock_out === '');
    });
    if (todayRegistration) {
      isClockedIn.value = true;
      lastClockIn.value = todayRegistration.clock_in;
    } else {
      isClockedIn.value = false;
      lastClockIn.value = null;
    }
  }
};

const localFetchRecentRegistrations = async () => {
  pageLoading.value = true; // Use main page loading for this
  try {
    const data = await fetchRecentRegistrations();
    if (data && Array.isArray(data)) {
      recentRegistrations.value = data;
      stats.value = calculateStatistics(data); // Use service's calculateStatistics
      await localCheckClockInStatus(); // Update clock-in status based on fresh data
    }
  } catch (error) {
    console.error("Failed to fetch recent registrations:", error);
    // Snackbar is handled by the service/useApi
  } finally {
    pageLoading.value = false;
  }
};

const handleClockIn = async () => {
  clockInLoading.value = true;
  try {
    const data = await serviceClockIn();
    if (data) {
      isClockedIn.value = true;
      lastClockIn.value = data.time_registration?.clock_in || data.clock_in;
      await localFetchRecentRegistrations(); // Refresh all data
    }
  } catch (error) {
    console.error("Failed to clock in:", error);
    // Snackbar handled by service
  } finally {
    clockInLoading.value = false;
  }
};

const handleClockOut = async () => {
  clockOutLoading.value = true;
  try {
    const data = await serviceClockOut();
    if (data) {
      isClockedIn.value = false;
      await localFetchRecentRegistrations(); // Refresh all data
    }
  } catch (error) {
    console.error("Failed to clock out:", error);
    // Snackbar handled by service
  } finally {
    clockOutLoading.value = false;
  }
};

const handleEditItem = (item) => {
  editedItem.value = JSON.parse(JSON.stringify(item));
  if (editedItem.value.clock_in) {
    editedItem.value.clock_in = formatTime(editedItem.value.clock_in);
  }
  if (editedItem.value.clock_out) {
    editedItem.value.clock_out = formatTime(editedItem.value.clock_out);
  } else {
    editedItem.value.clock_out = ''; // Ensure it's an empty string for the input if null
  }
  editDialog.value = true;
};

const closeEditDialog = () => {
  editDialog.value = false;
  editedItem.value = {};
};

const saveEditedTimeRegistration = async (itemToSave) => {
  // The service's updateTimeRegistration expects HH:MM:SS or null for clock_out
  // The itemToSave from dialog gives HH:MM or empty string for clock_out
  const payload = {
    ...itemToSave,
    // clock_in and clock_out are already formatted as HH:MM by the dialog/time input
    // The service will append :00 if needed
  };

  try {
    await updateTimeRegistration(itemToSave.id, payload);
    closeEditDialog();
    await localFetchRecentRegistrations(); // Refresh data
  } catch (error) {
    console.error("Failed to save time registration:", error);
    if (error.response && error.response.status === 422) {
      // Service snackbar might show a generic message.
      // If specific handling for 422 (like keeping dialog open) is needed,
      // it has to be implemented here by not closing dialog or re-showing it.
      // For now, rely on service snackbar and close dialog.
      serviceSnackbar.value.text = error.response.data.message || 'Cannot save: This time overlaps with another registration.';
      serviceSnackbar.value.color = 'warning';
      serviceSnackbar.value.show = true;
      // To ensure dialog stays open on 422 if needed:
      // editDialog.value = true; // Re-open or prevent closing
    }
    // If not a 422, or if we want to refresh anyway to show consistent state:
    // await localFetchRecentRegistrations();
  }
};

onMounted(async () => {
  updateCurrentTime();
  setInterval(updateCurrentTime, 60000);
  await localFetchRecentRegistrations();
});

// No explicit return needed for <script setup> in Vue 3
// Template has access to all top-level bindings
</script>
