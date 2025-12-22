<template>
  <div class="card-modern p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
    <!-- Post Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="relative">
          <Avatar :user="post.user" size="md" />
          <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
        </div>
        <div class="min-w-0 flex-1">
          <h4 class="font-semibold text-base sm:text-lg text-gray-900 truncate">{{ post.user.name }}</h4>
          <p class="text-xs sm:text-sm text-gray-500">{{ formatDate(post.created_at) }}</p>
        </div>
      </div>
      <div v-if="canEdit(post)" class="relative" ref="menuRef">
        <button
          @click.stop="showMenu = !showMenu"
          class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200"
        >
          <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
        <div
          v-if="showMenu"
          class="absolute right-0 mt-2 w-36 bg-white border border-gray-200 rounded-xl shadow-xl z-10 overflow-hidden"
        >
          <button
            @click="handleEdit"
            class="block w-full text-left px-4 py-2.5 hover:bg-indigo-50 text-sm text-gray-700 hover:text-indigo-600 transition-colors"
          >
            <i class="fa-solid fa-pen mr-2"></i>Edit
          </button>
          <button
            @click="handleDelete"
            class="block w-full text-left px-4 py-2.5 hover:bg-red-50 text-sm text-red-600 transition-colors"
          >
            <i class="fa-solid fa-trash mr-2"></i>Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Post Content -->
    <div v-if="!isEditing" class="mb-4">
      <p class="text-gray-800 whitespace-pre-wrap text-sm sm:text-base break-words leading-relaxed">{{ post.content }}</p>
    </div>
    <PostForm
      v-else
      :post="post"
      :loading="updating"
      @submit="handleUpdate"
      @cancel="isEditing = false"
    />

    <!-- Post Actions -->
    <div v-if="!isEditing" class="flex items-center gap-4 pt-4 border-t border-gray-100">
      <button
        @click="handleLike"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-xl transition-all duration-200 text-sm sm:text-base font-medium',
          post.is_liked
            ? 'bg-gradient-to-r from-red-50 to-pink-50 text-red-600 hover:from-red-100 hover:to-pink-100 shadow-sm'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
        ]"
      >
        <i :class="post.is_liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
        <span>{{ post.likes_count }}</span>
      </button>
      <button
        @click="showComments = !showComments"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-xl transition-all duration-200 text-sm sm:text-base font-medium',
          showComments
            ? 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
        ]"
      >
        <i class="fa-regular fa-comment"></i>
        <span>{{ post.comments.length }}</span>
      </button>
    </div>

    <!-- Comments Section -->
    <div v-if="showComments" class="mt-4 pt-4 border-t border-gray-100 space-y-3">
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

