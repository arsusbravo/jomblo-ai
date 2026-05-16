<template>
  <div>
    <div v-if="paymentBanner" :class="paymentBanner.ok
        ? 'bg-green-50 border-green-200 text-green-700'
        : 'bg-amber-50 border-amber-200 text-amber-700'"
      class="mx-4 mt-4 border text-sm px-4 py-3 rounded-lg">
      {{ paymentBanner.text }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!conversations.length" class="flex flex-col items-center justify-center py-24 px-6 text-center">
      <p class="text-6xl mb-4">💬</p>
      <p class="text-lg font-semibold text-gray-800">{{ i18n.__('user.dashboard_no_chats') }}</p>
      <p class="text-gray-500 mt-1">{{ i18n.__('user.dashboard_find_partner') }}</p>
      <RouterLink
        to="/dashboard/characters"
        class="mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-indigo-700 transition-colors"
      >{{ i18n.__('user.nav_find') }}</RouterLink>
    </div>

    <!-- Conversation list -->
    <div v-else>
      <div
        v-for="conv in conversations"
        :key="conv.id"
        @click="goToChat(conv.character.id)"
        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 active:bg-gray-100 cursor-pointer border-b border-gray-100 transition-colors"
      >
        <!-- Avatar -->
        <div
          class="w-14 h-14 rounded-full overflow-hidden shrink-0"
          :class="conv.character.gender === 'female' ? 'bg-pink-100' : 'bg-blue-100'"
        >
          <img
            v-if="conv.character.avatar_url"
            :src="conv.character.avatar_url"
            :alt="conv.character.name"
            class="w-full h-full object-cover"
          />
          <span v-else class="w-full h-full flex items-center justify-center text-2xl">
            {{ conv.character.gender === 'female' ? '👩' : '👨' }}
          </span>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-baseline mb-0.5">
            <p class="font-semibold text-gray-900">{{ conv.character.name }}</p>
            <p class="text-xs text-gray-400 shrink-0 ml-2">{{ timeAgo(conv.last_message?.created_at) }}</p>
          </div>
          <p class="text-sm text-gray-500 truncate">
            {{ conv.last_message?.content ?? '...' }}
          </p>
        </div>
      </div>

      <!-- Find more -->
      <RouterLink
        to="/dashboard/characters"
        class="flex items-center gap-3 px-4 py-4 text-indigo-600 hover:bg-indigo-50 transition-colors border-b border-gray-100"
      >
        <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </div>
        <p class="font-medium">{{ i18n.__('user.dashboard_find_more') }}</p>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import api from '@/api'
import { useI18nStore } from '@/stores/i18n'
import { useAuthStore } from '@/stores/auth'

const i18n = useI18nStore()
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const conversations = ref([])
const loading = ref(true)
const paymentBanner = ref(null)

onMounted(async () => {
  if (route.query.payment === 'success') {
    paymentBanner.value = { ok: true, text: i18n.__('user.payment_success') }
    await auth.fetchUser() // webhook already credited server-side — refresh local state
    router.replace({ query: {} })
  } else if (route.query.payment === 'cancelled') {
    paymentBanner.value = { ok: false, text: i18n.__('user.payment_cancelled') }
    router.replace({ query: {} })
  }

  try {
    const { data } = await api.get('/api/user/conversations')
    conversations.value = data.conversations
  } catch {
    // session expired — router guard will redirect on next navigation
  } finally {
    loading.value = false
  }
})

function goToChat(characterId) {
  router.push({ name: 'user.chat', params: { characterId } })
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000)
  if (diff < 60) return 'now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d`
  return new Date(dateStr).toLocaleDateString()
}
</script>
