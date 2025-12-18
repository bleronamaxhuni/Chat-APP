<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-bold mb-4 text-center">Login</h2>

      <form @submit.prevent="submit">
        <input v-model="form.email" type="email" placeholder="Email" class="w-full mb-3 px-3 py-2 border rounded" />
        <input v-model="form.password" type="password" placeholder="Password" class="w-full mb-3 px-3 py-2 border rounded" />

        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">Login</button>

        <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>

        <p class="mt-3 text-sm">
          Don't have an account?
          <router-link to="/register" class="text-blue-600">Register</router-link>
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
  email: '',
  password: '',
})

const submit = async () => {
  error.value = null
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid credentials'
  }
}
</script>
