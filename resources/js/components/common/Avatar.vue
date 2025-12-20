<template>
  <div
    :class="[
      'rounded-full flex items-center justify-center overflow-hidden',
      avatarUrl && !imageError ? 'bg-gray-200' : '',
      !avatarUrl || imageError ? 'text-white font-semibold' : '',
      sizeClasses
    ]"
    :style="(!avatarUrl || imageError) ? { backgroundColor: avatarColor } : {}"
  >
    <img
      v-if="avatarUrl && !imageError"
      :src="avatarUrl"
      :alt="user?.name || 'Avatar'"
      class="w-full h-full object-cover"
      @error="handleImageError"
    />
    <span v-else>{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { getAvatarUrl, getInitials, getAvatarColor } from '../../composables/useAvatar'

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
  },
})

const imageError = ref(false)

const sizeClasses = computed(() => {
  const sizes = {
    xs: 'w-6 h-6 text-xs',
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-16 h-16 text-xl',
    xl: 'w-24 h-24 text-2xl',
  }
  return sizes[props.size] || sizes.md
})

const avatarUrl = computed(() => getAvatarUrl(props.user))
const initials = computed(() => getInitials(props.user))
const avatarColor = computed(() => getAvatarColor(props.user))

const handleImageError = () => {
  imageError.value = true
}
</script>

