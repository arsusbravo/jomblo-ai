<template>
  <div class="max-w-2xl p-4 sm:p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ i18n.__('user.profile_title') }}</h2>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
      <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-1">{{ i18n.__('user.profile_balance') }}</p>
      <p v-if="isUnlimited" class="text-lg font-bold text-indigo-600">
        ∞ {{ i18n.__('user.profile_unlimited', { date: unlimitedDate }) }}
      </p>
      <p v-else class="text-lg font-bold" :class="credits === 0 ? 'text-red-500' : credits <= 5 ? 'text-amber-500' : 'text-gray-900'">
        {{ i18n.__('user.credits_remaining', { count: credits }) }}
      </p>
    </div>

    <!-- Guest: prompt to create an account -->
    <div v-if="auth.isGuest" class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 space-y-3">
      <p class="text-sm text-amber-800">{{ i18n.__('user.guest_banner') }}</p>
      <button
        @click="goRegister"
        class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition-colors"
      >{{ i18n.__('user.guest_register_cta') }}</button>
    </div>

    <!-- Registered but email not verified -->
    <div v-else-if="!auth.emailVerified" class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 space-y-3">
      <p class="text-sm text-amber-800">{{ i18n.__('user.pricing_verify_required') }}</p>
      <button
        v-if="!resent"
        @click="resendVerification"
        class="bg-indigo-50 text-indigo-700 px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-indigo-100 transition-colors"
      >{{ i18n.__('user.verify_resend') }}</button>
      <p v-else class="text-green-700 text-sm font-medium">{{ i18n.__('user.verify_resent') }}</p>
    </div>

    <div v-if="!auth.isGuest" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <form @submit.prevent="handleSave" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('user.profile_name') }}</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ i18n.__('user.profile_email') }}</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ i18n.__('user.profile_gender') }}</label>
          <div class="flex gap-3">
            <label
              v-for="option in [{ value: 'male', label: i18n.__('user.profile_male'), emoji: '👨' }, { value: 'female', label: i18n.__('user.profile_female'), emoji: '👩' }]"
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

<div v-if="success" class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
          {{ success }}
        </div>
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="saving"
          class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60"
        >
          {{ saving ? i18n.__('user.profile_saving') : i18n.__('user.profile_save') }}
        </button>
      </form>
    </div>

    <!-- Danger zone -->
    <div v-if="!auth.isAdmin" class="bg-white rounded-2xl shadow-sm border border-red-200 p-6 mt-6">
      <h3 class="text-base font-bold text-red-600 mb-1">{{ i18n.__('user.danger_title') }}</h3>
      <p class="text-sm text-gray-500 mb-4">{{ i18n.__('user.danger_warning') }}</p>
      <button
        @click="showDeleteModal = true"
        class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors"
      >{{ i18n.__('user.danger_delete_account') }}</button>
    </div>

    <!-- Delete confirmation modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/60 backdrop-blur-sm" @click.self="closeDeleteModal">
      <div class="bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 space-y-4">
          <p class="text-3xl text-center">⚠️</p>
          <h3 class="text-lg font-bold text-gray-900 text-center">{{ i18n.__('user.danger_delete_account') }}</h3>
          <p class="text-sm text-gray-600 text-center">{{ i18n.__('user.danger_warning') }}</p>
          <p
            v-if="isUnlimited"
            class="text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-center"
          >{{ i18n.__('user.danger_unlimited_warning', { date: unlimitedDate }) }}</p>
          <p
            v-else-if="credits > 0"
            class="text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-center"
          >{{ i18n.__('user.danger_credits_warning', { count: credits }) }}</p>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ i18n.__('user.danger_confirm_prompt') }}</label>
            <input
              v-model="deleteConfirm"
              type="text"
              placeholder="DELETE"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 font-mono"
              autocomplete="off"
            />
          </div>
          <p v-if="deleteError" class="text-sm text-red-500 text-center">{{ deleteError }}</p>
          <div class="flex gap-3 pt-2">
            <button
              @click="closeDeleteModal"
              :disabled="deleting"
              class="flex-1 py-2.5 rounded-lg text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-60"
            >{{ i18n.__('user.danger_cancel') }}</button>
            <button
              @click="deleteAccount"
              :disabled="deleting || deleteConfirm !== 'DELETE'"
              class="flex-1 py-2.5 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
            >{{ deleting ? i18n.__('user.danger_deleting') : i18n.__('user.danger_confirm_button') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'

const auth = useAuthStore()
const i18n = useI18nStore()
const router = useRouter()
const resent = ref(false)

function goRegister() {
  router.push({ name: 'register' })
}

async function resendVerification() {
  try {
    await api.post('/email/verification-notification')
  } catch {
    // throttled or already sent — same UX
  }
  resent.value = true
}

const showDeleteModal = ref(false)
const deleteConfirm = ref('')
const deleting = ref(false)
const deleteError = ref('')

function closeDeleteModal() {
  if (deleting.value) return
  showDeleteModal.value = false
  deleteConfirm.value = ''
  deleteError.value = ''
}

async function deleteAccount() {
  if (deleteConfirm.value !== 'DELETE') return
  deleting.value = true
  deleteError.value = ''
  try {
    await api.delete('/api/user/profile?confirm=DELETE')
    auth.user = null
    // Hard navigation clears any in-memory state cleanly post-deletion
    window.location.href = '/'
  } catch (e) {
    deleting.value = false
    deleteError.value = e.response?.data?.message ?? i18n.__('auth.error_generic')
  }
}

const isUnlimited = computed(() => {
  const until = auth.user?.unlimited_until
  return !!until && new Date(until) > new Date()
})
const unlimitedDate = computed(() =>
  auth.user?.unlimited_until
    ? new Date(auth.user.unlimited_until).toLocaleDateString()
    : ''
)
const credits = computed(() => auth.user?.message_credits ?? 0)

const form = ref({ name: '', email: '', gender: 'male' })
const saving = ref(false)
const success = ref('')
const error = ref('')

onMounted(() => {
  form.value.name      = auth.user?.name      ?? ''
  form.value.email     = auth.user?.email     ?? ''
  form.value.gender = auth.user?.gender ?? 'male'
})

async function handleSave() {
  saving.value = true
  success.value = ''
  error.value = ''
  try {
    const { data } = await api.put('/api/user/profile', form.value)
    auth.user = data.user
    success.value = i18n.__('user.profile_success')
  } catch (e) {
    error.value = e.response?.data?.message ?? i18n.__('auth.error_generic')
  } finally {
    saving.value = false
  }
}
</script>
