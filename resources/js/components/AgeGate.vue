<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
  >
    <div v-if="visible" class="fixed inset-0 z-[100] bg-gray-950/95 backdrop-blur-sm flex items-center justify-center p-6">
      <div class="max-w-sm w-full text-center">
        <p class="text-5xl mb-6">🔞</p>
        <h2 class="text-2xl font-extrabold text-white mb-3">Leeftijdsverificatie</h2>
        <p class="text-gray-400 mb-8 leading-relaxed">
          JombloAI is uitsluitend bestemd voor personen van <strong class="text-white">18 jaar en ouder</strong>.
          Ben jij 18 jaar of ouder?
        </p>
        <div class="flex gap-3">
          <button
            @click="deny"
            class="flex-1 py-3 rounded-xl border border-gray-600 text-gray-300 hover:bg-gray-800 transition-colors font-medium"
          >
            Nee, ik ben jonger
          </button>
          <button
            @click="accept"
            class="flex-1 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-colors"
          >
            Ja, ik ben 18+
          </button>
        </div>
        <p class="text-xs text-gray-600 mt-6">
          Door verder te gaan ga je akkoord met onze
          <RouterLink to="/terms" @click="accept" class="text-gray-500 hover:text-gray-400 underline">Algemene Voorwaarden</RouterLink>.
        </p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const STORAGE_KEY = 'jomblo_age_verified'
const visible = ref(!sessionStorage.getItem(STORAGE_KEY))

function accept() {
  sessionStorage.setItem(STORAGE_KEY, '1')
  visible.value = false
}

function deny() {
  window.location.href = 'https://www.google.com'
}
</script>
