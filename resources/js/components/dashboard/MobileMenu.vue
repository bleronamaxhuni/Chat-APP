<template>
  <div>
    <button
      @click="$emit('toggle')"
      class="sm:hidden p-3 rounded-xl bg-white/80 hover:bg-white focus:outline-none transition-all duration-200 hover:scale-110 relative z-10 shadow-md border border-gray-200"
      aria-label="Menu"
    >
      <i class="fa-solid fa-bars text-gray-700 text-xl"></i>
    </button>

    <div
      v-show="isOpen"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 sm:hidden"
      @click="$emit('close')"
    ></div>

    <div
      v-show="isOpen"
      class="fixed top-0 right-0 h-screen w-80 bg-white shadow-2xl z-50 sm:hidden flex flex-col transform transition-transform duration-300 ease-in-out"
      :class="isOpen ? 'translate-x-0' : 'translate-x-full'"
    >
      <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 flex-shrink-0">
        <h2 class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          Menu
        </h2>
        <button
          @click="$emit('close')"
          class="p-2 rounded-xl hover:bg-gray-100 transition-colors"
          aria-label="Close menu"
        >
          <i class="fa-solid fa-times text-gray-600"></i>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-4 space-y-4">
        <UserSearch 
          ref="userSearchRef"
          @add-friend="$emit('add-friend', $event)"
          @accept-friend="$emit('accept-friend', $event)"
          @select-user="$emit('select-user', $event)"
        />

        <div class="border-b border-gray-200 pb-4">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-semibold text-gray-700">
              <i class="fa-regular fa-bell mr-2 text-indigo-500"></i>Notifications
            </label>
            <span
              v-if="unreadNotificationCount > 0"
              class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold leading-none text-white bg-gradient-to-r from-red-500 to-pink-500 rounded-full"
            >
              {{ displayNotificationCount }}
            </span>
          </div>
          <div class="max-h-64 overflow-y-auto space-y-2">
            <div v-if="mobileNotifications.length === 0" class="text-sm text-gray-500 text-center py-4">
              No notifications
            </div>
            <div
              v-for="n in mobileNotifications"
              :key="n.id"
              class="bg-gray-50 rounded-xl p-3 border border-gray-200"
            >
              <template v-if="getNotificationMessage(n)">
                <p class="text-sm text-gray-900 mb-2">
                  <span class="font-semibold" :class="getNotificationMessage(n).colorClass">
                    {{ getNotificationMessage(n).userName }}
                  </span>
                  {{ getNotificationMessage(n).message }}
                </p>
              </template>
              <div v-if="isPendingFriendRequest(n)" class="flex gap-2 mt-2">
                <button
                  @click="handleNotificationAction('accept-request', n)"
                  class="flex-1 px-3 py-1.5 rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-semibold"
                >
                  Accept
                </button>
                <button
                  @click="handleNotificationAction('reject-request', n)"
                  class="flex-1 px-3 py-1.5 rounded-lg bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-semibold"
                >
                  Decline
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="border-b border-gray-200 pb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-3">
            <i class="fa-solid fa-user-plus mr-2 text-indigo-500"></i>Requests Sent
          </label>
          <div class="max-h-48 overflow-y-auto space-y-2">
            <div v-if="!hasOutgoingRequests" class="text-sm text-gray-500 text-center py-4">
              No requests sent
            </div>
            <div
              v-for="req in friendRequests.outgoing"
              :key="`out-${req.id}`"
              class="bg-gray-50 rounded-xl p-3 border border-gray-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-900 truncate">{{ req.addressee.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ req.addressee.email }}</p>
                </div>
                <span
                  class="text-xs px-2 py-1 rounded-full font-semibold flex-shrink-0 capitalize"
                  :class="getStatusBadgeClasses(req.status)"
                >
                  {{ req.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <router-link
          to="/settings"
          @click="$emit('close')"
          class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 transition-all duration-200"
        >
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-gear text-white text-sm"></i>
          </div>
          <span class="font-semibold text-gray-900">Account Settings</span>
        </router-link>

        <button
          @click="$emit('logout'); $emit('close')"
          class="w-full flex items-center gap-3 p-3 rounded-xl bg-red-50 hover:bg-red-100 border border-red-200 hover:border-red-300 transition-all duration-200"
        >
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-arrow-right-from-bracket text-white text-sm"></i>
          </div>
          <span class="font-semibold text-red-700">Log Out</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import UserSearch from './UserSearch.vue'

const MOBILE_NOTIFICATIONS_LIMIT = 10

const NOTIFICATION_TYPES = {
  FRIEND_REQUEST_RECEIVED: 'friend_request_received',
  FRIEND_REQUEST_ACCEPTED: 'friend_request_accepted',
  POST_LIKED: 'post_liked',
  POST_COMMENTED: 'post_commented',
}

const FRIEND_REQUEST_STATUS = {
  PENDING: 'pending',
  ACCEPTED: 'accepted',
  REJECTED: 'rejected',
}

const userSearchRef = ref(null)

defineExpose({
  userSearchRef,
})

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  notifications: {
    type: Array,
    required: true,
  },
  friendRequests: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'toggle',
  'close',
  'logout',
  'accept-request',
  'reject-request',
  'add-friend',
  'accept-friend',
  'select-user',
])

const unreadNotificationCount = computed(
  () => props.notifications.filter(n => !n.read_at).length
)

const displayNotificationCount = computed(() =>
  unreadNotificationCount.value > 9 ? '9+' : unreadNotificationCount.value
)

const mobileNotifications = computed(() =>
  props.notifications
    .filter(n => Object.values(NOTIFICATION_TYPES).includes(n.data?.type))
    .slice(0, MOBILE_NOTIFICATIONS_LIMIT)
)

const hasOutgoingRequests = computed(() => props.friendRequests.outgoing.length > 0)

const getNotificationMessage = (notification) => {
  const { data } = notification
  if (!data) return null

  const messageMap = {
    [NOTIFICATION_TYPES.FRIEND_REQUEST_RECEIVED]: {
      condition: !data.result,
      userName: data.from_user_name,
      message: 'sent you a friend request',
      colorClass: 'text-indigo-600',
    },
    [NOTIFICATION_TYPES.FRIEND_REQUEST_ACCEPTED]: {
      condition: true,
      userName: data.by_user_name,
      message: 'accepted your friend request',
      colorClass: 'text-green-600',
    },
    [NOTIFICATION_TYPES.POST_LIKED]: {
      condition: true,
      userName: data.from_user_name,
      message: 'liked your post',
      colorClass: 'text-pink-600',
    },
    [NOTIFICATION_TYPES.POST_COMMENTED]: {
      condition: true,
      userName: data.from_user_name,
      message: 'commented on your post',
      colorClass: 'text-indigo-600',
    },
  }

  const config = messageMap[data.type]
  return config && config.condition ? {
    userName: config.userName,
    message: config.message,
    colorClass: config.colorClass,
  } : null
}

const isPendingFriendRequest = (notification) =>
  notification.data?.type === NOTIFICATION_TYPES.FRIEND_REQUEST_RECEIVED &&
  !notification.data.result

const getStatusBadgeClasses = (status) => {
  const statusClasses = {
    [FRIEND_REQUEST_STATUS.PENDING]: 'bg-orange-100 text-orange-700',
    [FRIEND_REQUEST_STATUS.ACCEPTED]: 'bg-green-100 text-green-700',
    [FRIEND_REQUEST_STATUS.REJECTED]: 'bg-red-100 text-red-700',
  }
  return statusClasses[status] || ''
}

const handleNotificationAction = (action, notification) => {
  emit(action, notification)
  emit('close')
}
</script>

