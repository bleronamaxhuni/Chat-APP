<template>
  <header class="relative glass backdrop-blur-xl border-b border-white/20 shadow-lg flex items-center justify-between px-3 sm:px-4 md:px-6 py-3 sm:py-4 z-50">
    <!-- Logo - Always visible -->
    <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg flex-shrink-0">
        <i class="fa-solid fa-comments text-white text-lg"></i>
      </div>
      <h1 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent truncate flex-shrink-0">
        Chat App
      </h1>
    </div>

    <div class="hidden md:block absolute left-64 lg:left-72">
      <div class="w-56 lg:w-64">
        <UserSearch 
          ref="userSearchRef"
          @add-friend="$emit('add-friend', $event)"
          @accept-friend="$emit('accept-friend', $event)"
          @select-user="$emit('select-user', $event)"
        />
      </div>
    </div>

    <!-- Desktop Menu -->
    <DesktopMenu
      :notifications="notifications"
      :friend-requests="friendRequests"
      :show-notifications="showNotifications"
      :show-friend-requests="showFriendRequests"
      @logout="$emit('logout')"
      @accept-request="$emit('accept-request', $event)"
      @reject-request="$emit('reject-request', $event)"
      @toggle-notifications="$emit('toggle-notifications')"
      @toggle-friend-requests="$emit('toggle-friend-requests')"
      @mark-all-notifications-read="$emit('mark-all-notifications-read')"
    />

    <!-- Mobile Menu -->
    <MobileMenu
      ref="mobileMenuRef"
      :is-open="showMobileMenu"
      :notifications="notifications"
      :friend-requests="friendRequests"
      @toggle="showMobileMenu = !showMobileMenu"
      @close="showMobileMenu = false"
      @logout="$emit('logout')"
      @accept-request="$emit('accept-request', $event)"
      @reject-request="$emit('reject-request', $event)"
      @add-friend="$emit('add-friend', $event)"
      @accept-friend="$emit('accept-friend', $event)"
      @select-user="$emit('select-user', $event)"
    />
  </header>
</template>

<script setup>
import { ref } from 'vue'
import DesktopMenu from './DesktopMenu.vue'
import MobileMenu from './MobileMenu.vue'
import UserSearch from './UserSearch.vue'

const userSearchRef = ref(null)
const mobileMenuRef = ref(null)
const showMobileMenu = ref(false)

defineExpose({
  get userSearchRef() {
    return userSearchRef.value || mobileMenuRef.value?.userSearchRef
  },
})

const props = defineProps({
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

const emit = defineEmits([
  'logout',
  'accept-request',
  'reject-request',
  'toggle-notifications',
  'toggle-friend-requests',
  'mark-all-notifications-read',
  'add-friend',
  'accept-friend',
  'select-user',
])
</script>

