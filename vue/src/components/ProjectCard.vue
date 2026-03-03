<template>
  <GlassCard class="group overflow-hidden">
    <div class="relative">
      <div class="absolute inset-0 bg-[radial-gradient(500px_circle_at_20%_0%,rgba(99,102,241,0.22),transparent_55%),radial-gradient(500px_circle_at_80%_20%,rgba(16,185,129,0.14),transparent_55%)] opacity-0 transition group-hover:opacity-100" />
      <div class="relative p-6 space-y-2">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-base font-semibold text-white sm:text-lg">{{ p.title }}</div>
            <p class="mt-1 text-sm text-white/60">{{ p.tagline }}</p>
          </div>
          <span class="badge-secondary">{{ p.category }}</span>
        </div>
      </div>
    </div>

    <div class="px-6 pb-6 space-y-4">
      <p class="text-sm leading-relaxed text-white/70">{{ p.summary }}</p>

      <div class="flex flex-wrap gap-2">
        <span v-for="t in p.stack" :key="t" class="badge-outline">{{ t }}</span>
      </div>

      <div class="flex items-center justify-between">
        <button class="btn-outline" @click="$emit('open', p.id)">
          View details <ArrowUpRight class="ml-2 h-4 w-4" />
        </button>

        <div class="flex items-center gap-2">
          <button v-for="l in p.links" :key="l.label" class="btn-icon" :aria-label="l.label" @click="open(l.href)">
            <component :is="l.icon || ArrowUpRight" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </GlassCard>
</template>

<script setup lang="ts">
import GlassCard from './GlassCard.vue'
import { ArrowUpRight } from 'lucide-vue-next'

function open(href: string) { window.open(href, '_blank') }

interface Link { label: string; href: string; icon?: any }
interface Project {
  id: string
  title: string
  tagline: string
  summary: string
  stack: string[]
  links: Link[]
  year: string
  category: 'Backend' | 'AI' | 'Enterprise' | 'IoT'
}

defineProps<{ p: Project }>()

defineEmits<{ (e: 'open', id: string): void }>()
</script>
