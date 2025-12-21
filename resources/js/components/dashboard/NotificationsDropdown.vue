<template>
  <div class="relative" ref="containerRef">
    <button
      @click="$emit('toggle')"
      class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none"
    >
      <i class="fa-regular fa-bell text-gray-700"></i>
      <span
        v-if="unreadNotificationCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full"
      >
        {{ unreadNotificationCount }}
      </span>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 top-full mt-2 w-72 sm:w-80 md:w-96 bg-white border rounded shadow-lg z-30 max-h-[80vh] overflow-hidden flex flex-col"
    >
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h3 class="font-semibold text-sm">Notifications</h3>
        <button
          v-if="unreadNotificationCount > 0"
          @click="$emit('mark-all-read')"
          class="text-xs text-blue-500 hover:underline"
        >
          Mark all as read
        </button>
      </div>
      <div class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 text-sm">
        <ul class="space-y-2">
          <li v-if="!incomingRequests.length" class="text-gray-500">
            No notifications.
          </li>
          <li
            v-for="n in incomingRequests"
            :key="n.id"
            class="border-b last:border-b-0 pb-2"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1 mr-2">
                <p class="font-medium">
                  <span v-if="n.data?.type === 'friend_request_received' && !n.data.result">
                    {{ n.data.from_user_name }} sent you a friend request
                  </span>
                  <span v-else-if="n.data?.type === 'friend_request_received' && n.data.result === 'accepted'">
                    You accepted {{ n.data.from_user_name }}'s friend request
                  </span>
                  <span v-else-if="n.data?.type === 'friend_request_accepted'">
                    {{ n.data.by_user_name }} accepted your friend request
                  </span>
                  <span v-else-if="n.data?.result === 'rejected'">
                    You declined {{ n.data.from_user_name }}'s friend request
                  </span>
                </p>
              </div>
              <span
                v-if="!n.read_at"
                class="mt-1 h-2 w-2 rounded-full bg-blue-500"
              ></span>
            </div>
            <div v-if="n.data?.type === 'friend_request_received' && !n.data.result" class="mt-2 flex space-x-2 text-xs">
              <button
                @click="$emit('accept', n)"
                class="px-2 py-1 rounded bg-green-500 text-white hover:bg-green-600"
              >
                Accept
              </button>
              <button
                @click="$emit('reject', n)"
                class="px-2 py-1 rounded bg-red-500 text-white hover:bg-red-600"
              >
                Decline
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
    n => n.data?.type === 'friend_request_received' || n.data?.type === 'friend_request_accepted'
  )
)

const unreadNotificationCount = computed(
  () => incomingRequests.value.filter(n => !n.read_at).length
)

const containerRef = useClickOutside(() => {
  if (props.isOpen) {
    emit('toggle')
  }
})
</script>

