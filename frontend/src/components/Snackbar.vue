<template>
  <v-snackbar
    v-model="snackbarStore.show"
    :color="snackbarStore.color"
    :timeout="snackbarStore.timeout"
    location="top"
  >
    {{ snackbarStore.message }}
    
    <template v-slot:actions>
      <v-btn
        icon="mdi-close"
        variant="text"
        @click="snackbarStore.hideMessage"
      ></v-btn>
    </template>
  </v-snackbar>
</template>

<script setup>
import { useSnackbarStore } from '@/stores/snackbar'

const snackbarStore = useSnackbarStore()
</script>
