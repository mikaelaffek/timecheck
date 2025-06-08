<template>
  <v-card>
    <v-card-title class="text-h5">
      <v-icon class="mr-2">mdi-history</v-icon>
      Recent Time Registrations
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="recentRegistrations"
        :items-per-page="5"
        class="elevation-1"
      >
        <template v-slot:item.user="{ item }">
          <div class="d-flex align-center">
            <v-avatar size="32" class="mr-2">
              <v-img :src="getAvatarUrl(item.user_id || (authUser ? authUser.id : ''))"></v-img>
            </v-avatar>
            <span>{{ item.user_name || (authUser ? authUser.name : 'Unknown User') }}</span>
          </div>
        </template>
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
          {{ item.total_hours || '-' }}
        </template>
        <template v-slot:item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            text-color="white"
            small
          >
            {{ item.status }}
          </v-chip>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon small color="primary" @click="onEditItem(item)">
            <v-icon small>mdi-pencil</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import {
  formatTime,
  formatDate,
  getStatusColor,
  getAvatarUrl
} from '../../../utils/formatters.js';

const props = defineProps({
  headers: {
    type: Array,
    required: true,
  },
  recentRegistrations: {
    type: Array,
    required: true,
    default: () => []
  },
  authUser: { // Used for fallback if item.user_id/user_name is not present
    type: Object,
    default: null // Making it not strictly required, but good to pass
  }
});

const emit = defineEmits(['edit-item']);

const onEditItem = (item) => {
  emit('edit-item', item);
};
</script>
