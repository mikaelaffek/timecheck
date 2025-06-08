<template>
  <v-card class="mb-4">
    <v-card-title class="text-h5">
      <v-icon class="mr-2">mdi-clock-outline</v-icon>
      Time Tracking
    </v-card-title>
    <v-card-text>
      <div class="text-center mb-4">
        <h2 class="text-h3">{{ currentTime }}</h2>
        <p class="text-subtitle-1">{{ currentDate }}</p>
      </div>

      <div class="text-center">
        <v-btn
          v-if="!isClockedIn"
          color="success"
          size="large"
          block
          @click="$emit('clock-in')"
          :loading="clockInLoading"
          :disabled="clockInLoading || clockOutLoading"
        >
          <v-icon left>mdi-login</v-icon>
          Clock In
        </v-btn>
        <v-btn
          v-else
          color="error"
          size="large"
          block
          @click="$emit('clock-out')"
          :loading="clockOutLoading"
          :disabled="clockInLoading || clockOutLoading"
        >
          <v-icon left>mdi-logout</v-icon>
          Clock Out
        </v-btn>

        <p v-if="isClockedIn && lastClockIn" class="mt-4">
          You clocked in at <strong>{{ formatTime(lastClockIn) }}</strong>
        </p>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import { formatTime } from '../../../utils/formatters.js';

const props = defineProps({
  currentTime: {
    type: String,
    required: true,
  },
  currentDate: {
    type: String,
    required: true,
  },
  isClockedIn: {
    type: Boolean,
    required: true,
  },
  lastClockIn: {
    type: String,
    default: null,
  },
  clockInLoading: {
    type: Boolean,
    default: false,
  },
  clockOutLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['clock-in', 'clock-out']);
</script>
