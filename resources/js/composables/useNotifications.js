import { ref, computed } from 'vue'
import api from '../services/api'
import echo from '../services/echo'
import { useAuthStore } from '../stores/auth'

export function useNotifications() {
  const notifications = ref([])
  const showNotifications = ref(false)
  const auth = useAuthStore()
  let notificationChannel = null

  const incomingRequests = computed(() =>
    notifications.value.filter(
      n => n.data?.type === 'friend_request_received' 
        || n.data?.type === 'friend_request_accepted'
        || n.data?.type === 'post_liked'
        || n.data?.type === 'post_commented'
    )
  )

  const unreadNotificationCount = computed(
    () => notifications.value.filter(n => !n.read_at).length
  )

  const loadNotifications = async () => {
    try {
      const res = await api.get('/notifications')
      notifications.value = res.data
    } catch (e) {
      console.error('Failed to load notifications', e)
    }
  }

  const markNotificationRead = async (notification) => {
    try {
      await api.post(`/notifications/${notification.id}/read`)
      notification.read_at = new Date().toISOString()
    } catch (e) {
      console.error('Failed to mark notification as read', e)
    }
  }

  const markAllNotificationsRead = async () => {
    const unread = notifications.value.filter(n => !n.read_at)
    await Promise.all(unread.map(n => markNotificationRead(n)))
  }

  const toggleNotifications = async () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value && !notifications.value.length) {
      await loadNotifications()
    }
  }

  const setupNotificationListener = (existingChannel = null) => {
    if (!auth.user?.id) return null

    notificationChannel = existingChannel || echo.private(`user.${auth.user.id}`)

    if (!existingChannel) {
      notificationChannel
        .subscribed(() => {
          console.log('✅ Subscribed to notification channel for user:', auth.user.id)
        })
        .error((error) => {
          console.error('❌ Notification channel subscription error:', error)
        })
    }

    notificationChannel.listen('.notification.created', (data) => {
      console.log('🔔 Notification received in real-time:', data)
      notifications.value.unshift(data)
    })

    return notificationChannel
  }

  const removeNotificationListener = () => {
    if (notificationChannel) {
      notificationChannel.stopListening('.notification.created')
      echo.leave(`user.${auth.user?.id}`)
      notificationChannel = null
    }
  }

  return {
    notifications,
    showNotifications,
    incomingRequests,
    unreadNotificationCount,
    loadNotifications,
    markNotificationRead,
    markAllNotificationsRead,
    toggleNotifications,
    setupNotificationListener,
    removeNotificationListener,
  }
}

