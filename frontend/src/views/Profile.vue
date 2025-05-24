<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">My Profile</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8" lg="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon left color="primary" class="mr-2">mdi-account</v-icon>
            Profile Information
          </v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="saveProfile">
              <v-text-field
                v-model="profile.name"
                label="Full Name"
                prepend-icon="mdi-account"
                :rules="[rules.required]"
                required
              ></v-text-field>

              <v-text-field
                v-model="profile.email"
                label="Email"
                prepend-icon="mdi-email"
                :rules="[rules.required, rules.email]"
                required
              ></v-text-field>

              <v-text-field
                v-model="profile.phone"
                label="Phone Number"
                prepend-icon="mdi-phone"
              ></v-text-field>

              <v-select
                v-model="profile.department_id"
                :items="departments"
                item-title="name"
                item-value="id"
                label="Department"
                prepend-icon="mdi-domain"
              ></v-select>

              <v-alert
                v-if="error"
                type="error"
                dismissible
                class="mt-3"
              >
                {{ error }}
              </v-alert>

              <v-alert
                v-if="success"
                type="success"
                dismissible
                class="mt-3"
              >
                {{ success }}
              </v-alert>
            </v-form>
          </v-card-text>
          <v-card-actions class="px-4 pb-4">
            <v-spacer></v-spacer>
            <v-btn
              color="primary"
              :loading="loading"
              :disabled="!valid || loading"
              @click="saveProfile"
            >
              Save Changes
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, reactive } from 'vue'
import axios from 'axios'

interface Department {
  id: number
  name: string
}

interface Profile {
  name: string
  email: string
  phone: string | null
  department_id: number | null
}

export default defineComponent({
  name: 'Profile',
  setup() {
    const valid = ref(false)
    const loading = ref(false)
    const error = ref('')
    const success = ref('')
    const departments = ref<Department[]>([])
    const profile = reactive<Profile>({
      name: '',
      email: '',
      phone: null,
      department_id: null
    })

    const rules = {
      required: (v: string) => !!v || 'This field is required',
      email: (v: string) => {
        const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        return pattern.test(v) || 'Invalid email address'
      }
    }

    const fetchProfile = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/user')
        const user = response.data
        profile.name = user.name
        profile.email = user.email
        profile.phone = user.phone
        profile.department_id = user.department_id
      } catch (err) {
        error.value = 'Failed to load profile information'
        console.error('Error fetching profile:', err)
      } finally {
        loading.value = false
      }
    }

    const fetchDepartments = async () => {
      try {
        const response = await axios.get('/api/departments')
        departments.value = response.data
      } catch (err) {
        console.error('Error fetching departments:', err)
      }
    }

    const saveProfile = async () => {
      loading.value = true
      error.value = ''
      success.value = ''
      
      try {
        await axios.put('/api/user/profile', profile)
        success.value = 'Profile updated successfully'
      } catch (err) {
        error.value = 'Failed to update profile'
        console.error('Error updating profile:', err)
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchProfile()
      fetchDepartments()
    })

    return {
      valid,
      loading,
      error,
      success,
      profile,
      departments,
      rules,
      saveProfile
    }
  }
})
</script>
