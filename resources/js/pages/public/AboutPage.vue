<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h1 class="text-4xl font-bold text-gray-900 mb-6">{{ i18n.__('public.about_title') }}</h1>
    <p class="text-lg text-gray-600 mb-6">{{ i18n.__('public.about_p1') }}</p>
    <p class="text-gray-600 mb-6">{{ i18n.__('public.about_p2') }}</p>
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-8 mt-10">
      <h2 class="text-2xl font-bold text-indigo-900 mb-4">{{ i18n.__('public.about_mission_title') }}</h2>
      <p class="text-indigo-800">{{ i18n.__('public.about_mission_content') }}</p>
    </div>
    <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      <div v-for="stat in stats" :key="stat.label" class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
        <p class="text-3xl font-bold text-indigo-600">{{ stat.value }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ stat.label }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18nStore } from '@/stores/i18n'

const i18n = useI18nStore()

const stats = [
  { value: '50K+', label: 'Users' },
  { value: '12K+', label: 'Matches Made' },
  { value: '98%', label: 'Satisfaction Rate' },
  { value: '24/7', label: 'AI Support' },
]
</script>
