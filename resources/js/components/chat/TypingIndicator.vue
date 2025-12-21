<template>
  <div v-if="isTyping" class="mb-3 flex justify-start pb-2">
    <div class="max-w-[75%] px-3 py-2 rounded-lg text-sm bg-gray-200 text-gray-800">
      <div class="flex items-center gap-1">
        <span class="text-xs text-gray-600">{{ userName }} is typing</span>
        <div class="flex gap-1">
          <span class="typing-dot" style="animation-delay: 0s"></span>
          <span class="typing-dot" style="animation-delay: 0.2s"></span>
          <span class="typing-dot" style="animation-delay: 0.4s"></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isTyping: {
    type: Boolean,
    required: true,
  },
  userName: {
    type: String,
    required: true,
  },
})
</script>

<style scoped>
.typing-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #6b7280;
  animation: typing 1.4s infinite;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-8px);
    opacity: 1;
  }
}
</style>

