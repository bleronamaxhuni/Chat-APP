<template>
  <div class="relative" ref="containerRef">
    <div class="relative">
      <input
        v-model="searchQuery"
        @focus="isFocused = true"
        @input="handleSearch"
        type="text"
        placeholder="Search users..."
        class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-48 sm:w-56 md:w-64"
      />
      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
    </div>

    <!-- Search Results Dropdown -->
    <div
      v-if="isFocused && (searchQuery.length > 0 || searchResults.length > 0)"
      class="absolute right-0 top-full mt-2 w-72 sm:w-80 md:w-96 bg-white border rounded shadow-lg z-30 max-h-[60vh] overflow-hidden flex flex-col"
    >
      <div class="px-4 py-3 border-b">
        <h3 class="font-semibold text-sm">Search Results</h3>
      </div>
      <div class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 text-sm">
        <div v-if="isSearching" class="text-center py-4 text-gray-500">
          Searching...
        </div>
        <div v-else-if="searchQuery.length === 0" class="text-center py-4 text-gray-500">
          Type to search for users
        </div>
        <div v-else-if="searchResults.length === 0" class="text-center py-4 text-gray-500">
          No users found
        </div>
        <ul v-else class="space-y-2">
          <li
            v-for="user in searchResults"
            :key="user.id"
            class="flex items-center justify-between p-2 rounded hover:bg-gray-100 transition-colors cursor-pointer"
            @click="handleUserClick(user)"
          >
            <div class="flex items-center space-x-3 flex-1 min-w-0">
              <Avatar :user="user" size="sm" />
              <div class="flex-1 min-w-0">
                <p class="font-medium truncate">{{ user.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
              </div>
            </div>
            <button 
              v-if="!user.friendship_status || user.friendship_status === 'pending_incoming'"
              @click.stop="user.friendship_status === 'pending_incoming' ? $emit('accept-friend', user) : $emit('add-friend', user)" 
              class="text-xs sm:text-sm text-blue-500 hover:underline flex-shrink-0 ml-2 px-2 py-1 hover:bg-blue-50 rounded"
            >
              {{ user.friendship_status === 'pending_incoming' ? 'Accept' : 'Add Friend' }}
            </button>
            <span
              v-else-if="user.friendship_status === 'pending_outgoing'"
              class="text-xs sm:text-sm text-gray-500 flex-shrink-0 ml-2 px-2 py-1"
            >
              Pending
            </span>
            <span
              v-else-if="user.friendship_status === 'friends'"
              class="text-xs sm:text-sm text-green-600 flex-shrink-0 ml-2 px-2 py-1"
            >
              Friends
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useClickOutside } from '../../composables/useClickOutside'
import Avatar from '../common/Avatar.vue'
import api from '../../services/api'

const emit = defineEmits(['add-friend', 'accept-friend', 'select-user'])

const containerRef = useClickOutside(() => {
  isFocused.value = false
  searchQuery.value = ''
  searchResults.value = []
})

const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const isFocused = ref(false)
let searchTimeout = null

const handleSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  if (searchQuery.value.trim().length === 0) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  
  searchTimeout = setTimeout(async () => {
    try {
      const response = await api.get('/users/search', {
        params: { q: searchQuery.value.trim() },
      })
      searchResults.value = response.data
    } catch (error) {
      console.error('Failed to search users', error)
      searchResults.value = []
    } finally {
      isSearching.value = false
    }
  }, 300)
}

const handleUserClick = (user) => {
  emit('select-user', user)
  searchQuery.value = ''
  searchResults.value = []
  isFocused.value = false
}

const updateUserStatus = (userId, status, friendshipId = null) => {
  const userIndex = searchResults.value.findIndex(u => u.id === userId)
  if (userIndex !== -1) {
    searchResults.value[userIndex].friendship_status = status
    if (friendshipId !== null && friendshipId !== undefined) {
      searchResults.value[userIndex].friendship_id = friendshipId
    }
  }
}

defineExpose({
  updateUserStatus,
})
</script>

