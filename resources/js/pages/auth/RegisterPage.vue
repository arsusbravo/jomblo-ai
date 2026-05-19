<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ i18n.__('auth.register_upgrade_title') }}</h2>
    <p class="text-gray-500 mb-6">{{ i18n.__('auth.register_upgrade_subtitle') }}</p>

    <form @submit.prevent="handleRegister" novalidate class="space-y-4">
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

      <p v-if="errors._general" class="text-sm text-red-500">{{ errors._general }}</p>

      <button
        type="submit"
        :disabled="loading || !formReady"
        class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {{ loading ? i18n.__('auth.register_loading') : i18n.__('auth.register_button') }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm">
      <RouterLink to="/dashboard" class="text-gray-500 hover:text-indigo-600 hover:underline">{{ i18n.__('auth.register_upgrade_back') }}</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()

const form = ref({ email: '', password: '', password_confirmation: '' })
const loading = ref(false)
const errors = ref({})

const formReady = computed(() =>
  form.value.email.trim() !== '' &&
  form.value.password !== '' &&
  form.value.password_confirmation !== ''
)

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
    await auth.register(form.value)
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
  } finally {
    loading.value = false
  }
}
</script>
