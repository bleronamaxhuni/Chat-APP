<template>
  <div>
    <h3 class="font-semibold text-sm sm:text-base mb-2 sm:mb-3">Suggested Friends</h3>
    <ul class="space-y-1.5 sm:space-y-2">
      <li
        v-for="friend in friends"
        :key="friend.id"
        class="flex items-center justify-between bg-gray-100 p-2 rounded hover:bg-gray-200 transition-colors"
      >
        <div class="flex items-center space-x-2 flex-1 min-w-0">
          <Avatar :user="friend" size="sm" />
          <span class="text-xs sm:text-sm truncate">{{ friend.name }}</span>
        </div>
        <button 
          @click="$emit('addFriend', friend)" 
          class="text-xs sm:text-sm text-blue-500 hover:underline flex-shrink-0 ml-2"
        >
          Add
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import Avatar from '../common/Avatar.vue'

defineProps({
  friends: {
    type: Array,
    required: true,
  },
})

defineEmits(['addFriend'])
</script>

