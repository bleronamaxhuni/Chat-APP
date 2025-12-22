<template>
  <div class="relative" ref="containerRef">
    <div class="relative">
        <input
        v-model="searchQuery"
        @focus="isFocused = true"
        @input="handleSearch"
        type="text"
        placeholder="Search users..."
        class="pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white/80 backdrop-blur-sm w-full sm:w-56 md:w-64 transition-all duration-200"
      />
      <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
    </div>

    <!-- Search Results Dropdown -->
    <div
      v-if="isFocused && (searchQuery.length > 0 || searchResults.length > 0)"
      class="absolute left-0 top-full mt-2 w-72 sm:w-80 md:w-96 bg-white border border-gray-200 rounded-2xl shadow-2xl z-30 max-h-[60vh] overflow-hidden flex flex-col animate-fade-in"
    >
      <div class="px-4 py-3 border-b border-gray-200 bg-white">
        <h3 class="font-semibold text-sm bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Search Results</h3>
      </div>
      <div class="flex-1 overflow-y-auto px-3 sm:px-4 py-3 text-sm bg-gray-50">
        <div v-if="isSearching" class="text-center py-8 text-gray-500">
          <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
            <i class="fa-solid fa-spinner fa-spin text-indigo-500"></i>
          </div>
          <p class="text-sm">Searching...</p>
        </div>
        <div v-else-if="searchQuery.length === 0" class="text-center py-8 text-gray-500">
          <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
            <i class="fa-solid fa-magnifying-glass text-indigo-500"></i>
          </div>
          <p class="text-sm">Search...</p>
        </div>
        <div v-else-if="searchResults.length === 0" class="text-center py-8 text-gray-500">
          <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
            <i class="fa-solid fa-user-slash text-indigo-500"></i>
          </div>
          <p class="text-sm">No users found</p>
        </div>
        <ul v-else class="space-y-2">
          <li
            v-for="user in searchResults"
            :key="user.id"
            class="flex items-center justify-between p-3 rounded-xl bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-200 cursor-pointer"
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
              :class="[
                'text-xs sm:text-sm font-semibold flex-shrink-0 ml-2 px-3 py-1.5 rounded-lg transition-all duration-200',
                user.friendship_status === 'pending_incoming'
                  ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600 shadow-sm hover:shadow-md hover:scale-105'
                  : 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600 shadow-sm hover:shadow-md hover:scale-105'
              ]"
            >
              <i :class="user.friendship_status === 'pending_incoming' ? 'fa-solid fa-check mr-1' : 'fa-solid fa-user-plus mr-1'"></i>
              {{ user.friendship_status === 'pending_incoming' ? 'Accept' : 'Add Friend' }}
            </button>
            <span
              v-else-if="user.friendship_status === 'pending_outgoing'"
              class="text-xs sm:text-sm text-orange-600 font-semibold flex-shrink-0 ml-2 px-3 py-1.5 bg-orange-50 rounded-lg border border-orange-200"
            >
              <i class="fa-solid fa-clock mr-1"></i>Pending
            </span>
            <span
              v-else-if="user.friendship_status === 'friends'"
              class="text-xs sm:text-sm text-green-600 font-semibold flex-shrink-0 ml-2 px-3 py-1.5 bg-green-50 rounded-lg border border-green-200"
            >
              <i class="fa-solid fa-check-circle mr-1"></i>Friends
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

