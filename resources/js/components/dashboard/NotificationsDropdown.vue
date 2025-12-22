<template>
  <div class="relative" ref="containerRef">
    <button
      @click="$emit('toggle')"
      class="relative p-2.5 rounded-xl hover:bg-white/50 focus:outline-none transition-all duration-200 hover:scale-110 group"
    >
      <i class="fa-regular fa-bell text-gray-700 group-hover:text-indigo-600 text-base transition-colors"></i>
      <span
        v-if="unreadNotificationCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold leading-none text-white bg-gradient-to-r from-red-500 to-pink-500 rounded-full shadow-lg animate-pulse"
      >
        {{ unreadNotificationCount > 9 ? '9+' : unreadNotificationCount }}
      </span>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 top-full mt-2 w-72 sm:w-80 md:w-96 bg-white border border-gray-200 rounded-2xl shadow-2xl z-30 max-h-[80vh] overflow-hidden flex flex-col animate-fade-in"
    >
      <div class="px-4 py-4 border-b border-gray-200 flex items-center justify-between bg-white">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
            <i class="fa-solid fa-bell text-white text-xs"></i>
          </div>
          <h3 class="font-bold text-sm bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Notifications
          </h3>
        </div>
        <button
          v-if="unreadNotificationCount > 0"
          @click="$emit('mark-all-read')"
          class="text-xs text-indigo-600 hover:text-purple-600 font-medium hover:underline transition-colors"
        >
          Mark all read
        </button>
      </div>
      <div class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 text-sm bg-gray-50">
        <ul class="space-y-3">
          <li v-if="!incomingRequests.length" class="text-center py-8 text-gray-500">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
              <i class="fa-regular fa-bell text-indigo-500"></i>
            </div>
            <p class="text-sm">No notifications</p>
          </li>
          <li
            v-for="n in incomingRequests"
            :key="n.id"
            class="bg-white rounded-xl p-3 border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-200 hover:scale-[1.02]"
          >
            <div class="flex justify-between items-start gap-2">
              <div class="flex-1 min-w-0">
                <div class="flex items-start gap-2">
                  <div 
                    :class="[
                      'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                      getNotificationIconClass(n)
                    ]"
                  >
                    <i :class="getNotificationIcon(n)"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm leading-relaxed">
                      <span v-if="n.data?.type === 'friend_request_received' && !n.data.result">
                        <span class="font-semibold text-indigo-600">{{ n.data.from_user_name }}</span> sent you a friend request
                      </span>
                      <span v-else-if="n.data?.type === 'friend_request_received' && n.data.result === 'accepted'">
                        You accepted <span class="font-semibold text-indigo-600">{{ n.data.from_user_name }}</span>'s friend request
                      </span>
                      <span v-else-if="n.data?.type === 'friend_request_accepted'">
                        <span class="font-semibold text-green-600">{{ n.data.by_user_name }}</span> accepted your friend request
                      </span>
                      <span v-else-if="n.data?.result === 'rejected'">
                        You declined <span class="font-semibold text-red-600">{{ n.data.from_user_name }}</span>'s friend request
                      </span>
                      <span v-else-if="n.data?.type === 'post_liked'">
                        <span class="font-semibold text-pink-600">{{ n.data.from_user_name }}</span> liked your post
                      </span>
                      <span v-else-if="n.data?.type === 'post_commented'">
                        <span class="font-semibold text-indigo-600">{{ n.data.from_user_name }}</span> commented on your post
                      </span>
                    </p>
                  </div>
                </div>
              </div>
              <span
                v-if="!n.read_at"
                class="mt-1 h-2.5 w-2.5 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex-shrink-0 animate-pulse"
              ></span>
            </div>
            <div v-if="n.data?.type === 'friend_request_received' && !n.data.result" class="mt-3 flex gap-2">
              <button
                @click="$emit('accept', n)"
                class="flex-1 px-3 py-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-semibold hover:from-green-600 hover:to-emerald-600 transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105"
              >
                <i class="fa-solid fa-check mr-1"></i>Accept
              </button>
              <button
                @click="$emit('reject', n)"
                class="flex-1 px-3 py-2 rounded-xl bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-semibold hover:from-red-600 hover:to-pink-600 transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105"
              >
                <i class="fa-solid fa-times mr-1"></i>Decline
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useClickOutside } from '../../composables/useClickOutside'

const props = defineProps({
  notifications: {
    type: Array,
    required: true,
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['toggle', 'accept', 'reject', 'mark-all-read'])

const incomingRequests = computed(() =>
  props.notifications.filter(
    n => n.data?.type === 'friend_request_received' 
      || n.data?.type === 'friend_request_accepted'
      || n.data?.type === 'post_liked'
      || n.data?.type === 'post_commented'
  )
)

const unreadNotificationCount = computed(
  () => props.notifications.filter(n => !n.read_at).length
)

const getNotificationIcon = (notification) => {
  if (notification.data?.type === 'friend_request_received' || notification.data?.type === 'friend_request_accepted') {
    return 'fa-solid fa-user-plus text-white text-xs'
  } else if (notification.data?.type === 'post_liked') {
    return 'fa-solid fa-heart text-white text-xs'
  } else if (notification.data?.type === 'post_commented') {
    return 'fa-solid fa-comment text-white text-xs'
  }
  return 'fa-solid fa-bell text-white text-xs'
}

const getNotificationIconClass = (notification) => {
  if (notification.data?.type === 'friend_request_received' || notification.data?.type === 'friend_request_accepted') {
    return 'bg-gradient-to-br from-indigo-500 to-purple-500'
  } else if (notification.data?.type === 'post_liked') {
    return 'bg-gradient-to-br from-pink-500 to-rose-500'
  } else if (notification.data?.type === 'post_commented') {
    return 'bg-gradient-to-br from-blue-500 to-cyan-500'
  }
  return 'bg-gradient-to-br from-gray-400 to-gray-500'
}

const containerRef = useClickOutside(() => {
  if (props.isOpen) {
    emit('toggle')
  }
})
</script>

