<template>
  <div class="mt-2">
    <form @submit.prevent="handleSubmit" v-if="!isEditing || comment">
      <div class="flex flex-col sm:flex-row gap-2">
        <input
          v-model="content"
          type="text"
          placeholder="Write a comment..."
          class="flex-1 px-2 sm:px-3 py-1.5 sm:py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base"
          :maxlength="1000"
        />
        <div class="flex gap-2 sm:gap-0">
          <button
            v-if="isEditing"
            @click="$emit('cancel')"
            type="button"
            class="flex-1 sm:flex-initial px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-600 hover:text-gray-800 bg-gray-100 sm:bg-transparent rounded-lg sm:rounded-none transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!content.trim() || loading"
            class="flex-1 sm:flex-initial px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ loading ? '...' : isEditing ? 'Update' : 'Comment' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  comment: {
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
const isEditing = computed(() => !!props.comment)

watch(() => props.comment, (newComment) => {
  if (newComment) {
    content.value = newComment.content
  } else {
    content.value = ''
  }
}, { immediate: true })

const handleSubmit = () => {
  if (content.value.trim()) {
    const trimmedContent = content.value.trim()
    emit('submit', trimmedContent)
    if (!isEditing.value) {
      content.value = ''
    }
  }
}
</script>

