<template>
  <!-- Minimized State -->
  <div
    v-if="isOpen && chatData && isMinimized"
    :class="[
      'fixed bottom-0 glass backdrop-blur-xl border-l border-t border-white/20 flex items-center justify-between p-3 sm:p-4 transition-all duration-300 z-40 shadow-2xl cursor-pointer hover:bg-white/80',
      'w-full md:w-96',
      sidebarOpen ? 'md:right-96' : 'md:right-25',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
    @click="toggleMinimize"
  >
    <div class="flex items-center gap-3 flex-1 min-w-0">
      <div class="relative">
        <Avatar
          :user="chatUser"
          size="sm"
        />
        <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-sm text-gray-900 truncate">{{ chatData.userName }}</h3>
        <p class="text-xs text-gray-500 truncate">
          {{ messages.length > 0 ? messages[messages.length - 1].message : 'No messages' }}
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button
        @click.stop="toggleMinimize"
        class="text-gray-500 hover:text-indigo-600 p-2 hover:bg-indigo-50 rounded-xl transition-all duration-200 flex-shrink-0"
        title="Restore"
      >
        <i class="fa-solid fa-window-maximize text-sm"></i>
      </button>
      <button
        @click.stop="close"
        class="text-gray-500 hover:text-red-600 p-2 hover:bg-red-50 rounded-xl transition-all duration-200 flex-shrink-0"
        title="Close"
      >
        <i class="fa-solid fa-times text-sm"></i>
      </button>
    </div>
  </div>

  <!-- Mobile Overlay -->
  <div
    v-if="isOpen && chatData && !isMinimized"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
    @click="close"
  ></div>

  <!-- Full Chat Window -->
  <aside
    v-if="isOpen && chatData && !isMinimized"
    :class="[
      'fixed bottom-0 right-0',
      'w-full md:w-96',
      'h-screen md:h-100',
      'glass backdrop-blur-xl border-l border-white/20 flex flex-col transition-all duration-300 overflow-hidden z-[60] md:z-40 shadow-2xl',
      sidebarOpen ? 'md:right-80' : 'md:right-25',
      isOpen ? 'translate-x-0 translate-y-0' : 'translate-x-full md:translate-y-0 translate-y-full'
    ]"
  >
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-white/20 flex-shrink-0 bg-white/50">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="relative">
          <Avatar
            :user="chatUser"
            size="md"
          />
          <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-base text-gray-900 truncate">{{ chatData.userName }}</h3>
          <p class="text-xs text-gray-500 flex items-center gap-1">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Active now
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="toggleMinimize"
          class="hidden md:flex text-gray-500 hover:text-indigo-600 p-2 hover:bg-indigo-50 rounded-xl transition-all duration-200 flex-shrink-0"
          title="Minimize"
        >
          <i class="fa-solid fa-window-minimize text-sm"></i>
        </button>
        <button
          @click="close"
          class="text-gray-500 hover:text-red-600 p-2 hover:bg-red-50 rounded-xl transition-all duration-200 flex-shrink-0"
          title="Close"
        >
          <i class="fa-solid fa-times text-sm"></i>
        </button>
      </div>
    </div>

    <!-- Messages Area -->
    <div
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-4 pb-2 bg-gradient-to-b from-gray-50 to-white"
    >
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
            <i class="fa-solid fa-comments text-2xl text-indigo-500"></i>
          </div>
          <p class="text-gray-500 text-sm font-medium">No messages yet</p>
          <p class="text-gray-400 text-xs mt-1">Start the conversation!</p>
        </div>
      </div>
      <MessageBubble
        v-for="message in messages"
        :key="message.id"
        :message="message"
      />
      
      <!-- Typing Indicator -->
      <TypingIndicator
        :is-typing="isOtherUserTyping"
        :user-name="typingUserName"
      />
    </div>

    <!-- Input Area -->
    <MessageInput
      :conversation-id="chatData.conversationId"
      @message-sent="handleMessageSent"
      @typing="handleTyping"
    />
  </aside>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue'
