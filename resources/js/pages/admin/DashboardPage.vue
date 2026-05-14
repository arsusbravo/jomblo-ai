<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h2>

    <div v-if="loading" class="text-gray-500">Loading...</div>

    <div v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Total Users</p>
          <p class="text-3xl font-bold text-indigo-600 mt-1">{{ stats?.total_users ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Administrators</p>
          <p class="text-3xl font-bold text-purple-600 mt-1">{{ stats?.total_admins ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Total Members</p>
          <p class="text-3xl font-bold text-green-600 mt-1">{{ (stats?.total_users ?? 0) + (stats?.total_admins ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">System Status</p>
          <p class="text-lg font-bold text-green-600 mt-1">● Online</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex gap-3">
          <RouterLink
            to="/admin/users"
            class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
          >Manage Users</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api'

const stats = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/admin/dashboard')
    stats.value = data.stats
  } catch {
    // handled silently
  } finally {
    loading.value = false
  }
})
</script>
