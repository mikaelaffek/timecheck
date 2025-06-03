<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Change Password</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8" lg="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon left color="primary" class="mr-2">mdi-lock</v-icon>
            Update Password
          </v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="valid" @submit.prevent="changePassword">
              <v-text-field
                v-model="passwords.current_password"
                label="Current Password"
                prepend-icon="mdi-lock-outline"
                type="password"
                :rules="[rules.required]"
                required
              ></v-text-field>

              <v-text-field
                v-model="passwords.new_password"
                label="New Password"
                prepend-icon="mdi-lock"
                type="password"
                :rules="[rules.required, rules.minLength]"
                required
              ></v-text-field>

              <v-text-field
                v-model="passwords.confirm_password"
                label="Confirm New Password"
                prepend-icon="mdi-lock-check"
                type="password"
                :rules="[rules.required, rules.passwordMatch]"
                required
              ></v-text-field>

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
              @click="changePassword"
            >
              Update Password
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, reactive } from 'vue'
import { useApi } from '@/composables/useApi'

interface PasswordForm {
  current_password: string
  new_password: string
  confirm_password: string
}

export default defineComponent({
  name: 'ChangePassword',
  setup() {
    const valid = ref(false)
    const success = ref('')
    const form = ref(null)
    const api = useApi()
    
    const passwords = reactive<PasswordForm>({
      current_password: '',
      new_password: '',
      confirm_password: ''
    })

    const rules = {
      required: (v: string) => !!v || 'This field is required',
      minLength: (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
      passwordMatch: (v: string) => v === passwords.new_password || 'Passwords do not match'
    }
    
    const changePassword = async () => {
      success.value = ''
      
      if (!form.value || !form.value.validate()) {
        return
      }

      const result = await api.put('/api/user/password', {
        current_password: passwords.current_password,
        password: passwords.new_password
      }, { showSuccessNotification: true, successMessage: 'Password changed successfully' })

      if (result) {
        // Reset form fields
        passwords.current_password = ''
        passwords.new_password = ''
        passwords.confirm_password = ''
        if (form.value) {
          form.value.reset()
        }
        success.value = 'Password changed successfully'
      }
    }

    return {
      valid,
      loading: api.loading,
      error: api.snackbar.text,
      success,
      passwords,
      rules,
      changePassword,
      form
    }
  }
})
</script>
