<template>
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
</template>

<script setup>
import { defineProps } from 'vue';
import { formatHoursAndMinutes } from '../../../utils/formatters.js';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({ weeklyHours: 0, monthlyHours: 0 }) // Provide a default to prevent errors if undefined
  }
});
</script>
