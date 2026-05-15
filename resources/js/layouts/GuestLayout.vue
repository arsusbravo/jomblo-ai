<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-blue-100 flex flex-col justify-center items-center px-4">
    <RouterLink to="/" class="mb-8 flex items-center gap-3">
      <img :src="'/images/jomblo-logo.png'" alt="JombloAI" class="h-12 w-auto" />
      <span class="text-3xl font-bold text-indigo-600">Jomblo<span class="text-fuchsia-500">AI</span></span>
    </RouterLink>
    <div class="w-full max-w-md">
      <RouterView />
    </div>
    <p class="mt-6 text-sm text-gray-500">
      <RouterLink to="/" class="hover:text-indigo-600 transition-colors">{{ i18n.__('auth.back_home') }}</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { useI18nStore } from '@/stores/i18n'

const i18n = useI18nStore()
</script>
