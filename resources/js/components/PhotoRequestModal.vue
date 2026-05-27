<template>
  <Transition
    enter-active-class="transition duration-150 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-100 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-2xl w-full max-w-sm shadow-xl overflow-hidden">

        <!-- Confirm screen (first time this session) -->
        <div v-if="showConfirm">
          <div class="px-6 pt-6 pb-4 text-center">
            <p class="text-4xl mb-3">📸</p>
            <h3 class="font-bold text-gray-900 text-base">{{ i18n.__('user.photo_confirm_title') }}</h3>
            <p class="text-sm text-gray-500 mt-1.5">{{ i18n.__('user.photo_confirm_body', { credits: imageCost }) }}</p>
          </div>
          <div class="flex border-t border-gray-100">
            <button
              @click="$emit('close')"
              class="flex-1 py-4 text-sm text-gray-500 hover:bg-gray-50 transition-colors font-medium"
            >{{ i18n.__('user.photo_confirm_no') }}</button>
            <div class="w-px bg-gray-100" />
            <button
              @click="acceptConfirm"
              class="flex-1 py-4 text-sm text-indigo-600 hover:bg-indigo-50 transition-colors font-semibold"
            >{{ i18n.__('user.photo_confirm_yes') }}</button>
          </div>
        </div>

        <!-- Pose picker -->
        <div v-else>
          <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <h3 class="font-bold text-gray-900 text-base">{{ i18n.__('user.photo_modal_title') }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="grid grid-cols-3 gap-2 px-4 pb-5">
            <button
              v-for="pose in poses"
              :key="pose.key"
              @click="selectPose(pose.key)"
              class="flex flex-col items-center gap-1.5 py-4 px-2 rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all active:scale-95"
            >
              <span class="text-2xl">{{ pose.emoji }}</span>
              <span class="text-xs font-medium text-gray-700 text-center leading-tight">{{ i18n.__('user.' + pose.labelKey) }}</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18nStore } from '@/stores/i18n'

const props = defineProps({
  show: Boolean,
  imageCost: { type: Number, default: 5 },
})

const emit = defineEmits(['close', 'select'])

const i18n = useI18nStore()

const poses = [
  { key: 'seductive', emoji: '😏', labelKey: 'pose_seductive' },
  { key: 'lingerie',  emoji: '🩱', labelKey: 'pose_lingerie' },
  { key: 'bikini',    emoji: '👙', labelKey: 'pose_bikini' },
  { key: 'lying',     emoji: '🛌', labelKey: 'pose_lying' },
  { key: 'backpose',  emoji: '💋', labelKey: 'pose_backpose' },
  { key: 'boudoir',   emoji: '🌹', labelKey: 'pose_boudoir' },
]

const SESSION_KEY = 'photoConfirmed'
const showConfirm = ref(false)

watch(() => props.show, (val) => {
  if (val) {
    showConfirm.value = !sessionStorage.getItem(SESSION_KEY)
  }
})

function acceptConfirm() {
  sessionStorage.setItem(SESSION_KEY, '1')
  showConfirm.value = false
}

function selectPose(key) {
  emit('select', key)
}
</script>
