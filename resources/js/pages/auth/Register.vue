<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-bold mb-4 text-center">Register</h2>

      <form @submit.prevent="submit">
        <input v-model="form.name" type="text" placeholder="Name" class="w-full mb-3 px-3 py-2 border rounded" />
        <input v-model="form.email" type="email" placeholder="Email" class="w-full mb-3 px-3 py-2 border rounded" />
        <input v-model="form.password" type="password" placeholder="Password" class="w-full mb-3 px-3 py-2 border rounded" />
        <input v-model="form.password_confirmation" type="password" placeholder="Confirm Password" class="w-full mb-3 px-3 py-2 border rounded" />

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Register</button>

        <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>

        <p class="mt-3 text-sm">
          Already have an account?
          <router-link to="/login" class="text-green-600">Login</router-link>
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
