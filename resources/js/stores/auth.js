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
    async fetchUser() {
      try {
        const res = await api.get('/me')
        this.user = res.data
      } catch {
        this.user = null
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
      await api.post('/logout')
      this.user = null
    }
  }
})
