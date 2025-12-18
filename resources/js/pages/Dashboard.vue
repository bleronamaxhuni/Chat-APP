<template>
  <div class="min-h-screen flex flex-col bg-gray-100">

    <header class="relative bg-white shadow flex items-center justify-between px-6 py-3">
      <h1 class="text-xl font-bold">Chat App</h1>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button
            @click="toggleNotifications"
            class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none"
          >
            <i class="fa-regular fa-bell text-gray-700"></i>
            <span
              v-if="unreadNotificationCount > 0"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full"
            >
              {{ unreadNotificationCount }}
            </span>
          </button>

          <div
            v-if="showNotifications"
            class="absolute right-0 top-full mt-2 w-80 bg-white border rounded shadow-lg z-30"
          >
            <div class="px-4 py-3 border-b flex items-center justify-between">
              <h3 class="font-semibold text-sm">Notifications</h3>
              <button
                v-if="unreadNotificationCount > 0"
                @click="markAllNotificationsRead"
                class="text-xs text-blue-500 hover:underline"
              >
                Mark all as read
              </button>
            </div>
            <div class="max-h-80 overflow-y-auto px-4 py-3 text-sm">
              <ul class="space-y-2">
                <li v-if="!incomingRequests.length" class="text-gray-500">
                  No incoming friend requests.
                </li>
                <li
                  v-for="n in incomingRequests"
                  :key="n.id"
                  class="border-b last:border-b-0 pb-2"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1 mr-2">
                      <p class="font-medium">
                        <span v-if="!n.data.result">
                          {{ n.data.from_user_name }} sent you a friend request
                        </span>
                        <span v-else-if="n.data.result === 'accepted'">
                          You are now friends with {{ n.data.from_user_name }}
                        </span>
                        <span v-else-if="n.data.result === 'rejected'">
                          You declined {{ n.data.from_user_name }}'s friend request
                        </span>
                      </p>
                    </div>
                    <span
                      v-if="!n.read_at"
                      class="mt-1 h-2 w-2 rounded-full bg-blue-500"
                    ></span>
                  </div>
                  <div v-if="!n.data.result" class="mt-2 flex space-x-2 text-xs">
                    <button
                      @click="acceptRequest(n)"
                      class="px-2 py-1 rounded bg-green-500 text-white hover:bg-green-600"
                    >
                      Accept
                    </button>
                    <button
                      @click="rejectRequest(n)"
                      class="px-2 py-1 rounded bg-red-500 text-white hover:bg-red-600"
                    >
                      Decline
                    </button>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="relative">
          <button
            @click="toggleFriendRequests"
            class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none "
          >
          <i class="fa-solid fa-user-plus text-gray-700"></i>
          </button>
          <div
            v-if="showFriendRequests"
            class="absolute right-0 top-full mt-2 w-96 bg-white border rounded shadow-lg z-20"
          >
            <div class="px-4 py-3 border-b">
              <h3 class="font-semibold text-sm">Requests Sent</h3>
            </div>
            <div class="max-h-80 overflow-y-auto px-4 py-3 text-sm">
              <ul class="space-y-2">
                <li v-if="!friendRequests.outgoing.length" class="text-gray-500">
                  You haven't sent any friend requests yet.
                </li>
                <li
                  v-for="req in friendRequests.outgoing"
                  :key="`out-${req.id}`"
                  class="flex justify-between items-center border-b last:border-b-0 pb-2"
                >
                  <div class="flex flex-col">
                    <span class="font-medium">{{ req.addressee.name }}</span>
                    <span class="text-xs text-gray-500">{{ req.addressee.email }}</span>
                  </div>
                  <span
                    class="text-xs px-2 py-1 rounded-full"
                    :class="{
                      'bg-yellow-100 text-yellow-800': req.status === 'pending',
                      'bg-green-100 text-green-800': req.status === 'accepted',
                      'bg-red-100 text-red-800': req.status === 'rejected',
                    }"
                  >
                    {{ req.status }}
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <button @click="logout" class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none">
          <i class="fa-solid fa-arrow-right-from-bracket" text-gray-700></i>
        </button>
      </div>

    </header>

    <div class="flex flex-1 overflow-hidden">

      <aside class="w-64 bg-white border-r p-4 flex flex-col overflow-y-auto">
        <div class="flex flex-col items-center mb-6">
          <img class="w-24 h-24 rounded-full mb-2" :src="profile.photo" alt="Profile photo" />
          <h2 class="font-bold text-lg">{{ profile.name }}</h2>
          <p class="text-sm text-gray-500">{{ profile.email }}</p>
        </div>

        <h3 class="font-semibold mb-2">Suggested Friends</h3>
        <ul class="space-y-2">
          <li v-for="friend in suggestedFriends" :key="friend.id" class="flex items-center justify-between bg-gray-100 p-2 rounded hover:bg-gray-200">
            <div class="flex items-center space-x-2">
              <img class="w-8 h-8 rounded-full" :src="friend.photo" alt="Friend photo" />
              <span>{{ friend.name }}</span>
            </div>
            <button @click="addFriend(friend)" class="text-sm text-blue-500 hover:underline">Add</button>
          </li>
        </ul>
      </aside>

      <main class="flex-1 p-6 overflow-y-auto">
        <div v-for="post in posts" :key="post.id" class="bg-white p-4 rounded mb-4 shadow">
          <h4 class="font-bold mb-1">{{ post.author }}</h4>
          <p>{{ post.content }}</p>
        </div>
      </main>

      <aside :class="['w-80 bg-white border-l p-4 transition-transform duration-300', showConversations ? 'translate-x-0' : 'translate-x-full']">
        <h3 class="font-semibold mb-4">Conversations</h3>
        <ul class="space-y-2">
          <li v-for="conv in conversations" :key="conv.id" class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer">
            <img class="w-8 h-8 rounded-full mr-2" :src="conv.photo" alt="User" />
            <div>
              <p class="font-semibold">{{ conv.name }}</p>
              <p class="text-sm text-gray-500">{{ conv.lastMessage }}</p>
            </div>
          </li>
        </ul>
      </aside>

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

