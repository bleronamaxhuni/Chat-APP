<template>
  <div class="border-t bg-white p-3 flex-shrink-0">
    <form @submit.prevent="sendMessage" class="flex gap-2">
      <input
        v-model="messageText"
        type="text"
        placeholder="Type a message..."
        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        :disabled="sending"
      />
      <button
        type="submit"
        :disabled="!messageText.trim() || sending"
        class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <span v-if="!sending">Send</span>
        <span v-else>Sending...</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  conversationId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['message-sent'])

const messageText = ref('')
const sending = ref(false)

const sendMessage = async () => {
  if (!messageText.value.trim() || sending.value) return

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
    messageText.value = text // Restore message on error
  } finally {
    sending.value = false
  }
}
</script>

