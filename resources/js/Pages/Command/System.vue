<script setup>
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  info: { type: Object, default: () => ({}) },
})
</script>

<template>
  <CommandLayout>
    <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <article
        v-for="(value, key) in props.info"
        :key="key"
        class="rounded-3xl border border-white/10 bg-white/5 p-6"
      >
        <p class="text-xs uppercase tracking-wide text-slate-400">{{ key }}</p>
        <p class="mt-2 text-2xl font-semibold text-white">{{ value }}</p>
      </article>
    </section>
  </CommandLayout>
</template>
