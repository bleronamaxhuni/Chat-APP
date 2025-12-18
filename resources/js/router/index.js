import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/auth/Login.vue'
import Register from '../pages/auth/Register.vue'
import Dashboard from '../pages/Dashboard.vue'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', component: Login, meta: { guest: true } },
  { path: '/register', component: Register, meta: { guest: true } },
  { path: '/', component: Dashboard, meta: { auth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  // Ensure we have attempted to load the current user before enforcing guards
  if (!auth.initialized) {
    await auth.fetchUser()
  }

  if (to.meta.auth && !auth.isAuthenticated) return next('/login')
  if (to.meta.guest && auth.isAuthenticated) return next('/')
  next()
})

export default router
