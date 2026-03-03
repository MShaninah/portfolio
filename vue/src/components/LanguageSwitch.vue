<template>
  <button class="rounded-xl border lang-switch border-white/15 bg-white/5 text-sm text-white/90 hover:bg-white/10"
          @click="toggle">
    {{ label }}
  </button>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { i18n, toggleLocale } from "../i18n"

const label = computed(() => (i18n.global.locale.value === "en" ? "DE" : "EN"))
const toggle = () => toggleLocale()
</script>

<style scoped>
.lang-switch {
  padding: 0.4em 1.2em;
}
</style>