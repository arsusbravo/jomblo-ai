<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">AI Characters</h2>
      <div class="flex items-center gap-2">
        <!-- Import -->
        <label
          class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-semibold hover:border-indigo-400 hover:text-indigo-600 transition-colors cursor-pointer"
          :class="importing ? 'opacity-50 pointer-events-none' : ''"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          {{ importing ? 'Importing...' : 'Import' }}
          <input type="file" accept=".json" class="hidden" @change="handleImport" :disabled="importing" />
        </label>
        <!-- Export -->
        <button
          @click="handleExport"
          class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-semibold hover:border-indigo-400 hover:text-indigo-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export
        </button>
        <!-- Add -->
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
    </div>

    <!-- Import result toast -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="importResult" class="mb-4 px-4 py-3 rounded-lg text-sm font-medium" :class="importResult.error ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'">
        {{ importResult.message }}
      </div>
    </Transition>

    <!-- Category filter -->
    <div class="flex gap-2 mb-3">
      <button
        v-for="f in categoryFilters"
        :key="f.value"
        @click="categoryFilter = f.value"
        class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :class="categoryFilter === f.value
          ? 'bg-indigo-600 text-white'
          : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-400 hover:text-indigo-600'"
      >{{ f.label }}</button>
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
        v-for="character in pagedCharacters"
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
          <span
            class="absolute top-3 left-3 flex items-center gap-1 text-xs px-2 py-1 rounded-full font-semibold bg-black/55 text-white backdrop-blur-sm"
            title="Users who have chatted with this character"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 004 4z" />
            </svg>
            {{ character.chatters_count ?? 0 }}
          </span>
        </div>

        <!-- Info -->
        <div class="p-4">
          <div class="flex items-center gap-2 mb-1">
            <h3 class="font-bold text-gray-900">{{ character.name }}</h3>
            <span
              class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
              :class="character.gender === 'female' ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700'"
            >{{ character.gender }}</span>
            <span
              v-if="character.category"
              class="text-xs px-2 py-0.5 rounded-full font-medium capitalize bg-gray-100 text-gray-600"
            >{{ character.category }}</span>
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

    <!-- Pagination -->
    <div v-if="!loading && totalPages > 1" class="flex justify-center items-center gap-1 mt-6">
      <button
        @click="currentPage--"
        :disabled="currentPage === 1"
        class="px-3 py-1.5 rounded-lg text-sm border border-gray-200 bg-white text-gray-600 hover:border-indigo-400 hover:text-indigo-600 disabled:opacity-40 disabled:cursor-not-allowed"
      >‹</button>
      <button
        v-for="p in totalPages"
        :key="p"
        @click="currentPage = p"
        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
        :class="p === currentPage
          ? 'bg-indigo-600 text-white'
          : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-400 hover:text-indigo-600'"
      >{{ p }}</button>
      <button
        @click="currentPage++"
        :disabled="currentPage === totalPages"
        class="px-3 py-1.5 rounded-lg text-sm border border-gray-200 bg-white text-gray-600 hover:border-indigo-400 hover:text-indigo-600 disabled:opacity-40 disabled:cursor-not-allowed"
      >›</button>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select
              v-model="form.category"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            >
              <option value="realistic">Realistic</option>
              <option value="anime">Anime</option>
            </select>
          </div>

          <!-- Language tabs (description + prompt are per-language) -->
          <div class="border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex bg-gray-50 border-b border-gray-200">
              <button
                v-for="tab in localeTabs"
                :key="tab.code"
                type="button"
                @click="activeLocale = tab.code"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-colors border-r border-gray-200 last:border-r-0"
                :class="activeLocale === tab.code
                  ? 'bg-white text-indigo-600'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
              >
                <span>{{ tab.flag }}</span>
                <span>{{ tab.label }}</span>
                <span v-if="localeHasContent(tab.code)" class="text-green-500 text-xs">✓</span>
              </button>
            </div>

            <div class="p-4 space-y-4">
              <p v-if="activeLocale !== 'nl'" class="text-xs text-gray-400 -mt-1">
                Optional translation for {{ localeTabs.find(t => t.code === activeLocale)?.label }}. Leave empty to fall back to the default (NL).
              </p>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 font-normal">(shown to users)</span></label>
                <textarea
                  v-model="editingDescription"
                  rows="2"
                  placeholder="A short description shown on the character selection screen..."
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                />
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-sm font-medium text-gray-700">Personality Prompt <span class="text-gray-400 font-normal">(sent to AI as system prompt)</span></label>
                  <button
                    type="button"
                    @click="showPromptEditor = true"
                    class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    Fullscreen
                  </button>
                </div>
                <textarea
                  v-model="editingPrompt"
                  rows="12"
                  placeholder="You are Sophia, a warm and caring companion who loves deep conversations..."
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y font-mono text-sm leading-relaxed"
                />
              </div>
            </div>
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

    <!-- Fullscreen Prompt Editor -->
    <Teleport to="body">
      <div v-if="showPromptEditor" class="fixed inset-0 z-70 bg-gray-950 flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 bg-gray-900 border-b border-gray-700 shrink-0">
          <div>
            <p class="text-white font-semibold text-sm">Personality Prompt — {{ activeLocale.toUpperCase() }}</p>
            <p class="text-gray-400 text-xs mt-0.5">{{ form.name || 'Character' }} — changes save automatically when you close</p>
          </div>
          <button
            type="button"
            @click="showPromptEditor = false"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Done
          </button>
        </div>
        <textarea
          v-model="editingPrompt"
          class="flex-1 w-full bg-gray-950 text-gray-100 font-mono text-sm leading-7 p-6 resize-none focus:outline-none"
          placeholder="You are Sophia..."
          spellcheck="false"
        />
        <div class="px-5 py-2 bg-gray-900 border-t border-gray-700 shrink-0">
          <p class="text-gray-500 text-xs">{{ (editingPrompt || '').length }} characters · {{ (editingPrompt || '').split('\n').length }} lines</p>
        </div>
      </div>
    </Teleport>

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
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api from '@/api'

