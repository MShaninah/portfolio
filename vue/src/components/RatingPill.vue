<template>
  <div class="fixed bottom-5 right-5 z-50">
    <div class="rounded-2xl border border-white/10 bg-black/55 backdrop-blur">
      <div class="flex items-center gap-3 p-3">
        <div class="text-xs text-white/70">Rate This Portfolio</div>
        <div class="flex items-center gap-1">
          <button v-for="n in 5" :key="n"
                  @click="rating = n"
                  :aria-label="`Rate ${n} stars`"
                  :class="['rounded-lg p-1 transition hover:bg-white/10', rating && n <= rating ? 'text-white' : 'text-white/35']">
            <Star class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Star } from 'lucide-vue-next'

const rating = ref<number | null>(null)
</script>
