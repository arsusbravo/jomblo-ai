<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h2>

    <div v-if="loading" class="text-gray-500">Loading...</div>

    <div v-else>
      <!-- Members -->
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-400 mb-3">Members</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Users</p>
          <p class="text-3xl font-bold text-indigo-600 mt-1">{{ stats?.total_users ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Guests</p>
          <p class="text-3xl font-bold text-amber-600 mt-1">{{ stats?.guest_users ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Admins</p>
          <p class="text-3xl font-bold text-purple-600 mt-1">{{ stats?.total_admins ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">New (7 days)</p>
          <p class="text-3xl font-bold text-green-600 mt-1">{{ stats?.new_users_7d ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">System</p>
          <p class="text-lg font-bold text-green-600 mt-1">● Online</p>
        </div>
      </div>

      <!-- Characters -->
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-400 mb-3">Characters</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Total</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats?.characters?.total ?? 0 }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ stats?.characters?.active ?? 0 }} active</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Female</p>
          <p class="text-3xl font-bold text-pink-600 mt-1">{{ stats?.characters?.female ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Male</p>
          <p class="text-3xl font-bold text-blue-600 mt-1">{{ stats?.characters?.male ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Realistic</p>
          <p class="text-3xl font-bold text-gray-700 mt-1">{{ stats?.characters?.realistic ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Anime</p>
          <p class="text-3xl font-bold text-fuchsia-600 mt-1">{{ stats?.characters?.anime ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <p class="text-sm text-gray-500">Inactive</p>
          <p class="text-3xl font-bold text-gray-400 mt-1">{{ stats?.characters?.inactive ?? 0 }}</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
          <RouterLink
            to="/admin/users"
            class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
          >Manage Users</RouterLink>
          <RouterLink
            to="/admin/characters"
            class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
          >Manage Characters</RouterLink>
        </div>
      </div>

      <!-- New users (last 7 days) -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">New Users (last 7 days)</h3>
          <span class="text-xs text-gray-400">{{ newUsers?.length ?? 0 }} shown</span>
        </div>
        <p v-if="!newUsers?.length" class="text-sm text-gray-500">No new users in the last 7 days.</p>
        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="u in newUsers"
            :key="u.id"
            class="flex items-center gap-3 py-3"
          >
            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-semibold flex items-center justify-center text-sm shrink-0">
              {{ (u.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ u.name || '—' }}</p>
              <p class="text-xs text-gray-500 truncate">{{ u.email || '(no email)' }}</p>
            </div>
            <span
              class="text-[10px] uppercase tracking-wide px-2 py-0.5 rounded-full font-semibold"
              :class="badgeClass(u)"
            >{{ labelFor(u) }}</span>
            <span class="text-xs text-gray-400 shrink-0">{{ formatDate(u.created_at) }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api'

const stats = ref(null)
const newUsers = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/admin/dashboard')
    stats.value = data.stats
    newUsers.value = data.new_users ?? []
  } catch {
    // handled silently
  } finally {
    loading.value = false
  }
})

function labelFor(u) {
  if (u.role === 'admin') return 'Admin'
  if (u.is_guest) return 'Guest'
  return 'User'
}

function badgeClass(u) {
  if (u.role === 'admin') return 'bg-purple-100 text-purple-700'
  if (u.is_guest) return 'bg-amber-100 text-amber-700'
  return 'bg-indigo-100 text-indigo-700'
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString() : ''
}
</script>
