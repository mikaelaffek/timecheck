import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Views
const LandingPage = () => import('../views/LandingPage.vue')
const Login = () => import('../views/Login.vue')
const Dashboard = () => import('../views/DashboardSimple.vue') // Using the simplified version
const TimeRegistrations = () => import('../views/TimeRegistrations.vue')
const AdminTimeRegistrations = () => import('../views/AdminTimeRegistrations.vue')
const Settings = () => import('../views/Settings.vue')
const Profile = () => import('../views/Profile.vue')
const ChangePassword = () => import('../views/ChangePassword.vue')
const Preferences = () => import('../views/Preferences.vue')
const UserManagement = () => import('../views/UserManagement.vue')
const NotFound = () => import('../views/NotFound.vue')

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'LandingPage',
    component: LandingPage
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/time-registrations',
    name: 'TimeRegistrations',
    component: TimeRegistrations,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/time-registrations',
    name: 'AdminTimeRegistrations',
    component: AdminTimeRegistrations,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  // Schedules and Reports routes have been removed
  {
    path: '/settings',
    name: 'Settings',
    component: Settings,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true }
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: ChangePassword,
    meta: { requiresAuth: true }
  },
  {
    path: '/preferences',
    name: 'Preferences',
    component: Preferences,
    meta: { requiresAuth: true }
  },
  {
    path: '/users',
    name: 'UserManagement',
    component: UserManagement,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)
  
  // If token exists but user is not loaded, try to load user info
  if (authStore.token && !authStore.user) {
    console.log('Token exists but user not loaded, checking authentication...')
    await authStore.checkAuth()
    console.log('After check, authenticated:', authStore.isAuthenticated)
  }
  
  if (requiresAuth && !authStore.isAuthenticated) {
    console.log('Route requires auth but user is not authenticated, redirecting to login')
    next('/login')
  } else if (requiresAdmin && authStore.user?.role !== 'admin') {
    console.log('Route requires admin but user is not admin, redirecting to dashboard')
    next('/dashboard')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    console.log('User is already authenticated, redirecting to dashboard')
    next('/dashboard')
  } else {
    console.log('Navigation allowed to:', to.path)
    next()
  }
})

export default router
