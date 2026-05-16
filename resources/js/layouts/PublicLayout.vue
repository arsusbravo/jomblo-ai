<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm border-b border-gray-200 relative z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <RouterLink to="/" class="flex items-center gap-2">
            <img :src="'/images/jomblo-logo.png'" alt="JombloAI" class="h-8 w-auto" />
            <span class="text-xl font-bold text-indigo-600">Jomblo<span class="text-fuchsia-500">AI</span></span>
          </RouterLink>

          <!-- Desktop links -->
          <div class="hidden sm:flex items-center gap-4">
            <RouterLink to="/" class="text-gray-600 hover:text-indigo-600 transition-colors text-sm">{{ i18n.__('public.nav_home') }}</RouterLink>
            <RouterLink to="/about" class="text-gray-600 hover:text-indigo-600 transition-colors text-sm">{{ i18n.__('public.nav_about') }}</RouterLink>
            <template v-if="auth.isAuthenticated">
              <RouterLink
                :to="auth.isAdmin ? '/admin' : '/dashboard'"
                class="text-gray-600 hover:text-indigo-600 transition-colors text-sm"
              >{{ i18n.__('public.nav_dashboard') }}</RouterLink>
            </template>
            <template v-else>
              <RouterLink to="/login" class="text-gray-600 hover:text-indigo-600 transition-colors text-sm">{{ i18n.__('public.nav_login') }}</RouterLink>
              <RouterLink
                to="/register"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors text-sm font-semibold"
              >{{ i18n.__('public.nav_get_started') }}</RouterLink>
            </template>
          </div>

          <!-- Mobile right side -->
          <div class="flex sm:hidden items-center gap-2">
            <button
              @click="mobileOpen = !mobileOpen"
              class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
              aria-label="Menu"
            >
              <svg v-if="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="-translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-2 opacity-0"
      >
        <div v-if="mobileOpen" class="sm:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
          <RouterLink
            to="/"
            @click="mobileOpen = false"
            class="block px-3 py-2.5 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-sm font-medium"
          >{{ i18n.__('public.nav_home') }}</RouterLink>
          <RouterLink
            to="/about"
            @click="mobileOpen = false"
            class="block px-3 py-2.5 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-sm font-medium"
          >{{ i18n.__('public.nav_about') }}</RouterLink>

          <template v-if="auth.isAuthenticated">
            <RouterLink
              :to="auth.isAdmin ? '/admin' : '/dashboard'"
              @click="mobileOpen = false"
              class="block px-3 py-2.5 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-sm font-medium"
            >{{ i18n.__('public.nav_dashboard') }}</RouterLink>
          </template>
          <template v-else>
            <RouterLink
              to="/login"
              @click="mobileOpen = false"
              class="block px-3 py-2.5 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-sm font-medium"
            >{{ i18n.__('public.nav_login') }}</RouterLink>
            <RouterLink
              to="/register"
              @click="mobileOpen = false"
              class="block px-3 py-2.5 rounded-xl bg-indigo-600 text-white text-center text-sm font-semibold hover:bg-indigo-700 transition-colors mt-2"
            >{{ i18n.__('public.nav_get_started') }}</RouterLink>
          </template>
        </div>
      </Transition>
    </nav>

    <main>
      <RouterView />
    </main>

    <footer class="bg-white border-t border-gray-200 mt-16">
      <div class="max-w-7xl mx-auto px-4 py-8 text-center text-sm text-gray-400 space-y-3">
        <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
          <RouterLink to="/privacy" class="hover:text-indigo-600 transition-colors">Privacybeleid</RouterLink>
          <RouterLink to="/terms" class="hover:text-indigo-600 transition-colors">Algemene Voorwaarden</RouterLink>
          <RouterLink to="/cookies" class="hover:text-indigo-600 transition-colors">Cookiebeleid</RouterLink>
        </div>
        <p class="text-gray-500">{{ i18n.__('public.age_notice') }}</p>
        <p>&copy; {{ new Date().getFullYear() }} JombloAI — Arsus · KVK 76343251</p>
      </div>
    </footer>

    <CookieBanner />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import CookieBanner from '@/components/CookieBanner.vue'

const auth = useAuthStore()
const i18n = useI18nStore()
const mobileOpen = ref(false)
</script>
