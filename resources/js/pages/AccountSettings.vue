<template>
  <div class="min-h-screen bg-gray-100">
    <DashboardHeader
      :notifications="notifications"
      :friend-requests="friendRequests"
      :show-notifications="showNotifications"
      :show-friend-requests="showFriendRequests"
      @logout="logout"
      @accept-request="acceptRequest"
      @reject-request="rejectRequest"
      @toggle-notifications="toggleNotifications"
      @toggle-friend-requests="toggleFriendRequests"
      @mark-all-notifications-read="markAllNotificationsRead"
    />

    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Account Settings</h1>

          <!-- Profile Information Section -->
          <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
            
            <form @submit.prevent="updateProfile" class="space-y-4">
              <!-- Profile Image -->
              <div class="flex items-center space-x-4">
                <div class="relative">
                  <Avatar :user="profileData" size="xl" />
                  <label
                    class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 hover:opacity-100 cursor-pointer transition-opacity"
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
                <div>
                  <p class="text-sm text-gray-600">Click on the image to change your profile picture</p>
                  <p class="text-xs text-gray-500 mt-1">JPG, PNG or GIF. Max size 2MB</p>
                </div>
              </div>

              <!-- Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                  Name
                </label>
                <input
                  id="name"
                  v-model="profileData.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': profileErrors.name }"
                />
                <p v-if="profileErrors.name" class="mt-1 text-sm text-red-600">{{ profileErrors.name }}</p>
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                  Email
                </label>
                <input
                  id="email"
                  v-model="profileData.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': profileErrors.email }"
                />
                <p v-if="profileErrors.email" class="mt-1 text-sm text-red-600">{{ profileErrors.email }}</p>
              </div>

              <!-- Profile Update Messages -->
              <div v-if="profileSuccess" class="p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-800">{{ profileSuccess }}</p>
              </div>
              <div v-if="profileError" class="p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-800">{{ profileError }}</p>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="updatingProfile"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="updatingProfile">Saving...</span>
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>

          <hr class="my-8 border-gray-200" />

          <!-- Change Password Section -->
          <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
            
            <form @submit.prevent="updatePassword" class="space-y-4">
              <!-- Current Password -->
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                  Current Password
                </label>
                <input
                  id="current_password"
                  v-model="passwordData.current_password"
                  type="password"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': passwordErrors.current_password }"
                />
                <p v-if="passwordErrors.current_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.current_password }}</p>
              </div>

              <!-- New Password -->
              <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                  New Password
                </label>
                <input
                  id="new_password"
                  v-model="passwordData.password"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': passwordErrors.password }"
                />
                <p v-if="passwordErrors.password" class="mt-1 text-sm text-red-600">{{ passwordErrors.password }}</p>
                <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
              </div>

              <!-- Confirm New Password -->
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                  Confirm New Password
                </label>
                <input
                  id="password_confirmation"
                  v-model="passwordData.password_confirmation"
                  type="password"
                  required
                  minlength="8"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': passwordErrors.password_confirmation }"
                />
                <p v-if="passwordErrors.password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordErrors.password_confirmation }}</p>
              </div>

              <!-- Password Update Messages -->
              <div v-if="passwordSuccess" class="p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-800">{{ passwordSuccess }}</p>
              </div>
              <div v-if="passwordError" class="p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-800">{{ passwordError }}</p>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="updatingPassword"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="updatingPassword">Updating...</span>
                  <span v-else>Update Password</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Back to Dashboard Link -->
      <div class="mt-6">
        <router-link
          to="/"
          class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import api from '../services/api'
import DashboardHeader from '../components/dashboard/DashboardHeader.vue'
import Avatar from '../components/common/Avatar.vue'
import { useNotifications } from '../composables/useNotifications'
import { useFriendRequests } from '../composables/useFriendRequests'

const auth = useAuthStore()
const router = useRouter()

const {
  notifications,
  showNotifications,
  markAllNotificationsRead,
  toggleNotifications: toggleNotificationsComposable,
  loadNotifications,
} = useNotifications()

const {
  friendRequests,
  showFriendRequests,
  toggleFriendRequests: toggleFriendRequestsComposable,
  loadFriendRequests,
} = useFriendRequests()

const profileData = ref({
  name: '',
  email: '',
  profile_image: null,
})

const profileErrors = ref({})
const profileSuccess = ref('')
const profileError = ref('')
const updatingProfile = ref(false)
const uploadingImage = ref(false)

const passwordData = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const passwordErrors = ref({})
const passwordSuccess = ref('')
const passwordError = ref('')
const updatingPassword = ref(false)

onMounted(async () => {
  if (auth.user) {
    profileData.value = {
      name: auth.user.name || '',
      email: auth.user.email || '',
      profile_image: auth.user.profile_image || null,
    }
  }
  
  await loadNotifications()
  await loadFriendRequests()
})

const toggleNotifications = async () => {
  await toggleNotificationsComposable()
}

const toggleFriendRequests = async () => {
  await toggleFriendRequestsComposable()
}

const acceptRequest = async (notification) => {
  try {
    await api.post(`/friendships/${notification.data.friendship_id}/accept`)
    const index = notifications.value.findIndex(n => n.id === notification.id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
    await loadFriendRequests()
  } catch (e) {
    console.error('Failed to accept friend request', e)
  }
}

const rejectRequest = async (notification) => {
  try {
    await api.post(`/friendships/${notification.data.friendship_id}/reject`)
    const index = notifications.value.findIndex(n => n.id === notification.id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  } catch (e) {
    console.error('Failed to reject friend request', e)
  }
}

const logout = async () => {
  await auth.logout()
  router.push('/login')
}

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    profileError.value = 'Please select an image file'
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    profileError.value = 'Image size must be less than 2MB'
    return
  }

  uploadingImage.value = true
  profileError.value = ''
  profileSuccess.value = ''

  try {
    const formData = new FormData()
    formData.append('profile_image', file)

    const response = await api.post('/profile/image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    await auth.fetchUser()
    profileData.value.profile_image = auth.user?.profile_image
    profileSuccess.value = 'Profile image updated successfully'
    event.target.value = ''
  } catch (error) {
    profileError.value = error.response?.data?.message || 'Failed to upload image'
    console.error('Failed to upload profile image:', error)
  } finally {
    uploadingImage.value = false
  }
}

const updateProfile = async () => {
  profileErrors.value = {}
  profileSuccess.value = ''
  profileError.value = ''
  updatingProfile.value = true

  try {
    const response = await api.put('/profile', {
      name: profileData.value.name,
      email: profileData.value.email,
    })

    await auth.fetchUser()
    profileSuccess.value = 'Profile updated successfully'
    
    profileErrors.value = {}
  } catch (error) {
    if (error.response?.status === 422) {
      profileErrors.value = error.response.data.errors || {}
      profileError.value = 'Please correct the errors below'
    } else {
      profileError.value = error.response?.data?.message || 'Failed to update profile'
    }
  } finally {
    updatingProfile.value = false
  }
}

const updatePassword = async () => {
  passwordErrors.value = {}
  passwordSuccess.value = ''
  passwordError.value = ''
  updatingPassword.value = true

  try {
    await api.put('/profile/password', passwordData.value)

    passwordSuccess.value = 'Password updated successfully'
    
    passwordData.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    
    passwordErrors.value = {}
  } catch (error) {
    if (error.response?.status === 422) {
      passwordErrors.value = error.response.data.errors || {}
      passwordError.value = 'Please correct the errors below'
    } else {
      passwordError.value = error.response?.data?.message || 'Failed to update password'
    }
  } finally {
    updatingPassword.value = false
  }
}
</script>

