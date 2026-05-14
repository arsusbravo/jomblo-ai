import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

const SUPPORTED = ['en', 'nl', 'fr', 'de']
const STORAGE_KEY = 'jomblo_locale'

export const useI18nStore = defineStore('i18n', () => {
    const locale = ref(localStorage.getItem(STORAGE_KEY) || 'nl')
    const translations = ref({})
    const loaded = ref(false)

    async function setLocale(newLocale) {
        if (!SUPPORTED.includes(newLocale)) return
        locale.value = newLocale
        localStorage.setItem(STORAGE_KEY, newLocale)
        await loadTranslations(newLocale)
    }

    async function loadTranslations(loc) {
        try {
            const { data } = await api.get(`/api/lang/${loc}`)
            translations.value = data
            loaded.value = true
        } catch {
            translations.value = {}
            loaded.value = true
        }
    }

    async function init() {
        await loadTranslations(locale.value)
    }

    function __(key, replacements = {}) {
        let text = translations.value[key] ?? key
        for (const [k, v] of Object.entries(replacements)) {
            text = text.replace(`:${k}`, v)
        }
        return text
    }

    return { locale, translations, loaded, setLocale, init, __ }
})
