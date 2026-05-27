<template>
  <div class="relative flex flex-col h-[calc(100vh-3.5rem)] overflow-hidden">

    <!-- Background — covers entire chat container, never scrolls -->
    <div class="absolute inset-0 pointer-events-none">
      <img v-if="character?.avatar_url" :src="character.avatar_url" class="w-full h-full object-cover" />
      <div v-if="character?.avatar_url" class="absolute inset-0 bg-white/30 backdrop-blur-[3px]" />
      <div v-else class="w-full h-full bg-gray-50" />
    </div>

    <!-- Header -->
    <div class="relative bg-white/80 backdrop-blur-md border-b border-gray-200 px-6 py-4 flex items-center gap-4 shrink-0">
      <button @click="router.push({ name: 'user.characters' })" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <div v-if="character" class="flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-full overflow-hidden shrink-0 cursor-pointer ring-2 ring-transparent hover:ring-indigo-400 transition-all"
          :class="character.gender === 'female' ? 'bg-pink-100' : 'bg-blue-100'"
          @click="character.avatar_url && (selectedPhoto = null, photoOpen = true)"
        >
          <img v-if="character.avatar_url" :src="character.avatar_url" :alt="character.name" class="w-full h-full object-cover" />
          <span v-else class="w-full h-full flex items-center justify-center text-xl">
            {{ character.gender === 'female' ? '👩' : '👨' }}
          </span>
        </div>
        <div>
          <p class="font-semibold text-gray-900">{{ character.name }}</p>
          <p class="text-xs text-pink-400">{{ character.gender === 'female' ? i18n.__('user.chat_honey') : i18n.__('user.chat_babe') }}</p>
        </div>
      </div>
      <div v-else class="h-5 w-32 bg-gray-100 rounded animate-pulse" />

    </div>

    <!-- Messages -->
    <div ref="messagesEl" class="relative flex-1 overflow-y-auto px-6 py-4 space-y-4">
      <div class="space-y-4">
        <div v-if="loadingConversation" class="text-center text-gray-400 pt-8">{{ i18n.__('user.chat_loading') }}</div>

        <template v-else>
          <!-- Opening card -->
          <div v-if="!messages.length" class="flex justify-center pt-8">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg px-8 py-6 text-center max-w-xs">
              <p class="text-5xl mb-4">{{ character?.gender === 'female' ? '👩' : '👨' }}</p>
              <p class="font-semibold text-gray-800 text-base">{{ i18n.__('user.chat_say_hi', { name: character?.name }) }}</p>
              <p class="text-sm text-gray-500 mt-1">{{ i18n.__('user.chat_start') }}</p>
            </div>
          </div>

          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex"
            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <!-- AI avatar -->
            <div v-if="msg.role === 'assistant'" class="w-8 h-8 rounded-full overflow-hidden shrink-0 mr-2 mt-1"
              :class="character?.gender === 'female' ? 'bg-pink-100' : 'bg-blue-100'"
            >
              <img v-if="character?.avatar_url" :src="character.avatar_url" :alt="character?.name" class="w-full h-full object-cover" />
              <span v-else class="w-full h-full flex items-center justify-center text-sm">
                {{ character?.gender === 'female' ? '👩' : '👨' }}
              </span>
            </div>

            <!-- Image bubble -->
            <img
              v-if="msg.type === 'image'"
              :src="msg.content"
              class="max-w-[70%] rounded-2xl rounded-bl-sm shadow-sm cursor-pointer object-cover"
              style="max-height: 320px"
              @click="selectedPhoto = msg.content; photoOpen = true"
            />

            <!-- Text bubble -->
            <div
              v-else
              class="max-w-[70%] px-4 py-3 rounded-2xl text-sm leading-relaxed"
              :class="msg.role === 'user'
                ? 'bg-indigo-600 text-white rounded-br-sm'
                : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded-bl-sm'"
            >
              {{ msg.content }}
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="sending" class="flex justify-start">
            <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 mr-2 mt-1"
              :class="character?.gender === 'female' ? 'bg-pink-100' : 'bg-blue-100'"
            >
              <img v-if="character?.avatar_url" :src="character.avatar_url" class="w-full h-full object-cover" />
              <span v-else class="w-full h-full flex items-center justify-center text-sm">
                {{ character?.gender === 'female' ? '👩' : '👨' }}
              </span>
            </div>
            <div class="space-y-1">
              <div class="bg-white border border-gray-100 shadow-sm px-4 py-3 rounded-2xl rounded-bl-sm flex items-center gap-1">
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
              </div>
              <p v-if="generatingPhoto" class="text-[10px] text-gray-400 pl-1">{{ i18n.__('user.photo_generating') }}</p>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Input -->
    <div class="relative bg-white/80 backdrop-blur-md border-t border-gray-200 px-4 py-4 shrink-0">
      <!-- Emoji popover -->
      <div
        v-if="showEmojis"
        ref="emojiPopover"
        class="absolute bottom-full left-4 mb-2 w-72 max-h-72 overflow-y-auto bg-white border border-gray-200 rounded-2xl shadow-xl p-3 z-10"
      >
        <div v-for="group in emojiGroups" :key="group.label" class="mb-2 last:mb-0">
          <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1 px-1">{{ group.label }}</p>
          <div class="grid grid-cols-8 gap-1">
            <button
              v-for="e in group.emojis"
              :key="e"
              type="button"
              @click="insertEmoji(e)"
              class="text-xl w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100 transition-colors"
            >{{ e }}</button>
          </div>
        </div>
      </div>

      <form @submit.prevent="sendMessage" class="flex items-end gap-2">
        <!-- Photo request button -->
        <button
          type="button"
          @click="showPhotoModal = true"
          :disabled="sending || creditsRemaining === 0 || !character?.avatar_url"
          class="w-11 h-11 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
          :title="i18n.__('user.chat_photo_btn')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>

        <button
          type="button"
          @click.stop="toggleEmojis"
          :disabled="sending || creditsRemaining === 0"
          class="w-11 h-11 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
          aria-label="Insert emoji"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </button>
        <textarea
          ref="inputEl"
          v-model="input"
          @keydown.enter.exact.prevent="sendMessage"
          rows="1"
          :placeholder="creditsRemaining === 0 ? i18n.__('user.credits_empty') : i18n.__('user.chat_placeholder')"
          :disabled="sending || creditsRemaining === 0"
          class="flex-1 resize-none px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm disabled:opacity-60 max-h-32 overflow-y-auto"
          style="field-sizing: content"
          @click="creditsRemaining === 0 && (showPricing = true)"
        />
        <button
          type="submit"
          :disabled="!input.trim() || sending || creditsRemaining === 0"
          class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
        >
          <svg class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </form>
      <div class="flex items-center justify-between mt-2 px-1">
        <p class="text-xs text-gray-400">{{ i18n.__('user.chat_hint') }}</p>
        <p class="text-xs font-medium" :class="creditsColor">
          {{ creditsRemaining === 0
            ? i18n.__('user.credits_empty')
            : i18n.__('user.credits_remaining', { count: creditsRemaining }) }}
        </p>
      </div>
    </div>

    <!-- Pricing modal -->
    <PricingModal :show="showPricing" @close="showPricing = false" />

    <!-- Photo request modal -->
    <PhotoRequestModal
      :show="showPhotoModal"
      :image-cost="imageCost"
      @close="showPhotoModal = false"
      @select="requestPhoto"
    />

    <!-- Photo lightbox (avatar or generated image) -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="photoOpen"
        class="absolute inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-6"
        @click="photoOpen = false; selectedPhoto = null"
      >
        <img
          :src="selectedPhoto ?? character?.avatar_url"
          :alt="character?.name"
          class="max-h-full max-w-full rounded-2xl shadow-2xl object-contain"
          @click.stop
        />
        <button
          @click="photoOpen = false; selectedPhoto = null"
          class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import { useI18nStore } from '@/stores/i18n'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chat'
