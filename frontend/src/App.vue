<template>
  <v-app>
    <!-- Top App Bar -->
    <v-app-bar app dark v-if="hasToken" elevation="1">
      <div class="d-flex align-center">
        <img src="/timecheck-logo.svg" alt="Timecheck Logo" height="40" class="ml-4" />
      </div>
      <v-spacer></v-spacer>
      <v-btn icon class="mr-2">
        <v-icon>mdi-bell</v-icon>
      </v-btn>
      <v-btn icon class="mr-2">
        <v-icon>mdi-help-circle</v-icon>
      </v-btn>
      <v-menu offset-y>
        <template v-slot:activator="{ on, attrs }">
          <v-btn icon v-bind="attrs" v-on="on" class="mr-4">
            <v-avatar size="32" color="grey lighten-2">
              <v-icon v-if="!authStore.user?.avatar" color="grey darken-3">mdi-account</v-icon>
              <img v-else :src="authStore.user.avatar" alt="User Avatar" />
            </v-avatar>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/profile">
            <v-list-item-icon>
              <v-icon>mdi-account</v-icon>
            </v-list-item-icon>
            <v-list-item-title>My Profile</v-list-item-title>
          </v-list-item>
          <v-list-item to="/preferences">
            <v-list-item-icon>
              <v-icon>mdi-tune</v-icon>
            </v-list-item-icon>
            <v-list-item-title>Preferences</v-list-item-title>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-item @click="logout">
            <v-list-item-icon>
              <v-icon>mdi-logout</v-icon>
            </v-list-item-icon>
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Left Side Navigation with Icons Only -->
    <v-navigation-drawer 
      v-if="hasToken && $route.name !== 'LandingPage' && $route.name !== 'Login'" 
      app 
      permanent 
      mini-variant 
      mini-variant-width="64"
      color="blue-grey darken-4"
      dark
      class="side-menu"
    >
      <v-list nav dense>
        <v-list-item @click="navigateTo('/dashboard')" link class="py-2">
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Dashboard</v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-item @click="navigateTo('/time-registrations')" link class="py-2">
          <v-list-item-icon>
            <v-icon>mdi-clock-outline</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Time</v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <!-- Schedule and Reports menu items have been removed -->

        <v-list-item v-if="isAdmin || isManager" @click="navigateTo('/admin/time-registrations')" link class="py-2">
          <v-list-item-icon>
            <v-icon>mdi-account-group</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Employees</v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-item v-if="isAdmin" @click="navigateTo('/users')" link class="py-2">
          <v-list-item-icon>
            <v-icon>mdi-account-cog</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Admin</v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-item @click="navigateTo('/settings')" link class="py-2">
          <v-list-item-icon>
            <v-icon>mdi-cog</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Settings</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        
        <!-- Spacer to push logout to bottom -->
        <v-spacer></v-spacer>
        
        <!-- Logout button at the bottom -->
        <v-list-item @click="logout" link class="py-2 mt-auto">
          <v-list-item-icon>
            <v-icon>mdi-logout</v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- Main Content Area -->
    <v-main>
      <v-container fluid class="pa-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'

export default defineComponent({
  name: 'App',
  setup() {
    const drawer = ref(false)
    const authStore = useAuthStore()
    const router = useRouter()

    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const isAdmin = computed(() => authStore.user?.role === 'admin')
    const isManager = computed(() => authStore.user?.role === 'manager')
    const hasToken = computed(() => !!localStorage.getItem('token'))

    const logout = async () => {
      await authStore.logout()
      router.push('/login')
    }
    
    // Navigation function to prevent redundant navigation
    const navigateTo = (path) => {
      // Check if we're already on the requested path
      if (router.currentRoute.value.path !== path) {
        console.log(`Navigating to ${path}`)
        router.push(path)
      } else {
        console.log(`Already on ${path}, skipping navigation`)
      }
    }
    
    onMounted(async () => {
      console.log('App mounted, checking authentication...')
      await authStore.checkAuth()
      console.log('Authentication state:', authStore.isAuthenticated)
      console.log('User:', authStore.user)
    })

    return {
      drawer,
      authStore, // Expose authStore to the template
      isAuthenticated,
      isAdmin,
      isManager,
      hasToken,
      logout,
      navigateTo // Add the navigation function to the template
    }
  }
})
</script>

<style>
#app {
  font-family: 'Roboto', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background-color: #F5F5F5;
}

/* Side menu styling */
.side-menu {
  border-right: none;
}

.side-menu .v-list-item {
  margin: 8px 0;
}

.side-menu .v-list-item--active {
  background-color: #4ADE80;
}

.side-menu .v-list-item--active .v-icon {
  color: white;
}

.side-menu .v-list-item:not(.v-list-item--active) .v-icon {
  color: #90A4AE;
}

.side-menu .v-list-item:hover:not(.v-list-item--active) {
  background-color: rgba(74, 222, 128, 0.15);
}

/* App bar styling */
.v-app-bar.v-app-bar--fixed {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

/* Primary button color */
.v-btn.primary,
.v-app-bar.primary {
  /* Original styling restored */
}

/* Card styling */
.v-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Main content area padding */
.v-main .v-container {
  padding-left: 80px; /* Adjusted for the side menu width */
}

/* Avatar in the top bar */
.v-avatar {
  cursor: pointer;
}
</style>
