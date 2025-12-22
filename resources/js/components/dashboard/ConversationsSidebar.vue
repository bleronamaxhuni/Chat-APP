<template>
  <!-- Mobile Overlay -->
  <div
    v-if="isOpen"
    class="fixed inset-0 z-40 md:hidden bg-black/50 backdrop-blur-sm transition-opacity"
    @click="$emit('close')"
  ></div>
  
  <aside
    :class="[
      'fixed md:fixed top-0 md:top-[64px] right-0 h-screen md:h-[calc(100vh-64px)]',
      'w-full sm:w-60 md:w-60 lg:w-80',
      'glass backdrop-blur-xl border-l border-white/20 p-3 sm:p-4',
      'transition-transform duration-300 flex flex-col overflow-hidden z-50 md:z-30 shadow-2xl',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
  >
    <div class="flex items-center justify-between mb-4 flex-shrink-0">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
          <i class="fa-solid fa-comments text-white text-sm"></i>
        </div>
        <h3 class="font-bold text-base sm:text-lg bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Conversations
        </h3>
      </div>
      <button
        @click="$emit('close')"
        class="md:hidden p-2 hover:bg-white/50 rounded-xl transition-all duration-200 hover:scale-110"
        aria-label="Close conversations"
      >
        <i class="fa-solid fa-times text-gray-600"></i>
      </button>
    </div>
    
    <div class="flex-1 overflow-y-auto">
      <div v-if="loading" class="flex justify-center items-center py-8">
        <div class="flex flex-col items-center gap-3">
          <i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500"></i>
          <div class="text-gray-500 text-sm">Loading...</div>
        </div>
      </div>

      <div v-if="!loading && conversations.length > 0" class="mb-6">
        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3 px-2">Recent Conversations</h4>
        <ul class="space-y-2">
          <li
            v-for="conv in conversations"
            :key="conv.id"
            @click="openConversation(conv.id, conv.user_id)"
            class="flex items-center p-3 hover:bg-white/50 rounded-xl cursor-pointer transition-all duration-200 hover:scale-[1.02] group"
          >
            <div class="relative flex-shrink-0 mr-3">
              <Avatar
                :user="conv"
                size="md"
              />
              <span 
                v-if="isOnline(conv.last_seen_at)"
                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
              ></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-sm truncate text-gray-900 group-hover:text-indigo-600 transition-colors">{{ conv.name }}</p>
                <span 
                  v-if="conv.unreadCount > 0" 
                  class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs rounded-full px-2 py-0.5 ml-2 flex-shrink-0 font-semibold shadow-sm"
                >
                  {{ conv.unreadCount }}
                </span>
              </div>
              <p class="text-xs text-gray-500 truncate mt-0.5">{{ conv.lastMessage || 'No messages yet' }}</p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="!loading && friends.length > 0">
        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3 px-2">
          {{ conversations.length > 0 ? 'Friends' : 'Your Friends' }}
        </h4>
        <ul class="space-y-2">
          <li
            v-for="friend in friends"
            :key="friend.id"
            @click="openConversationWithFriend(friend)"
            class="flex items-center p-3 hover:bg-white/50 rounded-xl cursor-pointer transition-all duration-200 hover:scale-[1.02] group"
          >
            <div class="relative flex-shrink-0 mr-3">
              <Avatar
                :user="friend"
                size="md"
              />
              <span 
                v-if="isOnline(friend.last_seen_at)"
                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full animate-pulse"
              ></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm truncate text-gray-900 group-hover:text-indigo-600 transition-colors">{{ friend.name }}</p>
              <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                <span 
                  :class="[
                    'w-1.5 h-1.5 rounded-full',
                    isOnline(friend.last_seen_at) ? 'bg-green-500' : 'bg-gray-400'
                  ]"
                ></span>
                {{ isOnline(friend.last_seen_at) ? 'Online' : 'Offline' }}
              </p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="!loading && conversations.length === 0 && friends.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center mb-4">
          <i class="fa-solid fa-comments text-2xl text-indigo-500"></i>
        </div>
        <p class="text-sm font-medium">No conversations yet</p>
        <p class="text-xs mt-1 text-gray-400">Start chatting with your friends!</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import api from '../../services/api'
import Avatar from '../common/Avatar.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['open-chat', 'close'])

const conversations = ref([])
const friends = ref([])
const loading = ref(false)

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
    conversations.value = res.data
  } catch (error) {
    console.error('Failed to load conversations:', error)
    conversations.value = []
  }
}

const loadFriends = async () => {
  try {
    const res = await api.get('/conversations/friends')
    const conversationUserIds = new Set(conversations.value.map(c => c.user_id))
    friends.value = res.data.filter(friend => !conversationUserIds.has(friend.id))
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
        profile_image: conversation?.profile_image || null,
        messages: res.data,
      })
    } else {
      const res = await api.get(`/conversations/user/${userId}`)
      emit('open-chat', {
        conversationId: res.data.conversation.id,
        userId: res.data.conversation.user_id,
        userName: res.data.conversation.name,
        userEmail: res.data.conversation.email,
        profile_image: res.data.conversation.profile_image || null,
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
      profile_image: friend.profile_image || null,
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

const handleConversationSeen = () => {
  loadConversations()
}

onMounted(() => {
  if (props.isOpen) {
    loadData()
  }
  window.addEventListener('conversation-seen', handleConversationSeen)
})

onUnmounted(() => {
  window.removeEventListener('conversation-seen', handleConversationSeen)
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