import PricingModal from '@/components/PricingModal.vue'
import PhotoRequestModal from '@/components/PhotoRequestModal.vue'

const route = useRoute()
const router = useRouter()
const i18n = useI18nStore()
const auth = useAuthStore()
const chatStore = useChatStore()

const character = ref(null)
const conversation = ref(null)
const messages = ref([])
const input = ref('')
const inputEl = ref(null)
const showEmojis = ref(false)
const emojiPopover = ref(null)

const emojiGroups = [
  { label: 'Smileys', emojis: ['😀','😁','😂','🤣','😊','😍','🥰','😘','😉','😎','🤔','😏','😴','🤤','😋','😜','🤩','🥺','😢','😭','😳','😡','🙄','😬'] },
  { label: 'Hearts',  emojis: ['❤️','🧡','💛','💚','💙','💜','🤎','🖤','🤍','💕','💖','💗','💓','💞','💝','💘','💌','💔','❣️','💋'] },
  { label: 'Hands',   emojis: ['👍','👎','👏','🙏','🤝','✋','👋','🤗','💪','✌️','🤞','👌','🤘','🙌','🤲','👀'] },
  { label: 'Vibes',   emojis: ['🔥','✨','🎉','🌹','🌺','💯','⭐','🌙','☀️','🌈','☕','🍷','🍸','🥂','🍫','🍓'] },
]

function toggleEmojis() {
  showEmojis.value = !showEmojis.value
}

