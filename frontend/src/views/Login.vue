<template>
  <div class="login-page">
    <!-- Left side with logo and welcome message -->
    <div class="login-left-panel">
      <div class="login-logo-container">
        <img src="/logo-white-bg.svg" alt="Timetjek Logo" height="80" />
        <h1 class="login-welcome-text">Welcome to Timetjek</h1>
        <p class="login-subtitle">Your complete time management solution</p>
      </div>
    </div>

    <!-- Right side with login form -->
    <div class="login-right-panel">
      <div class="login-form-container">
        <h2 class="login-form-title">Sign In</h2>
        <p class="login-form-subtitle">Enter your credentials to access your account</p>
        
        <v-form ref="form" v-model="valid" @submit.prevent="login" class="login-form">
          <v-text-field
            v-model="personalId"
            label="Personal ID"
            name="personal-id"
            prepend-inner-icon="mdi-account"
            type="text"
            :rules="[rules.required]"
            required
            variant="outlined"
            bg-color="white"
            class="login-input"
          ></v-text-field>

          <v-text-field
            v-model="password"
            label="Password"
            name="password"
            prepend-inner-icon="mdi-lock"
            type="password"
            :rules="[rules.required]"
            required
            variant="outlined"
            bg-color="white"
            class="login-input"
          ></v-text-field>

          <div class="login-options">
            <v-checkbox label="Remember me" hide-details class="login-remember-me"></v-checkbox>
            <a href="#" class="login-forgot-password">Forgot password?</a>
          </div>

          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            closable
            class="login-error mt-4"
          >
            {{ error }}
          </v-alert>

          <v-btn 
            color="primary" 
            :loading="loading" 
            :disabled="!valid || loading" 
            @click="login"
            block
            size="large"
            class="login-button mt-6"
            elevation="0"
          >
            Sign In
          </v-btn>
        </v-form>

        <div class="login-footer">
          <p>&copy; {{ new Date().getFullYear() }} Timetjek. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>
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

<style scoped>
.login-page {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
}

/* Left panel styling */
.login-left-panel {
  flex: 1;
  background-color: rgb(var(--v-theme-primary)) !important;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
  overflow: hidden;
}

.login-left-panel::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('/public/pattern.svg') repeat;
  opacity: 0.05;
  z-index: 1;
}

.login-logo-container {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
}

.login-welcome-text {
  margin-top: 2rem;
  font-size: 2.5rem;
  font-weight: 600;
}

.login-subtitle {
  margin-top: 1rem;
  font-size: 1.2rem;
  opacity: 0.8;
}

/* Right panel styling */
.login-right-panel {
  flex: 1;
  background-color: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-form-container {
  width: 100%;
  max-width: 450px;
  padding: 2rem;
}

.login-form-title {
  font-size: 2rem;
  font-weight: 600;
  color: #1C3A2A;
  margin-bottom: 0.5rem;
}

.login-form-subtitle {
  color: #666;
  margin-bottom: 2rem;
}

.login-input {
  margin-bottom: 1.5rem;
}

.login-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
}

.login-forgot-password {
  color: #4ADE80;
  text-decoration: none;
  font-size: 0.9rem;
}

.login-button {
  margin-top: 2rem;
  /* Original styling restored */
  color: white;
  font-weight: 500;
  letter-spacing: 0.5px;
  border-radius: 8px;
  height: 50px;
  text-transform: none;
  font-size: 1.1rem;
}

.login-footer {
  margin-top: 3rem;
  text-align: center;
  color: #888;
  font-size: 0.9rem;
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .login-page {
    flex-direction: column;
  }
  
  .login-left-panel {
    flex: 0 0 200px;
  }
  
  .login-welcome-text {
    font-size: 1.8rem;
  }
  
  .login-subtitle {
    font-size: 1rem;
  }
}
</style>
