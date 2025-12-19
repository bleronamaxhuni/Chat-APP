<template>
  <div>
    <h3 class="font-semibold mb-2">Suggested Friends</h3>
    <ul class="space-y-2">
      <li
        v-for="friend in friends"
        :key="friend.id"
        class="flex items-center justify-between bg-gray-100 p-2 rounded hover:bg-gray-200"
      >
        <div class="flex items-center space-x-2">
          <img class="w-8 h-8 rounded-full" :src="friend.photo" alt="Friend photo" />
          <span>{{ friend.name }}</span>
        </div>
        <button @click="$emit('addFriend', friend)" class="text-sm text-blue-500 hover:underline">
          Add
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  friends: {
    type: Array,
    required: true,
  },
})

defineEmits(['addFriend'])
</script>

