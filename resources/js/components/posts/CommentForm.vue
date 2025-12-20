<template>
  <div class="mt-2">
    <form @submit.prevent="handleSubmit" v-if="!isEditing || comment">
      <div class="flex gap-2">
        <input
          v-model="content"
          type="text"
          placeholder="Write a comment..."
          class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          :maxlength="1000"
        />
        <button
          v-if="isEditing"
          @click="$emit('cancel')"
          type="button"
          class="px-3 py-2 text-gray-600 hover:text-gray-800"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="!content.trim() || loading"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? '...' : isEditing ? 'Update' : 'Comment' }}
        </button>
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
    emit('submit', content.value.trim())
  }
}
</script>

