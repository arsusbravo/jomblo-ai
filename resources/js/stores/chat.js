import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useChatStore = defineStore('chat', () => {
  const conversationId = ref(null)
  const pendingClear = ref(false)
  return { conversationId, pendingClear }
})
