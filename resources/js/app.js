import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

const auth = useAuthStore(pinia)

const currentPath = window.location.pathname
const isGuestRoute = currentPath === '/login' || currentPath === '/register'

if (isGuestRoute) {
  app.mount('#app')
} else {
  auth.fetchUser().finally(() => {
    app.mount('#app')
  })
}