function insertEmoji(emoji) {
  const el = inputEl.value
  if (!el) {
    input.value += emoji
    return
  }
  const start = el.selectionStart ?? input.value.length
  const end   = el.selectionEnd   ?? input.value.length
  input.value = input.value.slice(0, start) + emoji + input.value.slice(end)
  nextTick(() => {
    el.focus()
    const pos = start + emoji.length
    el.setSelectionRange(pos, pos)
  })
}

function onDocMousedown(e) {
  if (showEmojis.value && emojiPopover.value && !emojiPopover.value.contains(e.target)
      && !e.target.closest('[aria-label="Insert emoji"]')) {
    showEmojis.value = false
  }
}
const sending = ref(false)
const generatingPhoto = ref(false)
const showPhotoModal = ref(false)
const clearing = ref(false)
const loadingConversation = ref(true)
const messagesEl = ref(null)
const photoOpen = ref(false)
const selectedPhoto = ref(null)
const showPricing = ref(false)
const imageCost = 5

const creditsRemaining = ref(auth.user?.message_credits ?? 0)

watch(creditsRemaining, (val) => {
  if (val === 0) showPricing.value = true
}, { immediate: true })

const creditsColor = computed(() => {
  if (creditsRemaining.value === 0) return 'text-red-500'
  if (creditsRemaining.value <= 5) return 'text-orange-500'
  return 'text-gray-400'
})

onMounted(async () => {
  try {
    const { data } = await api.get(`/api/user/conversations/${route.params.characterId}`)
    conversation.value = data.conversation
    character.value = data.conversation.character
    messages.value = data.conversation.messages
    chatStore.conversationId = data.conversation.id
  } finally {
    loadingConversation.value = false
    await nextTick()
    scrollToBottom()
  }
  document.addEventListener('mousedown', onDocMousedown)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocMousedown)
  chatStore.conversationId = null
})

watch(() => chatStore.pendingClear, async (val) => {
  if (val) {
    chatStore.pendingClear = false
    await clearChat()
  }
})

async function sendMessage() {
  const content = input.value.trim()
  if (!content || sending.value) return

  if (creditsRemaining.value === 0) {
    showPricing.value = true
    return
  }

  input.value = ''
  sending.value = true
  showEmojis.value = false

  messages.value.push({ id: Date.now(), role: 'user', content })
  await nextTick()
  scrollToBottom()

  try {
    const { data } = await api.post(
      `/api/user/conversations/${conversation.value.id}/messages`,
      { content }
    )
    const idx = messages.value.findLastIndex(m => m.role === 'user')
    if (idx !== -1) messages.value[idx] = data.user_message

    const parts = data.ai_messages
    for (let i = 0; i < parts.length; i++) {
      if (i > 0) await typingDelay(parts[i - 1].content)
      messages.value.push(parts[i])
      await nextTick()
      scrollToBottom()
    }

    creditsRemaining.value = data.credits_remaining
    if (auth.user) auth.user.message_credits = data.credits_remaining
  } catch (e) {
    if (e.response?.status === 402) {
      creditsRemaining.value = 0
      if (auth.user) auth.user.message_credits = 0
      showPricing.value = true
    }
    messages.value.pop()
    input.value = content
  } finally {
    sending.value = false
    await nextTick()
    scrollToBottom()
    // On desktop (mouse/trackpad) refocus the input so the user can keep typing.
    // On mobile/tablet we skip this — auto-focus pops the keyboard and hides the reply.
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      inputEl.value?.focus()
    }
  }
}

async function clearChat() {
  if (clearing.value) return
  clearing.value = true
  try {
    await api.delete(`/api/user/conversations/${conversation.value.id}/messages`)
    messages.value = []
  } finally {
    clearing.value = false
  }
}

async function requestPhoto(pose) {
  showPhotoModal.value = false
  if (sending.value) return

  sending.value = true
  generatingPhoto.value = true
  await nextTick()
  scrollToBottom()

  try {
    const { data } = await api.post(
      `/api/user/conversations/${conversation.value.id}/images`,
      { pose }
    )
    messages.value.push(data.image_message)
    creditsRemaining.value = data.credits_remaining
    if (auth.user) auth.user.message_credits = data.credits_remaining
    await nextTick()
    scrollToBottom()
  } catch (e) {
    if (e.response?.status === 402) {
      creditsRemaining.value = 0
      if (auth.user) auth.user.message_credits = 0
      showPricing.value = true
    }
  } finally {
    sending.value = false
    generatingPhoto.value = false
  }
}

function scrollToBottom() {
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

function typingDelay(prevText) {
  const words = prevText.trim().split(/\s+/).length
  const ms = Math.min(5000, Math.max(1500, words * 150))
  return new Promise(resolve => setTimeout(resolve, ms))
}
</script>
