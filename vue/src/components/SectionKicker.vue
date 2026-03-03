<template>
  <div class="space-y-2">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/70">
      <Sparkles class="h-4 w-4" />
      <span>{{ subtitle }}</span>
    </div>
    <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ title }}</h2>
  </div>
</template>

<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next'

defineProps<{ title: string; subtitle: string }>()
</script>