const characters = ref([])
const loading = ref(true)
const genderFilter = ref('all')
const categoryFilter = ref('all')

const filters = [
  { value: 'all',    label: 'All' },
  { value: 'female', label: 'Female' },
  { value: 'male',   label: 'Male' },
]

const categoryFilters = [
  { value: 'all',       label: 'All' },
  { value: 'realistic', label: 'Realistic' },
  { value: 'anime',     label: 'Anime' },
]

const filteredCharacters = computed(() => {
  let r = characters.value
  if (genderFilter.value !== 'all') {
    r = r.filter(c => c.gender === genderFilter.value)
  }
  if (categoryFilter.value !== 'all') {
    r = r.filter(c => c.category === categoryFilter.value)
  }
  return r
})

// Numbered pagination — 12 per page
const perPage = 12
const currentPage = ref(1)
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredCharacters.value.length / perPage))
)
const pagedCharacters = computed(() =>
  filteredCharacters.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage)
)
watch([genderFilter, categoryFilter], () => { currentPage.value = 1 })
watch(totalPages, (tp) => { if (currentPage.value > tp) currentPage.value = tp })

const saving = ref(false)
const errors = ref([])
const deleteTarget = ref(null)
const showPromptEditor = ref(false)
const importing = ref(false)
const importResult = ref(null)
const avatarPreview = ref(null)
const avatarFile = ref(null)

const modal = reactive({ open: false, editing: false, characterId: null })
const form = reactive({
  name: '',
  gender: 'female',
  category: 'realistic',
  description: '',
  personality_prompt: '',
  is_active: true,
})

// Per-language content. 'nl' lives on the base `form`; others live here.
const localeTabs = [
  { code: 'nl', flag: '🇳🇱', label: 'NL' },
  { code: 'en', flag: '🇬🇧', label: 'EN' },
  { code: 'fr', flag: '🇫🇷', label: 'FR' },
  { code: 'de', flag: '🇩🇪', label: 'DE' },
]
const activeLocale = ref('nl')
const emptyTranslations = () => ({
  en: { description: '', personality_prompt: '' },
  fr: { description: '', personality_prompt: '' },
  de: { description: '', personality_prompt: '' },
})
const translations = reactive(emptyTranslations())

