<template>
  <div class="min-h-screen flex flex-col bg-gray-100">

    <!-- Top Bar -->
    <header class="bg-white shadow flex items-center justify-between px-6 py-3">
      <h1 class="text-xl font-bold">Chat App</h1>
      <button @click="logout" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Logout</button>
    </header>

    <div class="flex flex-1 overflow-hidden">

      <!-- Left Sidebar -->
      <aside class="w-64 bg-white border-r p-4 flex flex-col overflow-y-auto">
        <div class="flex flex-col items-center mb-6">
          <img class="w-24 h-24 rounded-full mb-2" :src="profile.photo" alt="Profile photo" />
          <h2 class="font-bold text-lg">{{ profile.name }}</h2>
          <p class="text-sm text-gray-500">{{ profile.email }}</p>
        </div>

        <h3 class="font-semibold mb-2">Suggested Friends</h3>
        <ul class="space-y-2">
          <li v-for="friend in suggestedFriends" :key="friend.id" class="flex items-center justify-between bg-gray-100 p-2 rounded hover:bg-gray-200">
            <div class="flex items-center space-x-2">
              <img class="w-8 h-8 rounded-full" :src="friend.photo" alt="Friend photo" />
              <span>{{ friend.name }}</span>
            </div>
            <button class="text-sm text-blue-500 hover:underline">Add</button>
          </li>
        </ul>
      </aside>

      <!-- Middle Content -->
      <main class="flex-1 p-6 overflow-y-auto">
        <div v-for="post in posts" :key="post.id" class="bg-white p-4 rounded mb-4 shadow">
          <h4 class="font-bold mb-1">{{ post.author }}</h4>
          <p>{{ post.content }}</p>
        </div>
      </main>

      <!-- Right Sidebar / Conversations -->
      <aside :class="['w-80 bg-white border-l p-4 transition-transform duration-300', showConversations ? 'translate-x-0' : 'translate-x-full']">
        <h3 class="font-semibold mb-4">Conversations</h3>
        <ul class="space-y-2">
          <li v-for="conv in conversations" :key="conv.id" class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer">
            <img class="w-8 h-8 rounded-full mr-2" :src="conv.photo" alt="User" />
            <div>
              <p class="font-semibold">{{ conv.name }}</p>
              <p class="text-sm text-gray-500">{{ conv.lastMessage }}</p>
            </div>
          </li>
        </ul>
      </aside>

      <!-- Floating Button to toggle conversations -->
      <button @click="showConversations = !showConversations"
              class="fixed bottom-6 right-6 bg-blue-500 text-white p-4 rounded-full shadow-lg hover:bg-blue-600">
        💬
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const logout = async () => {
  await auth.logout()
  router.push('/login')
}

// Dummy data for layout
const profile = {
  name: auth.user?.name || 'John Doe',
  email: auth.user?.email || 'john@example.com',
  photo: 'https://i.pravatar.cc/150?img=3'
}

const suggestedFriends = [
  { id: 1, name: 'Alice', photo: 'https://i.pravatar.cc/150?img=1' },
  { id: 2, name: 'Bob', photo: 'https://i.pravatar.cc/150?img=2' },
  { id: 3, name: 'Charlie', photo: 'https://i.pravatar.cc/150?img=4' },
]

const posts = [
  { id: 1, author: 'Alice', content: 'Hello world!' },
  { id: 2, author: 'Bob', content: 'This is a dummy post' },
]

const conversations = [
  { id: 1, name: 'Alice', lastMessage: 'Hey there!', photo: 'https://i.pravatar.cc/150?img=1' },
  { id: 2, name: 'Bob', lastMessage: 'How are you?', photo: 'https://i.pravatar.cc/150?img=2' },
]

const showConversations = ref(false)
</script>

<style>
/* Make right sidebar slide in/out */
aside.translate-x-full {
  transform: translateX(100%);
}

aside.translate-x-0 {
  transform: translateX(0);
}
</style>
