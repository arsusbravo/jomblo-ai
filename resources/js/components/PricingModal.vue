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

        <!-- Plans -->
        <div class="p-4 grid grid-cols-3 gap-3">
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
              class="w-full mt-auto py-2 rounded-xl text-sm font-semibold transition-colors"
              :class="plan.highlight
                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                : plan.id === 'unlimited'
                  ? 'bg-gray-900 text-white hover:bg-gray-700'
                  : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
            >{{ i18n.__('user.pricing_buy') }}</button>
          </div>
        </div>

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
import { ref, watch } from 'vue'
import { useI18nStore } from '@/stores/i18n'
import api from '@/api'

const props = defineProps({ show: Boolean })
defineEmits(['close'])

const i18n = useI18nStore()
const plans = ref([])
const currency = ref('EUR')

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

function handleBuy(planId) {
  // Stripe integration goes here
  alert(`Coming soon! Plan: ${planId}`)
}
</script>
