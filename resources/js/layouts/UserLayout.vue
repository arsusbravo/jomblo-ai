<template>
  <div class="h-screen flex flex-col bg-white overflow-hidden">

    <!-- Purple top bar -->
    <header class="bg-indigo-700 h-14 flex items-center justify-between px-4 shrink-0 shadow-md">
      <RouterLink to="/dashboard" class="text-white font-bold text-xl tracking-tight">JombloAI</RouterLink>

      <div class="flex items-center gap-1">

        <!-- 3-dot menu -->
        <div class="relative" ref="menuWrapper">
          <button
            @click="menuOpen = !menuOpen"
            class="p-2 rounded-full text-white hover:bg-white/10 transition-colors"
            aria-label="Menu"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="5" r="1.5" />
              <circle cx="12" cy="12" r="1.5" />
              <circle cx="12" cy="19" r="1.5" />
            </svg>
          </button>

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
          >
            <div
              v-if="menuOpen"
              class="absolute right-0 top-full mt-1 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-1 z-50 origin-top-right"
            >
              <!-- User info -->
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="font-semibold text-gray-900 text-sm">{{ auth.user?.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
              </div>

              <RouterLink
                to="/dashboard"
                @click="menuOpen = false"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                exact-active-class="text-indigo-600 bg-indigo-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                {{ i18n.__('user.nav_dashboard') }}
              </RouterLink>

              <RouterLink
                to="/dashboard/characters"
                @click="menuOpen = false"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                active-class="text-indigo-600 bg-indigo-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                {{ i18n.__('user.nav_find') }}
              </RouterLink>

              <RouterLink
                to="/dashboard/profile"
                @click="menuOpen = false"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                active-class="text-indigo-600 bg-indigo-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ i18n.__('user.nav_profile') }}
              </RouterLink>

              <button
                @click="showPricing = true; menuOpen = false"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-indigo-600 hover:bg-indigo-50 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="flex-1 text-left">{{ i18n.__('user.nav_get_messages') }}</span>
                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-semibold">
                  {{ auth.user?.message_credits ?? 0 }}
                </span>
              </button>

              <div class="border-t border-gray-100 mt-1 pt-1">
                <button
                  @click="handleLogout"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  {{ i18n.__('user.nav_logout') }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto bg-white">
      <RouterView />
    </main>

    <PricingModal :show="showPricing" @close="showPricing = false" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import PricingModal from '@/components/PricingModal.vue'

const auth = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()

const menuOpen = ref(false)
const menuWrapper = ref(null)
const showPricing = ref(false)

function onClickOutside(e) {
  if (menuWrapper.value && !menuWrapper.value.contains(e.target)) {
    menuOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))

async function handleLogout() {
  menuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
