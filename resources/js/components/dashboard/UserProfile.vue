<template>
  <div class="flex flex-col items-center mb-6">
    <div class="relative group">
      <Avatar :user="profile" size="xl" />
      <label
        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity"
        for="profile-image-upload"
      >
        <svg
          class="w-6 h-6 text-white"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
      </label>
      <input
        id="profile-image-upload"
        type="file"
        accept="image/*"
        class="hidden"
        @change="handleImageUpload"
      />
    </div>
    <h2 class="font-bold text-lg mt-2">{{ profile.name }}</h2>
    <p class="text-sm text-gray-500">{{ profile.email }}</p>
    <div v-if="uploading" class="mt-2 text-xs text-blue-500">Uploading...</div>
    <div v-if="uploadError" class="mt-2 text-xs text-red-500">{{ uploadError }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'
import Avatar from '../common/Avatar.vue'

const props = defineProps({
  profile: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['profile-updated'])

const auth = useAuthStore()
const uploading = ref(false)
const uploadError = ref(null)

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Please select an image file'
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    uploadError.value = 'Image size must be less than 2MB'
    return
  }

  uploading.value = true
  uploadError.value = null

  try {
    const formData = new FormData()
    formData.append('profile_image', file)

    const response = await api.post('/profile/image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    await auth.fetchUser()
    
    emit('profile-updated', response.data)

    event.target.value = ''
  } catch (error) {
    uploadError.value = error.response?.data?.message || 'Failed to upload image'
    console.error('Failed to upload profile image:', error)
  } finally {
    uploading.value = false
  }
}
</script>

