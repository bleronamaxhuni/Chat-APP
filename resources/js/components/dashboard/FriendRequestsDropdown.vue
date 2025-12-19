<template>
  <div class="relative" ref="containerRef">
    <button
      @click="$emit('toggle')"
      class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none"
    >
      <i class="fa-solid fa-user-plus text-gray-700"></i>
    </button>
    <div
      v-if="isOpen"
      class="absolute right-0 top-full mt-2 w-96 bg-white border rounded shadow-lg z-20"
    >
      <div class="px-4 py-3 border-b">
        <h3 class="font-semibold text-sm">Requests Sent</h3>
      </div>
      <div class="max-h-80 overflow-y-auto px-4 py-3 text-sm">
        <ul class="space-y-2">
          <li v-if="!friendRequests.outgoing.length" class="text-gray-500">
            You haven't sent any friend requests yet.
          </li>
          <li
            v-for="req in friendRequests.outgoing"
            :key="`out-${req.id}`"
            class="flex justify-between items-center border-b last:border-b-0 pb-2"
          >
            <div class="flex flex-col">
              <span class="font-medium">{{ req.addressee.name }}</span>
              <span class="text-xs text-gray-500">{{ req.addressee.email }}</span>
            </div>
            <span
              class="text-xs px-2 py-1 rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-800': req.status === 'pending',
                'bg-green-100 text-green-800': req.status === 'accepted',
                'bg-red-100 text-red-800': req.status === 'rejected',
              }"
            >
              {{ req.status }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useClickOutside } from '../../composables/useClickOutside'

const props = defineProps({
  friendRequests: {
    type: Object,
    required: true,
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['toggle'])

const containerRef = useClickOutside(() => {
  if (props.isOpen) {
    emit('toggle')
  }
})
</script>

