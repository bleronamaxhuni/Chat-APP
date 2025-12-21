<template>
  <!-- Minimized State -->
  <div
    v-if="isOpen && chatData && isMinimized"
    :class="[
      'fixed bottom-0 bg-white border-l border-t flex items-center justify-between p-3 transition-all duration-300 z-40 shadow-lg cursor-pointer hover:bg-gray-50',
      sidebarOpen ? 'right-80 w-96' : 'right-25 w-96',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
    @click="toggleMinimize"
  >
    <div class="flex items-center gap-3 flex-1 min-w-0">
      <Avatar
        :user="chatUser"
        size="sm"
      />
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-sm truncate">{{ chatData.userName }}</h3>
        <p class="text-xs text-gray-500 truncate">
          {{ messages.length > 0 ? messages[messages.length - 1].message : 'No messages' }}
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button
        @click.stop="toggleMinimize"
        class="text-gray-500 hover:text-gray-700 p-1.5 hover:bg-gray-100 rounded transition-colors flex-shrink-0"
        title="Restore"
      >
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
          />
        </svg>
      </button>
      <button
        @click.stop="close"
        class="text-gray-500 hover:text-gray-700 p-1.5 hover:bg-gray-100 rounded transition-colors flex-shrink-0"
        title="Close"
      >
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </div>

  <!-- Full Chat Window -->
  <aside
    v-if="isOpen && chatData && !isMinimized"
    :class="[
      'fixed bottom-0 w-96 h-100 bg-white border-l flex flex-col transition-all duration-300 overflow-hidden z-40 shadow-lg',
      sidebarOpen ? 'right-80' : 'right-25',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
  >
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b flex-shrink-0">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <Avatar
          :user="chatUser"
          size="md"
        />
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-sm truncate">{{ chatData.userName }}</h3>
          <p class="text-xs text-gray-500">Active now</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="toggleMinimize"
          class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
          title="Minimize"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M20 12H4"
            />
          </svg>
        </button>
        <button
          @click="close"
          class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
          title="Close"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Messages Area -->
    <div
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-4 pb-2 bg-gray-50"
    >
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500 text-sm">
        <p class="text-center">No messages yet.<br />Start the conversation!</p>
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

