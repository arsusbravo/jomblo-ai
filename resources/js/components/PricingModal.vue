<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/60 backdrop-blur-sm"
      @click.self="$emit('close')"
    >
      <div class="bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="px-6 pt-6 pb-4 text-center border-b border-gray-100 relative">
          <p class="text-3xl mb-2">💬</p>
          <h2 class="text-xl font-bold text-gray-900">{{ i18n.__('user.pricing_title') }}</h2>
          <p class="text-sm text-gray-500 mt-1">{{ i18n.__('user.pricing_subtitle') }}</p>
          <button
            @click="$emit('close')"
            class="absolute top-5 right-5 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Guest: must create an account first -->
        <div v-if="gateState === 'guest'" class="px-6 py-8 text-center space-y-4">
          <p class="text-3xl">🔒</p>
          <p class="text-gray-600 text-sm">{{ i18n.__('user.pricing_register_required') }}</p>
          <button
            @click="goRegister"
            class="w-full py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
          >{{ i18n.__('user.guest_register_cta') }}</button>
        </div>

        <!-- Registered but email not verified -->
        <div v-else-if="gateState === 'unverified'" class="px-6 py-8 text-center space-y-4">
          <p class="text-3xl">📧</p>
          <p class="text-gray-600 text-sm">{{ i18n.__('user.pricing_verify_required') }}</p>
          <button
            v-if="!resent"
            @click="resendVerification"
            class="w-full py-2.5 rounded-xl text-sm font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors"
          >{{ i18n.__('user.verify_resend') }}</button>
          <p v-else class="text-green-600 text-sm font-medium">{{ i18n.__('user.verify_resent') }}</p>
        </div>

        <!-- Plans -->
        <div v-else class="p-4 grid grid-cols-3 gap-3">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="rounded-2xl p-4 flex flex-col items-center text-center gap-2 relative"
            :class="plan.highlight
              ? 'border-2 border-indigo-500 bg-indigo-50'
              : 'border border-gray-200'"
          >
            <span
              v-if="plan.highlight"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-bold px-3 py-0.5 rounded-full"
            >★ {{ i18n.__('user.pricing_popular_name') }}</span>

            <p class="text-2xl">{{ plan.emoji }}</p>
            <p class="font-bold text-sm" :class="plan.highlight ? 'text-indigo-900' : 'text-gray-900'">
              {{ i18n.__(`user.pricing_${plan.id}_name`) }}
            </p>
            <p class="text-2xl font-extrabold" :class="plan.highlight ? 'text-indigo-900' : 'text-gray-900'">
              {{ formatPrice(plan.amount, currency) }}
            </p>
            <p class="text-xs" :class="plan.highlight ? 'text-indigo-400' : 'text-gray-400'">
              {{ plan.billing === 'monthly' ? i18n.__('user.pricing_per_month') : i18n.__('user.pricing_one_time') }}
            </p>
            <p class="text-sm font-semibold text-indigo-700 mt-1">
              {{ plan.credits ? `${plan.credits} msgs` : '∞ msgs' }}
            </p>
            <button
              @click="handleBuy(plan.id)"
              :disabled="buying"
              class="w-full mt-auto py-2 rounded-xl text-sm font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
              :class="plan.highlight
                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                : plan.id === 'unlimited'
                  ? 'bg-gray-900 text-white hover:bg-gray-700'
                  : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
            >{{ buying ? i18n.__('user.pricing_redirecting') : i18n.__('user.pricing_buy') }}</button>
          </div>
        </div>

        <p v-if="buyError" class="px-6 -mt-2 text-center text-sm text-red-500">{{ buyError }}</p>

        <!-- Footer -->
        <div class="px-6 pb-6 text-center">
          <button
            @click="$emit('close')"
            class="text-sm text-gray-400 hover:text-gray-600 transition-colors"
          >{{ i18n.__('user.pricing_close') }}</button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18nStore } from '@/stores/i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const props = defineProps({ show: Boolean })
const emit = defineEmits(['close'])

const i18n = useI18nStore()
const auth = useAuthStore()
const router = useRouter()
const plans = ref([])
const currency = ref('EUR')
const buying = ref(false)
const buyError = ref('')
const resent = ref(false)

const gateState = computed(() => {
  if (auth.isGuest) return 'guest'
  if (!auth.emailVerified) return 'unverified'
  return 'ok'
})

function goRegister() {
  emit('close')
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

watch(() => props.show, async (val) => {
  if (val && !plans.value.length) {
    const { data } = await api.get('/api/pricing')
    plans.value = data.plans
    currency.value = data.currency
  }
}, { immediate: true })

function formatPrice(amountInCents, currencyCode) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: currencyCode,
    minimumFractionDigits: 2,
  }).format(amountInCents / 100)
}

async function handleBuy(planId) {
  if (buying.value) return
  buying.value = true
  buyError.value = ''
  try {
    const { data } = await api.post('/api/user/checkout', { plan: planId })
    window.location.href = data.url
  } catch (e) {
    buying.value = false
    const msg = e.response?.data?.message
    if (msg === 'register_required') buyError.value = i18n.__('user.pricing_register_required')
    else if (msg === 'email_verification_required') buyError.value = i18n.__('user.pricing_verify_required')
    else if (e.response?.status === 503) buyError.value = i18n.__('user.pricing_unavailable')
    else buyError.value = i18n.__('auth.error_generic')
  }
}
</script>
