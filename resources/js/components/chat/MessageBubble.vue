<template>
  <div
    :class="[
      'mb-4 flex animate-fade-in',
      isOwnMessage ? 'justify-end' : 'justify-start'
    ]"
  >
    <div
      :class="[
        'max-w-[75%] px-4 py-3 rounded-2xl text-sm shadow-sm',
        isOwnMessage
          ? 'bg-gradient-to-br from-indigo-500 to-purple-500 text-white rounded-br-md'
          : 'bg-white text-gray-800 border border-gray-200 rounded-bl-md'
      ]"
    >
      <p class="text-sm leading-relaxed">{{ message.message }}</p>
      <div
        :class="[
          'flex items-center gap-1.5 mt-2',
        ]"
      >
        <p
          :class="[
            'text-xs',
            isOwnMessage ? 'text-indigo-100' : 'text-gray-500'
          ]"
        >
          {{ formatTime(message.created_at) }}
        </p>
        <span
          v-if="isOwnMessage && message.seen"
          :class="[
            'text-xs flex items-center gap-1',
            'text-indigo-100'
          ]"
        >
          <i class="fa-solid fa-check-double text-xs"></i>Seen
        </span>
      </div>
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

