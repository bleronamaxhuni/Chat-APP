<template>
  <div class="min-h-screen flex flex-col bg-gray-100">
    <DashboardHeader
      :notifications="notifications"
      :friend-requests="friendRequests"
      :show-notifications="showNotifications"
      :show-friend-requests="showFriendRequests"
      @logout="logout"
      @accept-request="acceptRequest"
      @reject-request="rejectRequest"
      @toggle-notifications="toggleNotifications"
      @toggle-friend-requests="toggleFriendRequests"
      @mark-all-notifications-read="markAllNotificationsRead"
    />

    <div class="flex flex-1 overflow-hidden">
      <aside class="w-64 bg-white border-r p-4 flex flex-col overflow-y-auto">
        <UserProfile :profile="profile" />
        <SuggestedFriendsList :friends="suggestedFriends" @add-friend="addFriend" />
      </aside>

      <main class="flex-1 p-6 overflow-y-auto">
        <div v-for="post in posts" :key="post.id" class="bg-white p-4 rounded mb-4 shadow">
          <h4 class="font-bold mb-1">{{ post.author }}</h4>
          <p>{{ post.content }}</p>
        </div>
      </main>

      <ConversationsSidebar :conversations="conversations" :is-open="showConversations" />

      <button
        @click="showConversations = !showConversations"
        class="fixed bottom-6 right-6 bg-blue-500 text-white p-4 rounded-full shadow-lg hover:bg-blue-600"
      >
        💬
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useNotifications } from '../composables/useNotifications'
import { useFriendRequests } from '../composables/useFriendRequests'
import DashboardHeader from '../components/dashboard/DashboardHeader.vue'
import UserProfile from '../components/dashboard/UserProfile.vue'
import SuggestedFriendsList from '../components/dashboard/SuggestedFriendsList.vue'
import ConversationsSidebar from '../components/dashboard/ConversationsSidebar.vue'

const auth = useAuthStore()
const router = useRouter()

const {
  notifications,
  showNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  toggleNotifications: toggleNotificationsComposable,
  loadNotifications,
} = useNotifications()

const {
  friendRequests,
  showFriendRequests,
  toggleFriendRequests: toggleFriendRequestsComposable,
  loadFriendRequests,
} = useFriendRequests()

const logout = async () => {
  await auth.logout()
  router.push('/login')
}

const profile = computed(() => ({
  name: auth.user?.name,
  email: auth.user?.email,
  photo: 'https://i.pravatar.cc/150?img=3',
}))

const suggestedFriends = ref([])
const showConversations = ref(false)

const posts = ref([
  { id: 1, author: 'Alice', content: 'Hello world!' },
  { id: 2, author: 'Bob', content: 'This is a dummy post' },
])

const conversations = ref([
  { id: 1, name: 'Alice', lastMessage: 'Hey there!', photo: 'https://i.pravatar.cc/150?img=1' },
  { id: 2, name: 'Bob', lastMessage: 'How are you?', photo: 'https://i.pravatar.cc/150?img=2' },
])

const toggleNotifications = async () => {
  await toggleNotificationsComposable()
}

const toggleFriendRequests = async () => {
  await toggleFriendRequestsComposable()
}

const addFriend = async (friend) => {
  try {
    await api.post('/friendships', { user_id: friend.id })
    suggestedFriends.value = suggestedFriends.value.filter(f => f.id !== friend.id)
    await loadFriendRequests()
  } catch (e) {
    console.error('Failed to add friend', e)
  }
}

const acceptRequest = async (notification) => {
  try {
    await api.post(`/friendships/${notification.data.friendship_id}/accept`)
    await markNotificationRead(notification)
    notification.data.result = 'accepted'
    await loadFriendRequests()
  } catch (e) {
    console.error('Failed to accept friend request', e)
  }
}

const rejectRequest = async (notification) => {
  try {
    await api.post(`/friendships/${notification.data.friendship_id}/reject`)
    await markNotificationRead(notification)
    notification.data.result = 'rejected'
  } catch (e) {
    console.error('Failed to reject friend request', e)
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/friendships/suggested', {
      params: { online_only: 1 },
    })
    suggestedFriends.value = res.data.map((user, index) => ({
      ...user,
      photo: `https://i.pravatar.cc/150?img=${(index % 10) + 1}`,
    }))
  } catch (e) {
    console.error('Failed to load suggested friends', e)
  }

  await loadFriendRequests()
  await loadNotifications()
})
</script>
