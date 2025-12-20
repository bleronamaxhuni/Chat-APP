<template>
  <div>
    <PostForm
      :loading="creating"
      @submit="handleCreatePost"
    />

    <div v-if="loading" class="text-center py-8">
      <p class="text-gray-500">Loading posts...</p>
    </div>

    <div v-else-if="posts.length === 0" class="text-center py-8">
      <p class="text-gray-500">No posts yet. Be the first to post!</p>
    </div>

    <PostCard
      v-for="post in posts"
      :key="post.id"
      :post="post"
      @update="handleUpdatePost"
      @delete="handleDeletePost"
      @like="handleLikePost"
      @comment-added="handleCommentAdded"
      @comment-updated="handleCommentUpdated"
      @comment-deleted="handleCommentDeleted"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PostForm from './PostForm.vue'
import PostCard from './PostCard.vue'
import api from '../../services/api'

const auth = useAuthStore()

const posts = ref([])
const loading = ref(false)
const creating = ref(false)

const loadPosts = async () => {
  loading.value = true
  try {
    const res = await api.get('/posts')
    posts.value = res.data
  } catch (e) {
    console.error('Failed to load posts', e)
  } finally {
    loading.value = false
  }
}

const handleCreatePost = async (content) => {
  creating.value = true
  try {
    const res = await api.post('/posts', { content })
    posts.value.unshift(res.data)
  } catch (e) {
    console.error('Failed to create post', e)
    alert('Failed to create post. Please try again.')
  } finally {
    creating.value = false
  }
}

const handleUpdatePost = async (post, content) => {
  try {
    const res = await api.put(`/posts/${post.id}`, { content })
    const index = posts.value.findIndex(p => p.id === post.id)
    if (index !== -1) {
      posts.value[index] = res.data
    }
  } catch (e) {
    console.error('Failed to update post', e)
    alert('Failed to update post. Please try again.')
    throw e
  }
}

const handleDeletePost = async (post) => {
  try {
    await api.delete(`/posts/${post.id}`)
    posts.value = posts.value.filter(p => p.id !== post.id)
  } catch (e) {
    console.error('Failed to delete post', e)
    alert('Failed to delete post. Please try again.')
  }
}

const handleLikePost = (post, likeData) => {
  const index = posts.value.findIndex(p => p.id === post.id)
  if (index !== -1) {
    posts.value[index].is_liked = likeData.is_liked
    posts.value[index].likes_count = likeData.likes_count
  }
}

const handleCommentAdded = (post, comment) => {
  const index = posts.value.findIndex(p => p.id === post.id)
  if (index !== -1) {
    posts.value[index].comments.push(comment)
  }
}

const handleCommentUpdated = (post, comment) => {
  const index = posts.value.findIndex(p => p.id === post.id)
  if (index !== -1) {
    const commentIndex = posts.value[index].comments.findIndex(c => c.id === comment.id)
    if (commentIndex !== -1) {
      posts.value[index].comments[commentIndex] = comment
    }
  }
}

const handleCommentDeleted = (post, comment) => {
  const index = posts.value.findIndex(p => p.id === post.id)
  if (index !== -1) {
    posts.value[index].comments = posts.value[index].comments.filter(c => c.id !== comment.id)
  }
}

onMounted(() => {
  loadPosts()
  
  // Double-check before calling loadPosts
  if (auth.isAuthenticated) {
    loadPosts()
  }
})

defineExpose({
  loadPosts,
})
</script>

