import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: state => !!state.user,
  },
  actions: {
    async fetchUser(skipIfNoSession = false) {
      if (skipIfNoSession) {
        const hasSession = document.cookie.includes('laravel-session=')
        if (!hasSession) {
          this.user = null
          this.initialized = true
          return
        }
      }

      try {
        const res = await api.get('/me')
        this.user = res.data
      } catch (error) {
        if (error.response?.status === 401) {
          this.user = null
        } else {
          this.user = null
        }
      } finally {
        this.initialized = true
      }
    },

    async login(email, password) {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/login', { email, password })
      await this.fetchUser()
    },

    async register(name, email, password, password_confirmation) {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/register', { name, email, password, password_confirmation })
      await this.fetchUser()
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.initialized = false
      }
    }
  }
})
