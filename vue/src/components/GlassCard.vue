<template>
  <div class="rounded-2xl border border-white/10 bg-white/[0.04] shadow-sm" v-bind="$attrs">
    <slot />
  </div>
</template>

<script setup lang="ts">
// Root element inherits and merges all incoming classes/attrs automatically.
</script>
