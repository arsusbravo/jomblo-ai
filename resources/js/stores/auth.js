import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const loading = ref(false)

    const isAuthenticated = computed(() => !!user.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    async function fetchUser() {
        try {
            const { data } = await api.get('/api/user')
            user.value = data
        } catch {
            user.value = null
        }
    }

    async function login(credentials) {
        await api.get('/sanctum/csrf-cookie')
        await api.post('/login', credentials)
        await fetchUser()
    }

    async function register(formData) {
        await api.get('/sanctum/csrf-cookie')
        await api.post('/register', formData)
        await fetchUser()
    }

    async function logout() {
        try {
            await api.post('/logout')
        } catch {
            // session already expired — still clear local state
        }
        user.value = null
    }

    return { user, loading, isAuthenticated, isAdmin, fetchUser, login, register, logout }
})
