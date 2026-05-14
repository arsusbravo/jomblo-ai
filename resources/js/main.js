import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './bootstrap'
import '../css/app.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

import { useAuthStore } from '@/stores/auth'
import { useI18nStore } from '@/stores/i18n'

// Global session-expiry handler: any 401 from a protected route logs the user out immediately
window.addEventListener('auth:expired', () => {
    const auth = useAuthStore()
    auth.user = null
    router.replace({ name: 'login' })
})

const i18n = useI18nStore()
i18n.init().then(() => app.mount('#app'))
