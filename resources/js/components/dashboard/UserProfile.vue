<template>
  <div class="flex flex-col items-center mb-6">
    <img class="w-24 h-24 rounded-full mb-2" :src="profile.photo" alt="Profile photo" />
    <h2 class="font-bold text-lg">{{ profile.name }}</h2>
    <p class="text-sm text-gray-500">{{ profile.email }}</p>
  </div>
</template>

<script setup>
defineProps({
  profile: {
    type: Object,
    required: true,
  },
})
</script>

