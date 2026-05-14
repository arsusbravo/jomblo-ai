<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-4 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-4 opacity-0"
  >
    <div
      v-if="visible"
      class="fixed bottom-0 inset-x-0 z-50 p-4 sm:p-6"
    >
      <div class="max-w-3xl mx-auto bg-gray-900 text-white rounded-2xl shadow-2xl px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <p class="text-sm text-gray-300 flex-1 leading-relaxed">
          🍪 Wij gebruiken strikt noodzakelijke cookies om de website te laten werken.
          Geen tracking, geen advertenties.
          <RouterLink to="/cookies" class="text-indigo-400 hover:text-indigo-300 underline ml-1">Meer informatie</RouterLink>
        </p>
        <button
          @click="accept"
          class="shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
        >
          Begrepen
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const STORAGE_KEY = 'jomblo_cookie_consent'
const visible = ref(!localStorage.getItem(STORAGE_KEY))

function accept() {
  localStorage.setItem(STORAGE_KEY, '1')
  visible.value = false
}
</script>
