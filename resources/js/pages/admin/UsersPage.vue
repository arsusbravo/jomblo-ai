<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
      <button
        @click="openCreate"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add User
      </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">Loading users...</div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm min-w-160">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Name</th>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Email</th>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Role</th>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Credits</th>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Joined</th>
            <th class="text-left px-6 py-3 text-gray-600 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <span class="font-medium text-gray-900">{{ user.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span
                :class="user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'"
                class="inline-block text-xs px-2 py-0.5 rounded-full font-medium capitalize"
              >{{ user.role }}</span>
            </td>
            <td class="px-6 py-4">
              <span class="font-semibold text-gray-800">{{ user.message_credits ?? 0 }}</span>
            </td>
            <td class="px-6 py-4 text-gray-500">{{ formatDate(user.created_at) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <button
                  @click="openEdit(user)"
                  class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors font-medium"
                >Edit</button>
                <button
                  @click="openGrantCredits(user)"
                  class="text-xs bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors font-medium"
                >Credits</button>
                <button
                  @click="confirmDelete(user)"
                  class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors font-medium"
                >Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="modal.open" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="text-lg font-bold text-gray-900">{{ modal.editing ? 'Edit User' : 'Add User' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="px-6 py-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Full name"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="email@example.com"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select
              v-model="form.role"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            >
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Password
              <span v-if="modal.editing" class="text-gray-400 font-normal">(leave blank to keep current)</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!modal.editing"
              placeholder="Min. 8 characters"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div v-if="errors.length" class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside space-y-1">
              <li v-for="err in errors" :key="err">{{ err }}</li>
            </ul>
          </div>

          <div class="flex gap-3 pt-1">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm"
            >Cancel</button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 bg-indigo-600 text-white py-2.5 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
              {{ saving ? 'Saving...' : (modal.editing ? 'Save Changes' : 'Create User') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Delete User</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong>? This cannot be undone.</p>
        <div class="flex gap-3 justify-end">
          <button @click="deleteTarget = null" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
          <button @click="deleteUser" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">Delete</button>
        </div>
      </div>
    </div>

    <!-- Grant Credits modal -->
    <div v-if="creditsTarget" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Grant Credits</h3>
        <p class="text-gray-500 text-sm mb-4">
          <strong>{{ creditsTarget.name }}</strong> currently has
          <strong>{{ creditsTarget.message_credits }}</strong> credits.
        </p>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Credits to add</label>
          <input
            v-model.number="creditsAmount"
            type="number"
            min="1"
            max="10000"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div class="flex gap-3 justify-end">
          <button @click="creditsTarget = null" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
          <button
            @click="doGrantCredits"
            :disabled="grantingSaving"
            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium disabled:opacity-60"
          >{{ grantingSaving ? 'Saving...' : 'Grant' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import api from '@/api'

const users = ref([])
const loading = ref(true)
const deleteTarget = ref(null)
const saving = ref(false)
const errors = ref([])
const creditsTarget = ref(null)
const creditsAmount = ref(50)
const grantingSaving = ref(false)

const modal = reactive({ open: false, editing: false, userId: null })
const form = reactive({ name: '', email: '', role: 'user', password: '' })

onMounted(fetchUsers)

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/api/admin/users')
    users.value = data.users
  } finally {
    loading.value = false
  }
}

function openCreate() {
  modal.open = true
  modal.editing = false
  modal.userId = null
  Object.assign(form, { name: '', email: '', role: 'user', password: '' })
  errors.value = []
}

function openEdit(user) {
  modal.open = true
  modal.editing = true
  modal.userId = user.id
  Object.assign(form, { name: user.name, email: user.email, role: user.role, password: '' })
  errors.value = []
}

function closeModal() {
  modal.open = false
}

async function handleSubmit() {
  errors.value = []
  saving.value = true
  try {
    if (modal.editing) {
      const { data } = await api.put(`/api/admin/users/${modal.userId}`, form)
      const idx = users.value.findIndex(u => u.id === modal.userId)
      if (idx !== -1) users.value[idx] = data.user
    } else {
      const { data } = await api.post('/api/admin/users', form)
      users.value.unshift(data.user)
    }
    closeModal()
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) {
      errors.value = Object.values(data.errors).flat()
    } else {
      errors.value = [data?.message ?? 'Something went wrong.']
    }
  } finally {
    saving.value = false
  }
}

function confirmDelete(user) {
  deleteTarget.value = user
}

async function deleteUser() {
  await api.delete(`/api/admin/users/${deleteTarget.value.id}`)
  users.value = users.value.filter(u => u.id !== deleteTarget.value.id)
  deleteTarget.value = null
}

function openGrantCredits(user) {
  creditsTarget.value = user
  creditsAmount.value = 50
}

async function doGrantCredits() {
  if (!creditsAmount.value || creditsAmount.value < 1) return
  grantingSaving.value = true
  try {
    const { data } = await api.post(`/api/admin/users/${creditsTarget.value.id}/credits`, { credits: creditsAmount.value })
    const idx = users.value.findIndex(u => u.id === creditsTarget.value.id)
    if (idx !== -1) users.value[idx] = data.user
    creditsTarget.value = null
  } finally {
    grantingSaving.value = false
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>
