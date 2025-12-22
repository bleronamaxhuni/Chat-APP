<template>
  <div class="hidden sm:flex items-center space-x-2 sm:space-x-3 md:space-x-4 flex-shrink-0 ml-auto">
    <NotificationsDropdown
      :notifications="notifications"
      :is-open="showNotifications"
      @toggle="$emit('toggle-notifications')"
      @accept="$emit('accept-request', $event)"
      @reject="$emit('reject-request', $event)"
      @mark-all-read="$emit('mark-all-notifications-read')"
    />
    
    <FriendRequestsDropdown
      :friend-requests="friendRequests"
      :is-open="showFriendRequests"
      @toggle="$emit('toggle-friend-requests')"
    />

    <router-link
      to="/settings"
      class="relative p-2.5 rounded-xl hover:bg-white/50 focus:outline-none transition-all duration-200 hover:scale-110 group"
      aria-label="Account Settings"
      title="Account Settings"
    >
      <i class="fa-solid fa-gear text-gray-700 group-hover:text-indigo-600 text-sm sm:text-base transition-colors"></i>
    </router-link>

    <button 
      @click="$emit('logout')" 
      class="relative p-2.5 rounded-xl hover:bg-red-50 focus:outline-none transition-all duration-200 hover:scale-110 group"
      aria-label="Logout"
    >
      <i class="fa-solid fa-arrow-right-from-bracket text-gray-700 group-hover:text-red-600 text-sm sm:text-base transition-colors"></i>
    </button>
  </div>
</template>

<script setup>
import NotificationsDropdown from './NotificationsDropdown.vue'
import FriendRequestsDropdown from './FriendRequestsDropdown.vue'

defineProps({
  notifications: {
    type: Array,
    required: true,
  },
  friendRequests: {
    type: Object,
    required: true,
  },
  showNotifications: {
    type: Boolean,
    default: false,
  },
  showFriendRequests: {
    type: Boolean,
    default: false,
  },
})

defineEmits([
  'logout',
  'accept-request',
  'reject-request',
  'toggle-notifications',
  'toggle-friend-requests',
  'mark-all-notifications-read',
])
</script>

