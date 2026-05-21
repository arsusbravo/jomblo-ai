<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ i18n.__('auth.login_title') }}</h2>
    <p class="text-gray-500 mb-6">{{ i18n.__('auth.login_subtitle') }}</p>

    <div v-if="googleEnabled" class="mb-5">
      <a
        href="/auth/google/redirect"
        class="w-full flex items-center justify-center gap-3 py-2.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold transition-colors"
      >
        <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.4-.4-3.5z"/>
          <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 16 19 13 24 13c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6 29.3 4 24 4 16.4 4 9.8 8.3 6.3 14.7z"/>
          <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.3 35 26.8 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.6 39.6 16.2 44 24 44z"/>
          <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.3-4.1 5.7l6.2 5.2C41 35.7 44 30.3 44 24c0-1.2-.1-2.4-.4-3.5z"/>
        </svg>
        {{ i18n.__('auth.continue_with_google') }}
      </a>
      <div class="flex items-center gap-3 my-4 text-xs uppercase text-gray-400">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span>{{ i18n.__('auth.or_divider') }}</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>
    </div>

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
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()
const route = useRoute()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

const googleEnabled = !!import.meta.env.VITE_GOOGLE_ENABLED
const turnstileEnabled = !!import.meta.env.VITE_TURNSTILE_SITE_KEY && !window.location.hostname.endsWith('.test')
const turnstileContainer = ref(null)
const turnstileToken = ref('')
let turnstileWidgetId = null

onMounted(() => {
  if (turnstileEnabled) renderTurnstile()
  if (route.query.social === 'error') {
    error.value = i18n.__('auth.social_error')
    router.replace({ query: {} })
  }
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
