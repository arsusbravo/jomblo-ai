<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ i18n.__('auth.login_title') }}</h2>
    <p class="text-gray-500 mb-6">{{ i18n.__('auth.login_subtitle') }}</p>

    <form @submit.prevent="handleLogin" novalidate class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.login_email') }}</label>
        <input
          v-model="form.email"
          type="email"
          :placeholder="i18n.__('auth.placeholder_email')"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.login_password') }}</label>
        <input
          v-model="form.password"
          type="password"
          :placeholder="i18n.__('auth.placeholder_password')"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
        />
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
        {{ error }}
      </div>

      <div v-if="turnstileEnabled" ref="turnstileContainer"></div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {{ loading ? i18n.__('auth.login_loading') : i18n.__('auth.login_button') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      {{ i18n.__('auth.login_no_account') }}
      <RouterLink to="/try" class="text-indigo-600 font-medium hover:underline">{{ i18n.__('auth.register_as_guest') }}</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

const turnstileEnabled = !!import.meta.env.VITE_TURNSTILE_SITE_KEY && !window.location.hostname.endsWith('.test')
const turnstileContainer = ref(null)
const turnstileToken = ref('')
let turnstileWidgetId = null

onMounted(() => {
  if (turnstileEnabled) renderTurnstile()
})

onUnmounted(() => {
  if (turnstileWidgetId !== null) window.turnstile?.remove(turnstileWidgetId)
})

function renderTurnstile() {
  if (window.turnstile && turnstileContainer.value) {
    turnstileWidgetId = window.turnstile.render(turnstileContainer.value, {
      sitekey: import.meta.env.VITE_TURNSTILE_SITE_KEY,
      callback: (token) => { turnstileToken.value = token },
      'expired-callback': () => { turnstileToken.value = '' },
    })
  } else {
    setTimeout(renderTurnstile, 100)
  }
}

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login({ ...form.value, cf_turnstile_response: turnstileToken.value })
    router.push(auth.isAdmin ? { name: 'admin.dashboard' } : { name: 'user.dashboard' })
  } catch {
    error.value = i18n.__('auth.error_invalid')
    window.turnstile?.reset(turnstileWidgetId)
    turnstileToken.value = ''
  } finally {
    loading.value = false
  }
}
</script>
