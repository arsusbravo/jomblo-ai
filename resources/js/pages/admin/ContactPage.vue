<template>
  <div class="p-6 max-w-6xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Support messages</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      <!-- Thread list -->
      <div class="md:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="loadingList" class="p-6 text-sm text-gray-400 text-center">Loading...</div>
        <div v-else-if="!threads.length" class="p-6 text-sm text-gray-400 text-center">No messages yet.</div>
        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="t in threads"
            :key="t.id"
            @click="openThread(t)"
            class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
            :class="active?.id === t.id ? 'bg-indigo-50' : ''"
          >
            <div class="flex items-center justify-between mb-0.5">
              <p class="text-sm font-semibold text-gray-900 truncate">{{ t.name }}</p>
              <span v-if="t.unread_count" class="ml-2 shrink-0 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold">
                {{ t.unread_count > 9 ? '9+' : t.unread_count }}
              </span>
            </div>
            <p class="text-xs text-gray-400 truncate">{{ t.last_message }}</p>
          </li>
        </ul>
      </div>

      <!-- Conversation -->
      <div class="md:col-span-2 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" style="height: 600px;">
        <div v-if="!active" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
          Select a conversation
        </div>

        <template v-else>
          <!-- Conv header -->
          <div class="px-5 py-3 border-b border-gray-100 shrink-0">
            <p class="font-semibold text-gray-900">{{ active.name }}</p>
            <p class="text-xs text-gray-400">{{ active.email }}</p>
          </div>

          <!-- Messages -->
          <div ref="convEl" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
            <div v-if="loadingConv" class="text-center text-gray-400 text-sm pt-8">Loading...</div>
            <template v-else>
              <div
                v-for="msg in conversation"
                :key="msg.id"
                class="flex"
                :class="msg.sender === 'admin' ? 'justify-end' : 'justify-start'"
              >
                <div class="max-w-[75%] space-y-1">
                  <div
                    class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
                    :class="msg.sender === 'admin'
                      ? 'bg-indigo-600 text-white rounded-br-sm'
                      : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded-bl-sm'"
                  >{{ msg.message }}</div>
                  <p class="text-[10px] text-gray-400" :class="msg.sender === 'admin' ? 'text-right' : 'text-left'">
                    {{ formatTime(msg.created_at) }}
                  </p>
                </div>
              </div>
            </template>
          </div>

          <!-- Reply input -->
          <div class="px-4 py-3 border-t border-gray-100 shrink-0">
            <form @submit.prevent="reply" class="flex items-end gap-2">
              <textarea
                v-model="replyInput"
                @keydown.enter.exact.prevent="reply"
                rows="1"
                placeholder="Type a reply..."
                :disabled="replying"
                class="flex-1 resize-none px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60 max-h-28 overflow-y-auto"
                style="field-sizing: content"
              />
              <button
                type="submit"
                :disabled="!replyInput.trim() || replying"
                class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 transition-colors disabled:opacity-40 shrink-0"
              >
                <svg class="w-4 h-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </form>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import api from '@/api'

const threads = ref([])
const loadingList = ref(true)
const active = ref(null)
const conversation = ref([])
const loadingConv = ref(false)
const replyInput = ref('')
const replying = ref(false)
const convEl = ref(null)

onMounted(async () => {
  const { data } = await api.get('/api/admin/contact')
  threads.value = data.threads
  loadingList.value = false
})

async function openThread(thread) {
  active.value = thread
  loadingConv.value = true
  conversation.value = []
  const { data } = await api.get(`/api/admin/contact/${thread.id}`)
  conversation.value = data.messages
  // Clear unread badge on this thread locally
  thread.unread_count = 0
  loadingConv.value = false
  await nextTick()
  scrollConv()
}

async function reply() {
  const content = replyInput.value.trim()
  if (!content || replying.value) return
  replying.value = true
  replyInput.value = ''
  try {
    const { data } = await api.post(`/api/admin/contact/${active.value.id}`, { message: content })
    conversation.value.push(data.message)
    await nextTick()
    scrollConv()
  } finally {
    replying.value = false
  }
}

function scrollConv() {
  if (convEl.value) convEl.value.scrollTop = convEl.value.scrollHeight
}

function formatTime(ts) {
  return new Date(ts).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
