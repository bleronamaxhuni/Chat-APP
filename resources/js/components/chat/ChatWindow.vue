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
      <img
        :src="getAvatarUrl(chatData.userEmail || chatData.userName)"
        :alt="chatData.userName"
        class="w-8 h-8 rounded-full object-cover flex-shrink-0"
        @error="handleImageError"
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
        <img
          :src="getAvatarUrl(chatData.userEmail || chatData.userName)"
          :alt="chatData.userName"
          class="w-10 h-10 rounded-full object-cover flex-shrink-0"
          @error="handleImageError"
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
      class="flex-1 overflow-y-auto p-4 bg-gray-50"
    >
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500 text-sm">
        <p class="text-center">No messages yet.<br />Start the conversation!</p>
      </div>
      <MessageBubble
        v-for="message in messages"
        :key="message.id"
        :message="message"
      />
    </div>

    <!-- Input Area -->
    <MessageInput
      :conversation-id="chatData.conversationId"
      @message-sent="handleMessageSent"
    />
  </aside>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import MessageBubble from './MessageBubble.vue'
import MessageInput from './MessageInput.vue'

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

const toggleMinimize = () => {
  isMinimized.value = !isMinimized.value
}

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

const close = () => {
  emit('close')
}

const handleMessageSent = (newMessage) => {
  messages.value.push(newMessage)
  scrollToBottom()
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Watch for chatData changes and update messages
watch(
  () => props.chatData,
  (newData) => {
    if (newData && newData.messages) {
      messages.value = newData.messages
      scrollToBottom()
      // Reset minimized state when new chat opens
      isMinimized.value = false
    }
  },
  { immediate: true }
)

// Reset minimized state when chat closes
watch(
  () => props.isOpen,
  (newVal) => {
    if (!newVal) {
      isMinimized.value = false
    }
  }
)

// Scroll to bottom when messages change
watch(
  () => messages.value.length,
  () => {
    scrollToBottom()
  }
)
</script>

