<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6" class="text-center">
        <h1 class="text-h2 mb-4">404</h1>
        <h2 class="text-h4 mb-6">Page Not Found</h2>
        <p class="text-body-1 mb-8">The page you are looking for does not exist or has been moved.</p>
        <v-btn color="primary" to="/">
          <v-icon left>mdi-home</v-icon>
          Return to Dashboard
        </v-btn>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'NotFound'
})
</script>
