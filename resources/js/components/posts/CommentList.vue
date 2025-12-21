<template>
  <div class="mt-2 sm:mt-3 space-y-1.5 sm:space-y-2">
    <div
      v-for="comment in comments"
      :key="comment.id"
      class="flex items-start gap-2 p-2 bg-gray-50 rounded-lg"
    >
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 sm:gap-2 mb-1 flex-wrap">
          <span class="font-semibold text-xs sm:text-sm truncate">{{ comment.user.name }}</span>
          <span class="text-xs text-gray-500 flex-shrink-0">{{ formatDate(comment.created_at) }}</span>
        </div>
        <p v-if="editingCommentId !== comment.id" class="text-xs sm:text-sm text-gray-700 break-words">
          {{ comment.content }}
        </p>
        <CommentForm
          v-else
          :comment="comment"
          :loading="updatingCommentId === comment.id"
          @submit="handleUpdateComment(comment, $event)"
          @cancel="editingCommentId = null"
        />
      </div>
      <div v-if="canEdit(comment)" class="flex gap-1 flex-shrink-0">
        <button
          @click="editingCommentId = comment.id"
          class="text-xs text-blue-500 hover:text-blue-700 transition-colors"
        >
          Edit
        </button>
        <button
          @click="handleDeleteComment(comment)"
          class="text-xs text-red-500 hover:text-red-700 transition-colors"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CommentForm from './CommentForm.vue'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  comments: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update', 'delete'])

const auth = useAuthStore()
const editingCommentId = ref(null)
const updatingCommentId = ref(null)

const canEdit = (comment) => {
  return auth.user && auth.user.id === comment.user.id
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  return date.toLocaleDateString()
}

const handleUpdateComment = async (comment, content) => {
  updatingCommentId.value = comment.id
  try {
    await emit('update', comment, content)
    editingCommentId.value = null
  } finally {
    updatingCommentId.value = null
  }
}

const handleDeleteComment = (comment) => {
  if (confirm('Are you sure you want to delete this comment?')) {
    emit('delete', comment)
  }
}
</script>

