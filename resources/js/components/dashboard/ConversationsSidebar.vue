<template>
  <aside
    :class="[
      'w-80 bg-white border-l p-4 transition-transform duration-300 flex flex-col overflow-hidden',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
  >
    <h3 class="font-semibold mb-4 flex-shrink-0">Conversations</h3>
    
    <div class="flex-1 overflow-y-auto">
      <div v-if="loading" class="flex justify-center items-center py-8">
        <div class="text-gray-500">Loading...</div>
      </div>

      <div v-if="!loading && conversations.length > 0" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Conversations</h4>
        <ul class="space-y-2">
          <li
            v-for="conv in conversations"
            :key="conv.id"
            @click="openConversation(conv.id, conv.user_id)"
            class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer transition-colors"
          >
            <img 
              class="w-10 h-10 rounded-full mr-3 object-cover flex-shrink-0" 
              :src="getAvatarUrl(conv.email || conv.name)" 
              :alt="conv.name"
              @error="handleImageError"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-sm truncate">{{ conv.name }}</p>
                <span 
                  v-if="conv.unreadCount > 0" 
                  class="bg-blue-500 text-white text-xs rounded-full px-2 py-0.5 ml-2 flex-shrink-0"
                >
                  {{ conv.unreadCount }}
                </span>
              </div>
              <p class="text-xs text-gray-500 truncate">{{ conv.lastMessage || 'No messages yet' }}</p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="!loading && friends.length > 0">
        <h4 class="text-sm font-medium text-gray-700 mb-2">
          {{ conversations.length > 0 ? 'Friends' : 'Your Friends' }}
        </h4>
        <ul class="space-y-2">
          <li
            v-for="friend in friends"
            :key="friend.id"
            @click="openConversationWithFriend(friend)"
            class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer transition-colors"
          >
            <div class="relative flex-shrink-0">
              <img 
                class="w-10 h-10 rounded-full mr-3 object-cover" 
                :src="getAvatarUrl(friend.email || friend.name)" 
                :alt="friend.name"
                @error="handleImageError"
              />
              <span 
                v-if="isOnline(friend.last_seen_at)"
                class="absolute bottom-0 right-2 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
              ></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm truncate">{{ friend.name }}</p>
              <p class="text-xs text-gray-500">
                {{ isOnline(friend.last_seen_at) ? 'Online' : 'Offline' }}
              </p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="!loading && conversations.length === 0 && friends.length === 0" class="flex flex-col items-center justify-center py-8 text-gray-500">
        <p class="text-sm">No conversations yet</p>
        <p class="text-xs mt-1">Start chatting with your friends!</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['open-chat'])

const conversations = ref([])
const friends = ref([])
const loading = ref(false)

const getAvatarUrl = (identifier) => {
  if (!identifier) return 'https://i.pravatar.cc/150?img=1'
  const hash = identifier.split('').reduce((acc, char) => {
    return char.charCodeAt(0) + ((acc << 5) - acc)
  }, 0)
  const index = Math.abs(hash % 10) + 1
  return `https://i.pravatar.cc/150?img=${index}`
}

const handleImageError = (event) => {
  event.target.src = 'https://i.pravatar.cc/150?img=1'
}

const isOnline = (lastSeenAt) => {
  if (!lastSeenAt) return false
  const lastSeen = new Date(lastSeenAt)
  const now = new Date()
  const diffMinutes = (now - lastSeen) / (1000 * 60)
  return diffMinutes < 5 
}

const loadConversations = async () => {
  try {
    const res = await api.get('/conversations')
    conversations.value = res.data.map(conv => ({
      ...conv,
      photo: getAvatarUrl(conv.email || conv.name),
    }))
  } catch (error) {
    console.error('Failed to load conversations:', error)
    conversations.value = []
  }
}

const loadFriends = async () => {
  try {
    const res = await api.get('/conversations/friends')
    const conversationUserIds = new Set(conversations.value.map(c => c.user_id))
    friends.value = res.data
      .filter(friend => !conversationUserIds.has(friend.id))
      .map(friend => ({
        ...friend,
        photo: getAvatarUrl(friend.email || friend.name),
      }))
  } catch (error) {
    console.error('Failed to load friends:', error)
    friends.value = []
  }
}

const loadData = async () => {
  loading.value = true
  try {
    await Promise.all([loadConversations(), loadFriends()])
  } finally {
    loading.value = false
  }
}

const openConversation = async (conversationId, userId) => {
  try {
    if (conversationId) {
      const res = await api.get(`/conversations/${conversationId}/messages`)
      const conversation = conversations.value.find(c => c.id === conversationId)
      emit('open-chat', {
        conversationId,
        userId,
        userName: conversation?.name || 'Friend',
        userEmail: conversation?.email || null,
        messages: res.data,
      })
    } else {
      const res = await api.get(`/conversations/user/${userId}`)
      emit('open-chat', {
        conversationId: res.data.conversation.id,
        userId: res.data.conversation.user_id,
        userName: res.data.conversation.name,
        userEmail: res.data.conversation.email,
        messages: res.data.messages,
      })
      await loadData()
    }
  } catch (error) {
    console.error('Failed to open conversation:', error)
  }
}

const openConversationWithFriend = async (friend) => {
  try {
    const res = await api.get(`/conversations/user/${friend.id}`)
    emit('open-chat', {
      conversationId: res.data.conversation.id,
      userId: res.data.conversation.user_id,
      userName: friend.name,
      userEmail: friend.email,
      messages: res.data.messages,
    })
    await loadData()
  } catch (error) {
    console.error('Failed to open conversation with friend:', error)
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    loadData()
  }
})

onMounted(() => {
  if (props.isOpen) {
    loadData()
  }
})
</script>

<style scoped>
aside.translate-x-full {
  transform: translateX(100%);
}

aside.translate-x-0 {
  transform: translateX(0);
}
</style>

