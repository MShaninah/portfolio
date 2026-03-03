<template>
  <div class="mx-auto max-w-6xl px-4 py-10">
    <button class="btn-ghost mb-6" @click="$emit('back')">
      <ArrowLeft class="mr-2 h-4 w-4" /> Back
    </button>

    <div class="grid gap-6 lg:grid-cols-3">
      <GlassCard class="lg:col-span-2">
        <div class="p-6 space-y-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-xl font-semibold text-white sm:text-2xl">{{ project.title }}</div>
              <p class="mt-1 text-sm text-white/60">{{ project.tagline }}</p>
            </div>
            <span class="badge-secondary">{{ project.year }}</span>
          </div>

          <div class="h-px bg-white/10" />

          <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-white/60">Overview</div>
            <p class="mt-2 text-sm leading-relaxed text-white/75">{{ project.summary }}</p>
          </div>

          <div class="h-px bg-white/10" />

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <div class="text-xs font-semibold uppercase tracking-widest text-white/60">Key highlights</div>
              <ul class="mt-3 space-y-2 text-sm text-white/75">
                <li v-for="x in project.highlights" :key="x" class="flex gap-2">
                  <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-white/35" />
                  <span>{{ x }}</span>
                </li>
              </ul>
            </div>

            <div>
              <div class="text-xs font-semibold uppercase tracking-widest text-white/60">Stack</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="t in project.stack" :key="t" class="badge-outline">{{ t }}</span>
              </div>
            </div>
          </div>

          <div class="h-px bg-white/10" />

          <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-white/60">Add to this page</div>
            <div class="mt-3 grid gap-3">
              <div v-for="x in addList" :key="x" class="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-white/70">
                {{ x }}
              </div>
            </div>
          </div>
        </div>
      </GlassCard>

      <div class="space-y-6">
        <GlassCard>
          <div class="p-6 space-y-3">
            <div class="text-base font-semibold text-white">Links</div>
            <button v-for="l in project.links" :key="l.label" class="btn-outline w-full justify-between flex" @click="open(l.href)">
              <span class="inline-flex items-center gap-2">
                <component :is="l.icon || ArrowUpRight" class="h-4 w-4" /> {{ l.label }}
              </span>
              <ArrowUpRight class="h-4 w-4" />
            </button>
          </div>
        </GlassCard>

        <GlassCard>
          <div class="p-6 text-sm text-white/70">
            Add one strong visual per project. Keep the text short; let the proof (screenshots, links, metrics) do the work.
          </div>
        </GlassCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import GlassCard from './GlassCard.vue'
import { ArrowLeft, ArrowUpRight } from 'lucide-vue-next'

function open(href: string) { window.open(href, '_blank') }

interface Link { label: string; href: string; icon?: any }
interface Project {
  id: string
  title: string
  tagline: string
  summary: string
  highlights: string[]
  stack: string[]
  links: Link[]
  year: string
  category: 'Backend' | 'AI' | 'Enterprise' | 'IoT'
}

const addList = [
  '1–2 screenshots (or a short GIF) showing the UI/output',
  'A short architecture diagram (optional)',
  'A clear note on what’s public vs private (client work)',
  'Numbers if available (latency, cost, volume, stability)'
]

defineProps<{ project: Project }>()

defineEmits<{ (e: 'back'): void }>()
</script>
