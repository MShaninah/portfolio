<template>
  <div class="sticky top-0 z-40 border-b border-white/10 bg-black/40 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
      <button
          @click="$emit('nav','home')"
          class="group home inline-flex items-center gap-2 rounded-xl px-2 py-1 text-sm font-medium text-white/90 hover:bg-white/5"
      >
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-white/10 bg-white/5 shadow-sm">
          <span class="text-xs font-semibold tracking-widest">MS</span>
        </span>
        <span class="tracking-tight">{{ $t('nav.home') }}</span>
      </button>

      <div class="hidden items-center gap-1 sm:flex">
        <button
            v-for="it in items"
            :key="it.id"
            class="rounded-xl px-3 py-2 text-sm text-white/80 hover:text-white hover:bg-white/5"
            @click="$emit('nav', it.id)"
        >
          {{ $t(it.i18nKey) }}
        </button>
      </div>

      <div class="flex items-center gap-2">
        <button
          class="inline-flex items-center justify-center rounded-xl text-white/80 hover:bg-white/5 hover:text-white"
          :aria-label="isLight ? 'Switch to dark mode' : 'Switch to light mode'"
          @click="$emit('toggle-theme')"
          title="Toggle theme"
        >
          <component :is="isLight ? Moon : Sun" class="h-4 w-4" />
        </button>
        <LanguageSwitch />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Sun, Moon } from "lucide-vue-next"
import LanguageSwitch from "./LanguageSwitch.vue"

const props = defineProps<{ isLight: boolean }>()

defineEmits<{ (e: "nav", id: string): void; (e: 'toggle-theme'): void }>()

const items = [
  { id: "about", i18nKey: "nav.about" },
  { id: "skills", i18nKey: "nav.skills" },
  { id: "projects", i18nKey: "nav.projects" },
  { id: "contact", i18nKey: "nav.contact" },
] as const
</script>

<style scoped>
.home {
  padding: 0.4em 1.2em;
}
</style>