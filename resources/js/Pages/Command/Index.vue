<script setup>
import CommandLayout from '@/Layouts/CommandLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
})
</script>

<template>
  <CommandLayout>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <article class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-black/30">
        <p class="text-xs uppercase tracking-wide text-slate-400">Environment variables</p>
        <p class="mt-3 text-4xl font-semibold text-white">{{ props.stats.env ?? 0 }}</p>
        <p class="text-sm text-slate-400 mt-1">keys detected inside .env</p>
        <Link
          href="/command/environment"
          class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-amber-300"
        >
          Manage variables
        </Link>
      </article>

      <article class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-black/30">
        <p class="text-xs uppercase tracking-wide text-slate-400">Editable paths</p>
        <p class="mt-3 text-4xl font-semibold text-white">{{ props.stats.fileRoots ?? 0 }}</p>
        <p class="text-sm text-slate-400 mt-1">roots enabled for quick edits</p>
        <Link
          href="/command/files"
          class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-amber-300"
        >
          Open file editor
        </Link>
      </article>

      <article class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-black/30">
        <p class="text-xs uppercase tracking-wide text-slate-400">Git status</p>
        <p class="mt-3 text-lg font-semibold text-white">
          {{ props.stats.git?.branch || 'Unknown branch' }}
        </p>
        <p class="text-sm text-slate-400 mt-1">{{ props.stats.git?.latest }}</p>
        <Link
          href="/command/git"
          class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-amber-300"
        >
          Manage deploys
        </Link>
      </article>
    </div>
    <section class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-amber-500/20 via-amber-500/10 to-transparent p-6">
        <h2 class="text-xl font-semibold text-white">Security reminders</h2>
        <ul class="mt-4 space-y-3 text-sm text-slate-200">
          <li>• Keep the master password offline and rotate frequently.</li>
          <li>• Every form requires explicit confirmation before we execute anything.</li>
          <li>• Actions are logged with timestamps for auditing.</li>
        </ul>
      </div>
      <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <h2 class="text-xl font-semibold text-white">Quick links</h2>
        <div class="mt-4 grid gap-3">
          <Link href="/command/artisan" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">Artisan console</Link>
          <Link href="/command/sql" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">SQL console</Link>
          <Link href="/command/logs" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">Live logs</Link>
          <Link href="/command/maintenance" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">Maintenance tasks</Link>
        </div>
      </div>
    </section>
  </CommandLayout>
</template>
