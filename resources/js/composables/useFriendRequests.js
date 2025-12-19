import { ref } from 'vue'
import api from '../services/api'

export function useFriendRequests() {
  const friendRequests = ref({ outgoing: [], incoming: [] })
  const showFriendRequests = ref(false)

  const loadFriendRequests = async () => {
    try {
      const res = await api.get('/friendships/requests')
      friendRequests.value = res.data
    } catch (e) {
      console.error('Failed to load friend requests', e)
    }
  }

  const toggleFriendRequests = async () => {
    showFriendRequests.value = !showFriendRequests.value
    if (showFriendRequests.value && !friendRequests.value.outgoing.length && !friendRequests.value.incoming.length) {
      await loadFriendRequests()
    }
  }

  return {
    friendRequests,
    showFriendRequests,
    loadFriendRequests,
    toggleFriendRequests,
  }
}

