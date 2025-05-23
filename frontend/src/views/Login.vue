<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Timetjek Login</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="login">
              <v-text-field
                v-model="personalId"
                label="Personal ID"
                name="personal-id"
                prepend-icon="mdi-account"
                type="text"
                :rules="[rules.required]"
                required
              ></v-text-field>

              <v-text-field
                v-model="password"
                label="Password"
                name="password"
                prepend-icon="mdi-lock"
                type="password"
                :rules="[rules.required]"
                required
              ></v-text-field>
            </v-form>
            <v-alert
              v-if="error"
              type="error"
              dismissible
              class="mt-3"
            >
              {{ error }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn 
              color="primary" 
              :loading="loading" 
              :disabled="!valid || loading" 
              @click="login"
            >
              Login
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

export default defineComponent({
  name: 'Login',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const form = ref(null)
    const valid = ref(false)
    const loading = ref(false)
    const error = ref('')
    
    const personalId = ref('')
    const password = ref('')
    
    const rules = {
      required: (v: string) => !!v || 'This field is required'
    }
    
    const login = async () => {
      if (!valid.value) return
      
      loading.value = true
      error.value = ''
      
      try {
        const success = await authStore.login(personalId.value, password.value)
        
        if (success) {
          router.push('/')
        } else {
          error.value = 'Invalid credentials. Please try again.'
        }
      } catch (err) {
        error.value = 'An error occurred during login. Please try again.'
        console.error(err)
      } finally {
        loading.value = false
      }
    }
    
    return {
      form,
      valid,
      loading,
      error,
      personalId,
      password,
      rules,
      login
    }
  }
})
</script>