const editingDescription = computed({
  get: () => activeLocale.value === 'nl' ? form.description : translations[activeLocale.value].description,
  set: (v) => { activeLocale.value === 'nl' ? (form.description = v) : (translations[activeLocale.value].description = v) },
})
const editingPrompt = computed({
  get: () => activeLocale.value === 'nl' ? form.personality_prompt : translations[activeLocale.value].personality_prompt,
  set: (v) => { activeLocale.value === 'nl' ? (form.personality_prompt = v) : (translations[activeLocale.value].personality_prompt = v) },
})

function localeHasContent(code) {
  if (code === 'nl') return !!(form.description || form.personality_prompt)
  const t = translations[code]
  return !!(t.description || t.personality_prompt)
}

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
  Object.assign(form, { name: '', gender: 'female', category: 'realistic', description: '', personality_prompt: '', is_active: true })
  Object.assign(translations, emptyTranslations())
  activeLocale.value = 'nl'
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
    category: character.category ?? 'realistic',
    description: character.description,
    personality_prompt: character.personality_prompt,
    is_active: character.is_active,
  })
  Object.assign(translations, emptyTranslations())
  for (const t of character.translations ?? []) {
    if (translations[t.locale]) {
      translations[t.locale].description = t.description ?? ''
      translations[t.locale].personality_prompt = t.personality_prompt ?? ''
    }
  }
  activeLocale.value = 'nl'
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

  // Validate translation tabs up front (before any save) — a locale needs BOTH
  // fields or neither. This avoids creating a base character then failing partway.
  for (const code of ['en', 'fr', 'de']) {
    const t = translations[code]
    const hasOne = !!(t.description?.trim() || t.personality_prompt?.trim())
    const hasBoth = !!(t.description?.trim() && t.personality_prompt?.trim())
    if (hasOne && !hasBoth) {
      errors.value = [`${code.toUpperCase()} translation needs both a description and a prompt (or leave both empty).`]
      activeLocale.value = code
      return
    }
  }

  saving.value = true

  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('gender', form.gender)
  formData.append('category', form.category)
  formData.append('description', form.description)
  formData.append('personality_prompt', form.personality_prompt)
  formData.append('is_active', form.is_active ? '1' : '0')
  if (avatarFile.value) {
    formData.append('avatar', avatarFile.value)
  }

  try {
    let characterId
    let savedCharacter

    if (modal.editing) {
      const { data } = await api.post(`/api/admin/characters/${modal.characterId}`, formData)
      characterId = modal.characterId
      savedCharacter = data.character
    } else {
      const { data } = await api.post('/api/admin/characters', formData)
      characterId = data.character.id
      savedCharacter = data.character
    }

    // Save per-language translations (completeness already validated above).
    const savedTranslations = []
    for (const code of ['en', 'fr', 'de']) {
      const t = translations[code]
      if (!t.description?.trim()) continue
      await api.post(`/api/admin/characters/${characterId}/translations`, {
        locale: code,
        description: t.description,
        personality_prompt: t.personality_prompt,
      })
      savedTranslations.push({ locale: code, description: t.description, personality_prompt: t.personality_prompt })
    }
    savedCharacter.translations = savedTranslations

    if (modal.editing) {
      const idx = characters.value.findIndex(c => c.id === modal.characterId)
      // update endpoint doesn't return chatters_count — keep the existing value
      if (idx !== -1) characters.value[idx] = { ...savedCharacter, chatters_count: characters.value[idx].chatters_count ?? 0 }
    } else {
      characters.value.unshift({ ...savedCharacter, chatters_count: 0 })
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

function handleExport() {
  window.location.href = '/api/admin/characters/export'
}

async function handleImport(event) {
  const file = event.target.files[0]
  if (!file) return
  event.target.value = ''

  importing.value = true
  importResult.value = null

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await api.post('/api/admin/characters/import', formData)
    importResult.value = { error: false, message: `Imported ${data.imported} character(s)${data.skipped ? `, skipped ${data.skipped}` : ''}.` }
    await fetchCharacters()
  } catch (e) {
    importResult.value = { error: true, message: e.response?.data?.message ?? 'Import failed.' }
  } finally {
    importing.value = false
    setTimeout(() => { importResult.value = null }, 5000)
  }
}
</script>
