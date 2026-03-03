import { createI18n } from "vue-i18n"
import { watch } from "vue"
import en from "./locales/en"
import de from "./locales/de"

export type Locale = "en" | "de"

const STORAGE_KEY = "portfolio_locale"

function safeGetSaved(): Locale | null {
    try {
        const v = localStorage.getItem(STORAGE_KEY)
        return v === "en" || v === "de" ? v : null
    } catch {
        return null
    }
}

function detectInitial(): Locale {
    const saved = safeGetSaved()
    if (saved) return saved
    return navigator.language?.toLowerCase().startsWith("de") ? "de" : "en"
}

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: detectInitial(),
    fallbackLocale: "en",
    messages: { en, de },
})

// Persist whenever locale changes (and set <html lang>)
watch(
    i18n.global.locale,
    (val) => {
        document.documentElement.lang = val as string
        try {
            localStorage.setItem(STORAGE_KEY, val as string)
        } catch {}
    },
    { immediate: true }
)

export function toggleLocale() {
    i18n.global.locale.value = i18n.global.locale.value === "en" ? "de" : "en"
}