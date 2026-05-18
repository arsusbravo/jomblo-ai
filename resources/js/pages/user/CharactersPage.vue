<template>
  <div class="p-4 sm:p-6">
    <div class="mb-4">
      <h2 class="text-2xl font-bold text-gray-900">{{ i18n.__('user.characters_title') }}</h2>
      <p class="text-gray-500 mt-1">{{ i18n.__('user.characters_subtitle') }}</p>
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

    <div v-if="loading" class="text-gray-500">{{ i18n.__('user.characters_loading') }}</div>

    <div v-else-if="!filteredCharacters.length" class="text-center py-16 text-gray-400">
      {{ i18n.__('user.characters_empty') }}
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="character in displayedCharacters"
        :key="character.id"
        @click="startChat(character.id)"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
      >
        <!-- Avatar -->
        <div
          class="aspect-3/4 relative"
          :class="character.gender === 'female' ? 'bg-linear-to-br from-pink-100 to-rose-200' : 'bg-linear-to-br from-blue-100 to-indigo-200'"
        >
          <img
            v-if="character.avatar_url"
            :src="character.avatar_url"
            :alt="character.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-8xl">
            {{ character.gender === 'female' ? '👩' : '👨' }}
          </div>
          <span
            class="absolute bottom-3 left-3 text-xs font-semibold px-3 py-1 rounded-full capitalize"
            :class="character.gender === 'female' ? 'bg-pink-500 text-white' : 'bg-blue-500 text-white'"
          >{{ character.gender === 'female' ? i18n.__('user.chat_honey') : i18n.__('user.chat_babe') }}</span>
        </div>

        <!-- Info -->
        <div class="p-5">
          <h3 class="text-lg font-bold text-gray-900 mb-1">{{ character.name }}</h3>
          <p class="text-sm text-gray-500 mb-4 line-clamp-3">{{ character.description }}</p>
          <button
            @click.stop="startChat(character.id)"
            class="w-full py-2.5 rounded-xl font-semibold text-sm transition-colors"
            :class="character.gender === 'female'
              ? 'bg-pink-500 text-white hover:bg-pink-600'
              : 'bg-indigo-600 text-white hover:bg-indigo-700'"
          >
            {{ i18n.__('user.characters_chat') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Infinite-scroll trigger -->
    <div v-if="!loading && hasMore" ref="sentinel" class="flex justify-center py-8">
      <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { useI18nStore } from '@/stores/i18n'

const router = useRouter()
const i18n = useI18nStore()
const characters = ref([])
const loading = ref(true)
const genderFilter = ref('female')

const filters = computed(() => [
  { value: 'female', label: i18n.__('user.characters_filter_female') },
  { value: 'male',   label: i18n.__('user.characters_filter_male') },
])

const filteredCharacters = computed(() =>
  genderFilter.value === 'all'
    ? characters.value
    : characters.value.filter(c => c.gender === genderFilter.value)
)

// Infinite scroll: 3 per load on mobile, 6 on tablet/desktop
const pageSizeFor = () => (window.innerWidth < 640 ? 3 : 6)
const pageSize = ref(pageSizeFor())
const visibleCount = ref(pageSize.value)

const displayedCharacters = computed(() =>
  filteredCharacters.value.slice(0, visibleCount.value)
)
const hasMore = computed(() => visibleCount.value < filteredCharacters.value.length)

function onResize() {
  pageSize.value = pageSizeFor()
}

// Switching gender restarts paging from the top
watch(genderFilter, () => {
  visibleCount.value = pageSize.value
})

const sentinel = ref(null)
let observer = null

watch(sentinel, (el) => {
  observer?.disconnect()
  if (el) observer?.observe(el)
})

onMounted(async () => {
  window.addEventListener('resize', onResize)
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting && hasMore.value) {
      visibleCount.value += pageSize.value
    }
  }, { rootMargin: '200px' })

  try {
    const { data } = await api.get('/api/user/characters')
    characters.value = data.characters
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  observer?.disconnect()
})

function startChat(characterId) {
  router.push({ name: 'user.chat', params: { characterId } })
}
</script>
