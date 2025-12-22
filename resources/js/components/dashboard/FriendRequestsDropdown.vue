<template>
  <div class="relative" ref="containerRef">
    <button
      @click="$emit('toggle')"
      class="relative p-2.5 rounded-xl hover:bg-white/50 focus:outline-none transition-all duration-200 hover:scale-110 group"
    >
      <i class="fa-solid fa-user-plus text-gray-700 group-hover:text-indigo-600 text-base transition-colors"></i>
      <span
        v-if="friendRequests.outgoing.filter(r => r.status === 'pending').length > 0"
        class="absolute -top-1 -right-1 w-2 h-2 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full animate-pulse"
      ></span>
    </button>
    <div
      v-if="isOpen"
      class="absolute right-0 top-full mt-2 w-72 sm:w-80 md:w-96 bg-white border border-gray-200 rounded-2xl shadow-2xl z-20 max-h-[80vh] overflow-hidden flex flex-col animate-fade-in"
    >
      <div class="px-4 py-4 border-b border-gray-200 bg-white">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
            <i class="fa-solid fa-user-plus text-white text-xs"></i>
          </div>
          <h3 class="font-bold text-sm bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Requests Sent
          </h3>
        </div>
      </div>
      <div class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 text-sm bg-gray-50">
        <ul class="space-y-3">
          <li v-if="!friendRequests.outgoing.length" class="text-center py-8 text-gray-500">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
              <i class="fa-solid fa-user-plus text-indigo-500"></i>
            </div>
            <p class="text-sm">You haven't sent any friend requests yet.</p>
          </li>
          <li
            v-for="req in friendRequests.outgoing"
            :key="`out-${req.id}`"
            class="bg-white rounded-xl p-3 border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-200 hover:scale-[1.02]"
          >
            <div class="flex justify-between items-center gap-3">
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center flex-shrink-0">
                  <i class="fa-solid fa-user text-indigo-500"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-900 truncate">{{ req.addressee.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ req.addressee.email }}</p>
                </div>
              </div>
              <span
                class="text-xs px-3 py-1.5 rounded-full font-semibold flex-shrink-0 capitalize"
                :class="{
                  'bg-gradient-to-r from-yellow-100 to-orange-100 text-orange-700 border border-orange-200': req.status === 'pending',
                  'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 border border-green-200': req.status === 'accepted',
                  'bg-gradient-to-r from-red-100 to-pink-100 text-red-700 border border-red-200': req.status === 'rejected',
                }"
              >
                <i 
                  :class="{
                    'fa-solid fa-clock': req.status === 'pending',
                    'fa-solid fa-check-circle': req.status === 'accepted',
                    'fa-solid fa-times-circle': req.status === 'rejected',
                  }"
                  class="mr-1"
                ></i>
                {{ req.status }}
              </span>
            </div>
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

