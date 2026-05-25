<template>
  <div class="flex flex-col h-[calc(100vh-3.5rem)]">

    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4 shrink-0">
      <h2 class="font-bold text-gray-900">{{ i18n.__('user.contact_title') }}</h2>
      <p class="text-xs text-gray-400 mt-0.5">{{ i18n.__('user.contact_subtitle') }}</p>
    </div>

    <!-- Messages -->
    <div ref="messagesEl" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
      <div v-if="loading" class="text-center text-gray-400 pt-12 text-sm">{{ i18n.__('user.chat_loading') }}</div>

      <template v-else>
        <div v-if="!messages.length" class="text-center text-gray-400 pt-12 text-sm">
          {{ i18n.__('user.contact_empty') }}
        </div>

        <div
          v-for="msg in messages"
          :key="msg.id"
          class="flex"
          :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'"
        >
          <!-- Support avatar -->
          <div v-if="msg.sender === 'admin'" class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center shrink-0 mr-2 mt-1">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>

          <div class="max-w-[75%] space-y-1">
            <div
              class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
              :class="msg.sender === 'user'
                ? 'bg-indigo-600 text-white rounded-br-sm'
                : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded-bl-sm'"
            >{{ msg.message }}</div>
            <p class="text-[10px] text-gray-400" :class="msg.sender === 'user' ? 'text-right' : 'text-left'">
              {{ formatTime(msg.created_at) }}
            </p>
          </div>
        </div>
      </template>
    </div>

    <!-- Input -->
    <div class="bg-white border-t border-gray-200 px-4 py-4 shrink-0">
      <form @submit.prevent="send" class="flex items-end gap-2">
        <textarea
          ref="inputEl"
          v-model="input"
          @keydown.enter.exact.prevent="send"
          rows="1"
          :placeholder="i18n.__('user.contact_message_placeholder')"
          :disabled="sending"
          class="flex-1 resize-none px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm disabled:opacity-60 max-h-32 overflow-y-auto"
          style="field-sizing: content"
        />
        <button
          type="submit"
          :disabled="!input.trim() || sending"
          class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
        >
          <svg class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </form>
      <p v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</p>
      <p class="text-xs text-gray-400 mt-1">{{ i18n.__('user.chat_hint') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import api from '@/api'
import { useI18nStore } from '@/stores/i18n'
import { useAuthStore } from '@/stores/auth'

const i18n = useI18nStore()
const auth = useAuthStore()

const messages = ref([])
const input = ref('')
const loading = ref(true)
const sending = ref(false)
const error = ref('')
const messagesEl = ref(null)
const inputEl = ref(null)

onMounted(async () => {
  auth.clearUnreadSupport()
  const { data } = await api.get('/api/user/contact')
  messages.value = data.messages
  loading.value = false
  await nextTick()
  scrollToBottom()
})

async function send() {
  const content = input.value.trim()
  if (!content || sending.value) return
  sending.value = true
  error.value = ''
  input.value = ''
  try {
    const { data } = await api.post('/api/user/contact', { message: content })
    messages.value.push(data.message)
    await nextTick()
    scrollToBottom()
  } catch (e) {
    error.value = e.response?.status === 429
      ? i18n.__('user.contact_too_many')
      : i18n.__('auth.error_generic')
    input.value = content
  } finally {
    sending.value = false
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      inputEl.value?.focus()
    }
  }
}

function scrollToBottom() {
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

function formatTime(ts) {
  return new Date(ts).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
