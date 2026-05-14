<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ i18n.__('auth.register_title') }}</h2>
    <p class="text-gray-500 mb-6">{{ i18n.__('auth.register_subtitle') }}</p>

    <form @submit.prevent="handleRegister" novalidate class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.register_name') }}</label>
        <input
          v-model="form.name"
          type="text"
          :placeholder="i18n.__('auth.placeholder_name')"
          :class="fieldClass(errors.name)"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ i18n.__('auth.register_gender') }}</label>
        <div class="flex gap-3">
          <label
            v-for="option in [{ value: 'male', label: i18n.__('auth.register_male'), emoji: '👨' }, { value: 'female', label: i18n.__('auth.register_female'), emoji: '👩' }]"
            :key="option.value"
            class="flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-colors"
            :class="form.gender === option.value
              ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
              : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'"
          >
            <input type="radio" v-model="form.gender" :value="option.value" class="hidden" />
            <span class="text-xl">{{ option.emoji }}</span>
            <span class="font-medium text-sm">{{ option.label }}</span>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.register_email') }}</label>
        <input
          v-model="form.email"
          type="email"
          :placeholder="i18n.__('auth.placeholder_email')"
          :class="fieldClass(errors.email)"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.register_password') }}</label>
        <input
          v-model="form.password"
          type="password"
          :placeholder="i18n.__('auth.placeholder_min8')"
          :class="fieldClass(errors.password)"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.register_confirm') }}</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          :placeholder="i18n.__('auth.placeholder_repeat')"
          :class="fieldClass(errors.password_confirmation)"
        />
        <p v-if="errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ errors.password_confirmation }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('auth.register_dob') }}</label>
        <input
          v-model="form.date_of_birth"
          type="date"
          :class="fieldClass(errors.date_of_birth)"
          @focus="initDob"
        />
        <p v-if="errors.date_of_birth" class="mt-1 text-xs text-red-500">{{ errors.date_of_birth }}</p>
        <p v-else class="mt-1 text-xs text-gray-400">{{ i18n.__('auth.register_dob_hint') }}</p>
      </div>

      <div v-if="turnstileEnabled" ref="turnstileContainer"></div>

      <p v-if="errors._general" class="text-sm text-red-500">{{ errors._general }}</p>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {{ loading ? i18n.__('auth.register_loading') : i18n.__('auth.register_button') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      {{ i18n.__('auth.register_has_account') }}
      <RouterLink to="/login" class="text-indigo-600 font-medium hover:underline">{{ i18n.__('auth.register_login') }}</RouterLink>
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

const defaultDob = new Date(Date.now() - 18 * 365.25 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
const form = ref({ name: '', gender: 'male', email: '', password: '', password_confirmation: '', date_of_birth: '' })
const loading = ref(false)
const errors = ref({})

const turnstileEnabled = !!import.meta.env.VITE_TURNSTILE_SITE_KEY
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

function initDob() {
  if (!form.value.date_of_birth) form.value.date_of_birth = defaultDob
}

function fieldClass(error) {
  return 'w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent transition-colors ' +
    (error
      ? 'border-red-400 focus:ring-red-400 bg-red-50'
      : 'border-gray-300 focus:ring-indigo-500')
}

async function handleRegister() {
  errors.value = {}
  loading.value = true
  try {
    await auth.register({ ...form.value, cf_turnstile_response: turnstileToken.value })
    router.push({ name: 'user.dashboard' })
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) {
      const mapped = {}
      for (const [field, messages] of Object.entries(data.errors)) {
        mapped[field] = messages[0]
      }
      errors.value = mapped
    } else {
      errors.value = { _general: data?.message ?? i18n.__('auth.error_generic') }
    }
    window.turnstile?.reset(turnstileWidgetId)
    turnstileToken.value = ''
  } finally {
    loading.value = false
  }
}
</script>