const auth = useAuthStore()
const router = useRouter()

const logout = async () => {
  await auth.logout()
  router.push('/login')
}

const profile = computed(() => ({
  name: auth.user?.name,
  email: auth.user?.email,
  photo: 'https://i.pravatar.cc/150?img=3'
}))

const suggestedFriends = ref([])
const friendRequests = ref({ outgoing: [], incoming: [] })
const showFriendRequests = ref(false)

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

const addFriend = async (friend) => {
  try {
    await api.post('/friendships', { user_id: friend.id })
    suggestedFriends.value = suggestedFriends.value.filter(f => f.id !== friend.id)
    await loadFriendRequests()
  } catch (e) {
    console.error('Failed to add friend', e)
  }
}

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

const loadNotifications = async () => {
  try {
    const res = await api.get('/notifications')
    notifications.value = res.data
  } catch (e) {
    console.error('Failed to load notifications', e)
  }
}

const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value && !notifications.value.length) {
    await loadNotifications()
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

const posts = ref([
  { id: 1, author: 'Alice', content: 'Hello world!' },
  { id: 2, author: 'Bob', content: 'This is a dummy post' },
])

const conversations = ref([
  { id: 1, name: 'Alice', lastMessage: 'Hey there!', photo: 'https://i.pravatar.cc/150?img=1' },
  { id: 2, name: 'Bob', lastMessage: 'How are you?', photo: 'https://i.pravatar.cc/150?img=2' },
])

const showConversations = ref(false)

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

<style>
aside.translate-x-full {
  transform: translateX(100%);
}

aside.translate-x-0 {
  transform: translateX(0);
}
</style>
