<template>
  <v-app>
    <v-app-bar app color="primary" dark v-if="isAuthenticated">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>Timetjek</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" app v-if="isAuthenticated">
      <v-list>
        <v-list-item to="/" prepend-icon="mdi-home">
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item to="/time-registrations" prepend-icon="mdi-clock">
          <v-list-item-title>Time Registrations</v-list-item-title>
        </v-list-item>
        <v-list-item to="/reports" prepend-icon="mdi-file-document">
          <v-list-item-title>Reports</v-list-item-title>
        </v-list-item>
        <v-list-item to="/settings" prepend-icon="mdi-cog">
          <v-list-item-title>Settings</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>

    <v-footer app>
      <span>&copy; {{ new Date().getFullYear() }} Timetjek</span>
    </v-footer>
  </v-app>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'

export default defineComponent({
  name: 'App',
  setup() {
    const drawer = ref(false)
    const authStore = useAuthStore()
    const router = useRouter()

    const isAuthenticated = computed(() => authStore.isAuthenticated)

    const logout = async () => {
      await authStore.logout()
      router.push('/login')
    }

    return {
      drawer,
      isAuthenticated,
      logout
    }
  }
})
</script>

<style>
#app {
  font-family: 'Roboto', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
