<template>
  <header class="relative bg-white shadow flex items-center justify-between px-3 sm:px-4 md:px-6 py-2 sm:py-3">
    <h1 class="text-lg sm:text-xl font-bold truncate">Chat App</h1>
    <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 flex-shrink-0">
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
      <button 
        @click="$emit('logout')" 
        class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none transition-colors"
        aria-label="Logout"
      >
        <i class="fa-solid fa-arrow-right-from-bracket text-gray-700 text-sm sm:text-base"></i>
      </button>
    </div>
  </header>
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

