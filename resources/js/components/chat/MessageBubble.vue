<template>
  <div
    :class="[
      'mb-3 flex',
      isOwnMessage ? 'justify-end' : 'justify-start'
    ]"
  >
    <div
      :class="[
        'max-w-[75%] px-3 py-2 rounded-lg text-sm',
        isOwnMessage
          ? 'bg-blue-500 text-white'
          : 'bg-gray-200 text-gray-800'
      ]"
    >
      <p class="text-sm">{{ message.message }}</p>
      <p
        :class="[
          'text-xs mt-1',
          isOwnMessage ? 'text-blue-100' : 'text-gray-500'
        ]"
      >
        {{ formatTime(message.created_at) }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
})

const auth = useAuthStore()

const isOwnMessage = computed(() => {
  return props.message.sender_id === auth.user?.id
})

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`

  return date.toLocaleDateString()
}
</script>

