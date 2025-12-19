import { ref, computed } from 'vue'
import api from '../services/api'

export function useNotifications() {
  const notifications = ref([])
  const showNotifications = ref(false)

  const incomingRequests = computed(() =>
    notifications.value.filter(
      n => n.data?.type === 'friend_request_received'
    )
  )

  const unreadNotificationCount = computed(
    () => incomingRequests.value.filter(n => !n.read_at).length
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
    const unread = incomingRequests.value.filter(n => !n.read_at)
    await Promise.all(unread.map(n => markNotificationRead(n)))
  }

  const toggleNotifications = async () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value && !notifications.value.length) {
      await loadNotifications()
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
  }
}

