<template>
  <aside
    :class="[
      'w-80 bg-white border-l p-4 transition-transform duration-300',
      isOpen ? 'translate-x-0' : 'translate-x-full'
    ]"
  >
    <h3 class="font-semibold mb-4">Conversations</h3>
    <ul class="space-y-2">
      <li
        v-for="conv in conversations"
        :key="conv.id"
        class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
      >
        <img class="w-8 h-8 rounded-full mr-2" :src="conv.photo" alt="User" />
        <div>
          <p class="font-semibold">{{ conv.name }}</p>
          <p class="text-sm text-gray-500">{{ conv.lastMessage }}</p>
        </div>
      </li>
    </ul>
  </aside>
</template>

<script setup>
defineProps({
  conversations: {
    type: Array,
    required: true,
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
})
</script>

<style scoped>
aside.translate-x-full {
  transform: translateX(100%);
}

aside.translate-x-0 {
  transform: translateX(0);
}
</style>

