<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">AI Characters</h2>
      <button
        @click="openCreate"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Character
      </button>
    </div>

    <!-- Gender filter -->
    <div class="flex gap-2 mb-6">
      <button
        v-for="f in filters"
        :key="f.value"
        @click="genderFilter = f.value"
        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :class="genderFilter === f.value
          ? 'bg-indigo-600 text-white'
          : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-400 hover:text-indigo-600'"
      >{{ f.label }}</button>
    </div>

    <div v-if="loading" class="text-gray-500">Loading characters...</div>

    <div v-else-if="!filteredCharacters.length" class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-500">
      No characters yet. Create one to get started.
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
      <div
        v-for="character in filteredCharacters"
        :key="character.id"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
      >
        <!-- Avatar -->
        <div class="relative aspect-3/4 bg-linear-to-br"
          :class="character.gender === 'female' ? 'from-pink-100 to-rose-200' : 'from-blue-100 to-indigo-200'"
        >
          <img
            v-if="character.avatar_url"
            :src="character.avatar_url"
            :alt="character.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-6xl">
            {{ character.gender === 'female' ? '👩' : '👨' }}
          </div>
          <span
            class="absolute top-3 right-3 text-xs px-2 py-1 rounded-full font-medium capitalize"
            :class="character.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'"
          >{{ character.is_active ? 'Active' : 'Inactive' }}</span>
        </div>

        <!-- Info -->
        <div class="p-4">
          <div class="flex items-center gap-2 mb-1">
            <h3 class="font-bold text-gray-900">{{ character.name }}</h3>
            <span
              class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
              :class="character.gender === 'female' ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700'"
            >{{ character.gender }}</span>
          </div>
          <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ character.description }}</p>
          <div class="flex gap-2">
            <button
              @click="openEdit(character)"
              class="flex-1 text-sm bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg hover:bg-indigo-100 transition-colors font-medium"
            >Edit</button>
            <button
              @click="confirmDelete(character)"
              class="flex-1 text-sm bg-red-50 text-red-600 px-3 py-2 rounded-lg hover:bg-red-100 transition-colors font-medium"
            >Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h3 class="text-lg font-bold text-gray-900">{{ modal.editing ? 'Edit Character' : 'Add Character' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="px-6 py-5 space-y-4 overflow-y-auto">
          <!-- Avatar upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
            <div class="flex items-center gap-4">
              <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                <span v-else class="text-3xl">{{ form.gender === 'female' ? '👩' : '👨' }}</span>
              </div>
              <div>
                <label class="cursor-pointer inline-flex items-center gap-2 bg-gray-50 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Upload Image
                  <input type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
                </label>
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF up to 2MB</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Character name"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
              <select
                v-model="form.gender"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              >
                <option value="female">Female</option>
                <option value="male">Male</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 font-normal">(shown to users)</span></label>
            <textarea
              v-model="form.description"
              required
              rows="2"
              placeholder="A short description shown on the character selection screen..."
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Personality Prompt <span class="text-gray-400 font-normal">(sent to AI as system prompt)</span></label>
            <textarea
              v-model="form.personality_prompt"
              required
              rows="5"
              placeholder="You are Sophia, a warm and caring companion who loves deep conversations..."
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none font-mono text-sm"
            />
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="form.is_active = !form.is_active"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-300'"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
            <span class="text-sm font-medium text-gray-700">Active (visible to users)</span>
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
            >{{ saving ? 'Saving...' : (modal.editing ? 'Save Changes' : 'Create Character') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Character</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong>? All conversations with this character will also be deleted.</p>
        <div class="flex gap-3 justify-end">
          <button @click="deleteTarget = null" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
          <button @click="deleteCharacter" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/api'

const characters = ref([])
const loading = ref(true)
const genderFilter = ref('all')

const filters = [
  { value: 'all',    label: 'All' },
  { value: 'female', label: 'Female' },
  { value: 'male',   label: 'Male' },
]

const filteredCharacters = computed(() =>
  genderFilter.value === 'all'
    ? characters.value
    : characters.value.filter(c => c.gender === genderFilter.value)
)
const saving = ref(false)
const errors = ref([])
const deleteTarget = ref(null)
const avatarPreview = ref(null)
const avatarFile = ref(null)

const modal = reactive({ open: false, editing: false, characterId: null })
const form = reactive({
  name: '',
  gender: 'female',
  description: '',
  personality_prompt: '',
  is_active: true,
})

onMounted(fetchCharacters)

async function fetchCharacters() {
  loading.value = true
  try {
    const { data } = await api.get('/api/admin/characters')
    characters.value = data.characters
  } finally {
    loading.value = false
  }
}

function openCreate() {
  modal.open = true
  modal.editing = false
  modal.characterId = null
  Object.assign(form, { name: '', gender: 'female', description: '', personality_prompt: '', is_active: true })
  avatarPreview.value = null
  avatarFile.value = null
  errors.value = []
}

function openEdit(character) {
  modal.open = true
  modal.editing = true
  modal.characterId = character.id
  Object.assign(form, {
    name: character.name,
    gender: character.gender,
    description: character.description,
    personality_prompt: character.personality_prompt,
    is_active: character.is_active,
  })
  avatarPreview.value = character.avatar_url
  avatarFile.value = null
  errors.value = []
}

function closeModal() {
  modal.open = false
}

async function handleAvatarChange(event) {
  const file = event.target.files[0]
  if (!file) return
  avatarPreview.value = URL.createObjectURL(file)
  avatarFile.value = await compressImage(file, 1000, 0.82)
}

function compressImage(file, maxPx, quality) {
  return new Promise((resolve) => {
    const img = new Image()
    img.onload = () => {
      let { width, height } = img
      if (width > maxPx || height > maxPx) {
        if (width > height) { height = Math.round(height * maxPx / width); width = maxPx }
        else { width = Math.round(width * maxPx / height); height = maxPx }
      }
      const canvas = document.createElement('canvas')
      canvas.width = width
      canvas.height = height
      canvas.getContext('2d').drawImage(img, 0, 0, width, height)
      canvas.toBlob(resolve, 'image/jpeg', quality)
    }
    img.src = URL.createObjectURL(file)
  })
}

async function handleSubmit() {
  errors.value = []
  saving.value = true

  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('gender', form.gender)
  formData.append('description', form.description)
  formData.append('personality_prompt', form.personality_prompt)
  formData.append('is_active', form.is_active ? '1' : '0')
  if (avatarFile.value) {
    formData.append('avatar', avatarFile.value)
  }

  try {
    if (modal.editing) {
      const { data } = await api.post(`/api/admin/characters/${modal.characterId}`, formData)
      const idx = characters.value.findIndex(c => c.id === modal.characterId)
      if (idx !== -1) characters.value[idx] = data.character
    } else {
      const { data } = await api.post('/api/admin/characters', formData)
      characters.value.unshift(data.character)
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

function confirmDelete(character) {
  deleteTarget.value = character
}

async function deleteCharacter() {
  await api.delete(`/api/admin/characters/${deleteTarget.value.id}`)
  characters.value = characters.value.filter(c => c.id !== deleteTarget.value.id)
  deleteTarget.value = null
}
</script>
