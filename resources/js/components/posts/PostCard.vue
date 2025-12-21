<template>
  <div class="bg-white p-3 sm:p-4 rounded-lg shadow mb-3 sm:mb-4">
    <!-- Post Header -->
    <div class="flex items-center justify-between mb-2 sm:mb-3">
      <div class="flex items-center gap-2 flex-1 min-w-0">
        <Avatar :user="post.user" size="md" />
        <div class="min-w-0 flex-1">
          <h4 class="font-semibold text-sm sm:text-base truncate">{{ post.user.name }}</h4>
          <p class="text-xs text-gray-500">{{ formatDate(post.created_at) }}</p>
        </div>
      </div>
      <div v-if="canEdit(post)" class="relative" ref="menuRef">
        <button
          @click.stop="showMenu = !showMenu"
          class="text-gray-500 hover:text-gray-700"
        >
          ⋮
        </button>
        <div
          v-if="showMenu"
          class="absolute right-0 mt-1 w-32 bg-white border rounded-lg shadow-lg z-10"
        >
          <button
            @click="handleEdit"
            class="block w-full text-left px-4 py-2 hover:bg-gray-100 text-sm"
          >
            Edit
          </button>
          <button
            @click="handleDelete"
            class="block w-full text-left px-4 py-2 hover:bg-gray-100 text-sm text-red-600"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Post Content -->
    <div v-if="!isEditing" class="mb-2 sm:mb-3">
      <p class="text-gray-800 whitespace-pre-wrap text-sm sm:text-base break-words">{{ post.content }}</p>
    </div>
    <PostForm
      v-else
      :post="post"
      :loading="updating"
      @submit="handleUpdate"
      @cancel="isEditing = false"
    />

    <!-- Post Actions -->
    <div v-if="!isEditing" class="flex items-center gap-2 sm:gap-4 pt-2 sm:pt-3 border-t">
      <button
        @click="handleLike"
        :class="[
          'flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1 rounded-lg transition-colors text-sm sm:text-base',
          post.is_liked
            ? 'bg-red-50 text-red-600 hover:bg-red-100'
            : 'text-gray-600 hover:bg-gray-100'
        ]"
      >
        <span>{{ post.is_liked ? '❤️' : '🤍' }}</span>
        <span>{{ post.likes_count }}</span>
      </button>
      <button
        @click="showComments = !showComments"
        class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1 rounded-lg text-gray-600 hover:bg-gray-100 text-sm sm:text-base"
      >
        💬 {{ post.comments.length }}
      </button>
    </div>

    <!-- Comments Section -->
    <div v-if="showComments" class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t">
      <CommentList
        :comments="post.comments"
        @update="handleCommentUpdate"
        @delete="handleCommentDelete"
      />
      <CommentForm
        :loading="addingComment"
        @submit="handleCommentSubmit"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import PostForm from './PostForm.vue'
import CommentList from './CommentList.vue'
import CommentForm from './CommentForm.vue'
import Avatar from '../common/Avatar.vue'
import { useAuthStore } from '../../stores/auth'
import { useClickOutside } from '../../composables/useClickOutside'
import api from '../../services/api'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update', 'delete', 'like', 'comment-added', 'comment-updated', 'comment-deleted'])

const auth = useAuthStore()
const showMenu = ref(false)
const isEditing = ref(false)
const showComments = ref(false)
const updating = ref(false)
const addingComment = ref(false)

const menuRef = useClickOutside(() => {
  showMenu.value = false
})

const canEdit = (post) => {
  return auth.user && auth.user.id === post.user.id
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

const handleEdit = () => {
  isEditing.value = true
  showMenu.value = false
}

const handleDelete = () => {
  if (confirm('Are you sure you want to delete this post?')) {
    emit('delete', props.post)
  }
  showMenu.value = false
}

const handleUpdate = async (content) => {
  updating.value = true
  try {
    await emit('update', props.post, content)
    isEditing.value = false
  } finally {
    updating.value = false
  }
}

const handleLike = async () => {
  try {
    const res = await api.post(`/posts/${props.post.id}/likes`)
    emit('like', props.post, res.data)
  } catch (e) {
    console.error('Failed to like post', e)
  }
}

const handleCommentSubmit = async (content) => {
  addingComment.value = true
  try {
    const res = await api.post(`/posts/${props.post.id}/comments`, { content })
    emit('comment-added', props.post, res.data)
  } catch (e) {
    console.error('Failed to add comment', e)
  } finally {
    addingComment.value = false
  }
}

const handleCommentUpdate = async (comment, content) => {
  try {
    const res = await api.put(`/comments/${comment.id}`, { content })
    emit('comment-updated', props.post, res.data)
  } catch (e) {
    console.error('Failed to update comment', e)
    throw e
  }
}

const handleCommentDelete = async (comment) => {
  try {
    await api.delete(`/comments/${comment.id}`)
    emit('comment-deleted', props.post, comment)
  } catch (e) {
    console.error('Failed to delete comment', e)
  }
}
</script>