import MessageBubble from './MessageBubble.vue'
import MessageInput from './MessageInput.vue'
import TypingIndicator from './TypingIndicator.vue'
import Avatar from '../common/Avatar.vue'
import echo from '../../services/echo'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  chatData: {
    type: Object,
    default: null,
  },
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const messages = ref([])
const messagesContainer = ref(null)
const isMinimized = ref(false)
let currentChannel = null
const isOtherUserTyping = ref(false)
const typingUserName = ref('')
let typingTimeout = null

const toggleMinimize = () => {
  isMinimized.value = !isMinimized.value
}

const chatUser = computed(() => {
  if (!props.chatData) return null
  return {
    name: props.chatData.userName,
    email: props.chatData.userEmail,
    profile_image: props.chatData.profile_image,
  }
})

const close = () => {
  emit('close')
}

const handleMessageSent = (newMessage) => {
  const exists = messages.value.some(msg => msg.id === newMessage.id)
  if (!exists) {
    messages.value.push(newMessage)
    scrollToBottom()
  }
}

const markMessagesAsSeen = async (conversationId) => {
  if (!conversationId) return
  
  try {
    await api.post(`/conversations/${conversationId}/mark-as-seen`)
    messages.value.forEach(msg => {
      if (msg.sender_id !== auth.user?.id) {
        msg.seen = true
      }
    })
    window.dispatchEvent(new CustomEvent('conversation-seen', { detail: { conversationId } }))
  } catch (error) {
    console.error('Failed to mark messages as seen:', error)
  }
}

const handleTyping = (isTyping) => {}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const setupChannelListener = (conversationId) => {
  cleanupChannel()

  if (!conversationId) return

  // console.log('Setting up listener for conversation:', conversationId)

  const channel = echo.private(`conversation.${conversationId}`)
  currentChannel = channel
  
  // channel.subscribed(() => {
  //   console.log('Successfully subscribed to conversation channel:', conversationId)
  // })

  // channel.error((error) => {
  //   console.error('Channel subscription error:', error)
  // })
  
  channel.listen('.message.sent', (data) => {
    if (props.chatData?.conversationId === conversationId) {
      const exists = messages.value.some(msg => msg.id === data.id)
      if (!exists) {
        messages.value.push(data)
        scrollToBottom()
        if (props.isOpen && !isMinimized.value && data.sender_id !== auth.user?.id) {
          markMessagesAsSeen(conversationId)
        }
      }
      isOtherUserTyping.value = false
    }
  })
  
  channel.listen('.user.typing', (data) => {
    if (props.chatData?.conversationId === conversationId && data.user_id !== auth.user?.id) {
      if (data.is_typing) {
        typingUserName.value = data.user_name
        isOtherUserTyping.value = true
        scrollToBottom()
        if (typingTimeout) {
          clearTimeout(typingTimeout)
        }
        typingTimeout = setTimeout(() => {
          isOtherUserTyping.value = false
        }, 3000)
      } else {
        isOtherUserTyping.value = false
        if (typingTimeout) {
          clearTimeout(typingTimeout)
        }
      }
    }
  })
}

const cleanupChannel = () => {
  if (currentChannel) {
    try {
      currentChannel.stopListening('.message.sent')
      currentChannel.stopListening('.user.typing')
    } catch (e) {
      if (currentChannel.unsubscribe) {
        currentChannel.unsubscribe()
      }
    }
    currentChannel = null
  }
  isOtherUserTyping.value = false
  if (typingTimeout) {
    clearTimeout(typingTimeout)
    typingTimeout = null
  }
}

watch(
  () => props.chatData,
  (newData) => {
    if (newData && newData.messages) {
      messages.value = newData.messages
      scrollToBottom()
      isMinimized.value = false
      
      if (newData.conversationId) {
        setupChannelListener(newData.conversationId)
        markMessagesAsSeen(newData.conversationId)
      }
    } else {
      cleanupChannel()
    }
  },
  { immediate: true }
)

watch(
  () => props.isOpen,
  (newVal) => {
    if (!newVal) {
      isMinimized.value = false
      cleanupChannel()
    } else if (newVal && props.chatData?.conversationId) {
      setupChannelListener(props.chatData.conversationId)
      markMessagesAsSeen(props.chatData.conversationId)
    }
  }
)

watch(
  () => messages.value.length,
  () => {
    scrollToBottom()
  }
)

onUnmounted(() => {
  cleanupChannel()
})
</script>

