<template>
  <div class="border-t border-white/20 bg-white/50 backdrop-blur-sm p-4 flex-shrink-0">
    <form @submit.prevent="sendMessage" class="flex gap-3">
      <input
        v-model="messageText"
        type="text"
        placeholder="Type a message..."
        class="flex-1 px-4 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white/80"
        :disabled="sending"
        @input="handleInput"
      />
      <button
        type="submit"
        :disabled="!messageText.trim() || sending"
        class="px-6 py-3 text-sm bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-xl hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 font-medium"
      >
        <span v-if="!sending">
          <i class="fa-solid fa-paper-plane mr-2"></i>Send
        </span>
        <span v-else class="flex items-center gap-2">
          <i class="fa-solid fa-spinner fa-spin"></i>Sending...
        </span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  conversationId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['message-sent', 'typing'])

const messageText = ref('')
const sending = ref(false)
let typingTimeout = null
let lastTypingState = false

const emitTyping = async (isTyping) => {
  if (lastTypingState === isTyping) return
  lastTypingState = isTyping

  try {
    const api = (await import('../../services/api')).default
    await api.post(`/conversations/${props.conversationId}/typing`, {
      is_typing: isTyping,
    })
    emit('typing', isTyping)
  } catch (error) {
    console.error('Failed to emit typing:', error)
  }
}

const handleInput = () => {
  if (typingTimeout) {
    clearTimeout(typingTimeout)
  }

  if (messageText.value.trim().length > 0) {
    emitTyping(true)

    typingTimeout = setTimeout(() => {
      emitTyping(false)
    }, 1000)
  } else {
    emitTyping(false)
  }
}

const sendMessage = async () => {
  if (!messageText.value.trim() || sending.value) return

  if (typingTimeout) {
    clearTimeout(typingTimeout)
  }
  emitTyping(false)

  const text = messageText.value.trim()
  messageText.value = ''
  sending.value = true

  try {
    const api = (await import('../../services/api')).default
    const res = await api.post(`/conversations/${props.conversationId}/messages`, {
      message: text,
    })
    emit('message-sent', res.data)
  } catch (error) {
    console.error('Failed to send message:', error)
    messageText.value = text
  } finally {
    sending.value = false
  }
}

watch(messageText, handleInput)

onUnmounted(() => {
  if (typingTimeout) {
    clearTimeout(typingTimeout)
  }
  emitTyping(false)
})
</script>

