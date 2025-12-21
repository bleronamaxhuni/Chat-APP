import axios from 'axios'
import router from '../router'
import { useAuthStore } from '../stores/auth'

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const auth = useAuthStore()
            const isMeEndpoint = error.config?.url?.includes('/me')
            const currentPath = router.currentRoute.value?.path || window.location.pathname
            const isGuestRoute = currentPath === '/login' || currentPath === '/register'

            auth.user = null
            if (!auth.initialized) {
                auth.initialized = true
            }

            if (isMeEndpoint && isGuestRoute) {
                return Promise.reject(error)
            }

            if (!isGuestRoute) {
                router.push('/login')
            }
        }
        return Promise.reject(error)
    }
)

export default api
