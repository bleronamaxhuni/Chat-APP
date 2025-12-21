<template>
  <div class="bg-white p-3 sm:p-4 rounded-lg shadow mb-3 sm:mb-4">
    <form @submit.prevent="handleSubmit" v-if="!isEditing || post">
      <textarea
        v-model="content"
        placeholder="What's on your mind?"
        class="w-full p-2 sm:p-3 border rounded-lg mb-2 sm:mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base resize-none"
        rows="3"
        :maxlength="5000"
      ></textarea>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0">
        <span class="text-xs sm:text-sm text-gray-500">{{ content.length }}/5000</span>
        <div class="flex space-x-2 w-full sm:w-auto">
          <button
            v-if="isEditing"
            @click="$emit('cancel')"
            type="button"
            class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 text-sm sm:text-base text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!content.trim() || loading"
            class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 text-sm sm:text-base bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ loading ? 'Posting...' : isEditing ? 'Update' : 'Post' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  post: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit', 'cancel'])

const content = ref('')
const isEditing = computed(() => !!props.post)

watch(() => props.post, (newPost) => {
  if (newPost) {
    content.value = newPost.content
  } else {
    content.value = ''
  }
}, { immediate: true })

const handleSubmit = () => {
  if (content.value.trim()) {
    emit('submit', content.value.trim())
  }
}
</script>

