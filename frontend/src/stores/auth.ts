import { defineStore } from 'pinia'
import axios from 'axios'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface AuthState {
  user: User | null
  token: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('token')
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token
  },
  
  actions: {
    async login(personalId: string, password: string) {
      try {
        const response = await axios.post('/api/login', {
          personal_id: personalId,
          password
        })
        
        const { user, token } = response.data
        
        this.user = user
        this.token = token
        
        localStorage.setItem('token', token)
        
        // Set the Authorization header for all future requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        console.log('Login successful, token set:', token)
        return true
      } catch (error) {
        console.error('Login failed:', error)
        return false
      }
    },
    
    async logout() {
      try {
        await axios.post('/api/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
      }
    },
    
    async checkAuth() {
      if (!this.token) return false
      
      try {
        const response = await axios.get('/api/user')
        this.user = response.data
        return true
      } catch (error) {
        this.user = null
        this.token = null
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
        return false
      }
    }
  }
})
