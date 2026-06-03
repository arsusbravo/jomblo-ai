<template>
  <div>
    <!-- Hero -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-24 text-center">
      <h1 class="text-3xl sm:text-5xl font-extrabold text-gray-900 mb-4 sm:mb-6 leading-tight">
        {{ i18n.__('public.hero_title') }}
        <span class="text-indigo-600">Jomblo<span class="text-fuchsia-500">AI</span></span>
      </h1>
      <p class="text-base sm:text-xl text-gray-600 max-w-2xl mx-auto mb-8 sm:mb-10">
        {{ i18n.__('public.hero_subtitle') }}
      </p>
      <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4">
        <RouterLink
          to="/try"
          class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-base font-semibold hover:bg-indigo-700 transition-colors shadow-lg"
        >{{ i18n.__('public.hero_cta') }}</RouterLink>
        <RouterLink
          to="/about"
          class="bg-white text-indigo-600 border border-indigo-200 px-8 py-3 rounded-xl text-base font-semibold hover:bg-indigo-50 transition-colors"
        >{{ i18n.__('public.hero_learn') }}</RouterLink>
      </div>
    </section>

    <!-- Featured characters -->
    <section v-if="featured.female?.length || featured.male?.length" class="py-10 sm:py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-8 sm:space-y-12">
          <!-- Female -->
          <div>
            <p class="text-xs font-semibold text-pink-400 uppercase tracking-widest mb-3 text-center">♀ {{ i18n.__('user.characters_filter_female') }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
              <RouterLink
                v-for="character in featured.female"
                :key="character.id"
                to="/try"
                class="group relative rounded-2xl overflow-hidden shadow-md aspect-3/4 block bg-linear-to-br from-pink-100 to-rose-200"
              >
                <img :src="character.avatar_url" :alt="character.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent px-3 py-3">
                  <p class="text-white text-xs sm:text-sm font-semibold truncate">{{ character.name }}</p>
                </div>
              </RouterLink>
            </div>
          </div>

          <!-- Male -->
          <div>
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-3 text-center">♂ {{ i18n.__('user.characters_filter_male') }}</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
              <RouterLink
                v-for="character in featured.male"
                :key="character.id"
                to="/try"
                class="group relative rounded-2xl overflow-hidden shadow-md aspect-3/4 block bg-linear-to-br from-blue-100 to-indigo-200"
              >
                <img :src="character.avatar_url" :alt="character.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent px-3 py-3">
                  <p class="text-white text-xs sm:text-sm font-semibold truncate">{{ character.name }}</p>
                </div>
              </RouterLink>
            </div>
          </div>
        </div>
        <p class="text-center text-sm text-gray-400 mt-6">{{ i18n.__('public.featured_hint') }}</p>
      </div>
    </section>

    <!-- Features -->
    <section class="bg-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">{{ i18n.__('public.features_title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="feature in features" :key="feature.icon" class="text-center p-6">
            <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <span class="text-2xl">{{ feature.icon }}</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="py-16 bg-linear-to-b from-gray-50 to-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-3">{{ i18n.__('public.how_title') }}</h2>
        <p class="text-center text-gray-500 mb-12">{{ i18n.__('public.how_subtitle') }}</p>

        <!-- Steps -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-14">
          <div v-for="step in steps" :key="step.icon" class="flex flex-col items-center text-center p-5 bg-white rounded-2xl shadow-sm border border-gray-100">
            <span class="text-3xl mb-3">{{ step.icon }}</span>
            <p class="font-semibold text-gray-900 mb-1">{{ step.title }}</p>
            <p class="text-sm text-gray-500">{{ step.desc }}</p>
          </div>
        </div>

        <!-- Level progression -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <p class="font-semibold text-gray-900 text-center">{{ i18n.__('public.how_levels_title') }}</p>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="level in levels" :key="level.name" class="flex items-center gap-4 px-6 py-4">
              <div class="text-2xl w-10 text-center shrink-0">{{ level.icon }}</div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 text-sm">{{ level.name }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ level.desc }}</p>
              </div>
              <div class="text-xs font-medium text-indigo-500 shrink-0 text-right">{{ level.score }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-indigo-600 py-16">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">{{ i18n.__('public.cta_title') }}</h2>
        <p class="text-indigo-200 mb-8">{{ i18n.__('public.cta_subtitle') }}</p>
        <RouterLink
          to="/try"
          class="bg-white text-indigo-600 px-8 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition-colors"
        >{{ i18n.__('public.cta_button') }}</RouterLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18nStore } from '@/stores/i18n'
import api from '@/api'

const i18n = useI18nStore()
const featured = ref({ female: [], male: [] })

onMounted(async () => {
  try {
    const { data } = await api.get('/api/featured-characters')
    featured.value = data
  } catch {}
})

const features = computed(() => [
  { icon: '💘', title: i18n.__('public.feature_ai_title'), description: i18n.__('public.feature_ai_desc') },
  { icon: '📸', title: i18n.__('public.feature_safe_title'), description: i18n.__('public.feature_safe_desc') },
  { icon: '🧠', title: i18n.__('public.feature_chat_title'), description: i18n.__('public.feature_chat_desc') },
])

const steps = computed(() => [
  { icon: '💬', title: i18n.__('public.how_step1_title'), desc: i18n.__('public.how_step1_desc') },
  { icon: '📈', title: i18n.__('public.how_step2_title'), desc: i18n.__('public.how_step2_desc') },
  { icon: '📸', title: i18n.__('public.how_step3_title'), desc: i18n.__('public.how_step3_desc') },
])

const levels = computed(() => [
  { icon: '🌱', name: i18n.__('public.level0_name'), desc: i18n.__('public.level0_desc'), score: i18n.__('public.level_start') },
  { icon: '🌸', name: i18n.__('public.level1_name'), desc: i18n.__('public.level1_desc'), score: '20 pts' },
  { icon: '🔥', name: i18n.__('public.level2_name'), desc: i18n.__('public.level2_desc'), score: '50 pts' },
  { icon: '💋', name: i18n.__('public.level3_name'), desc: i18n.__('public.level3_desc'), score: '100 pts' },
  { icon: '❤️‍🔥', name: i18n.__('public.level4_name'), desc: i18n.__('public.level4_desc'), score: '200 pts' },
])
</script>
