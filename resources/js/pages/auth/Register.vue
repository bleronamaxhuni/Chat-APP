<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 p-4">
    <div class="w-full max-w-md glass backdrop-blur-xl p-8 rounded-3xl shadow-2xl border border-white/20 animate-fade-in">
      <!-- Logo/Icon -->
      <div class="flex justify-center mb-6">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-user-plus text-white text-2xl"></i>
        </div>
      </div>

      <h2 class="text-3xl font-bold mb-2 text-center bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
        Create Account
      </h2>
      <p class="text-gray-500 text-center mb-8 text-sm">Join Chat App and start connecting</p>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fa-solid fa-user mr-2 text-indigo-500"></i>Full Name
          </label>
          <input 
            v-model="form.name" 
            type="text" 
            placeholder="Enter your name" 
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white/50" 
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fa-solid fa-envelope mr-2 text-indigo-500"></i>Email
          </label>
          <input 
            v-model="form.email" 
            type="email" 
            placeholder="Enter your email" 
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white/50" 
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fa-solid fa-lock mr-2 text-indigo-500"></i>Password
          </label>
          <input 
            v-model="form.password" 
            type="password" 
            placeholder="Create a password" 
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white/50" 
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fa-solid fa-lock mr-2 text-indigo-500"></i>Confirm Password
          </label>
          <input 
            v-model="form.password_confirmation" 
            type="password" 
            placeholder="Confirm your password" 
            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white/50" 
          />
        </div>

        <button 
          type="submit" 
          class="w-full btn-gradient py-3 rounded-xl font-semibold text-base hover:scale-[1.02] active:scale-[0.98] transition-transform"
        >
          <i class="fa-solid fa-user-plus mr-2"></i>Create Account
        </button>

        <p v-if="error" class="text-red-500 mt-2 text-sm text-center bg-red-50 p-3 rounded-xl border border-red-200">
          <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ error }}
        </p>

        <p class="mt-6 text-sm text-center text-gray-600">
          Already have an account?
          <router-link to="/login" class="text-indigo-600 font-semibold hover:text-purple-600 transition-colors ml-1">
            Sign in instead
          </router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const error = ref(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = async () => {
  error.value = null
  try {
    await auth.register(form.name, form.email, form.password, form.password_confirmation)
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed'
  }
}
</script>
