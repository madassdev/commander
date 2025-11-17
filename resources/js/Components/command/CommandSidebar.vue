<template>
  <aside class="w-64 lg:w-72 border-r border-white/10 bg-slate-950/80 backdrop-blur">
    <div class="px-6 py-6 border-b border-white/5">
      <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-2xl bg-amber-400/20 text-amber-300 flex items-center justify-center">
          <Sparkles class="h-5 w-5" />
        </div>
        <div>
          <p class="text-sm uppercase tracking-[0.35em] text-slate-400">Command</p>
          <p class="text-lg font-semibold text-white">Control Room</p>
        </div>
      </div>
    </div>
    <nav class="px-3 py-6 space-y-1">
      <Link
        v-for="item in navItems"
        :key="item.key"
        :href="item.href"
        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition"
        :class="item.key === current ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5'"
        @click="$emit('navigate')"
      >
        <component :is="item.icon" class="h-4 w-4" />
        <span>{{ item.label }}</span>
      </Link>
    </nav>
    <div class="px-6 pb-6 text-xs text-slate-500">
      Protected area. All actions require confirmation + master password.
    </div>
  </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import {
  Activity,
  BookMarked,
  Database,
  FileText,
  Hammer,
  Layers,
  Shield,
  Sparkles,
  Terminal,
  Wrench,
} from 'lucide-vue-next'

const props = defineProps({
  current: { type: String, default: 'overview' },
})

const emit = defineEmits(['navigate'])

const navItems = [
  { key: 'overview', label: 'Overview', href: '/command', icon: Activity },
  { key: 'artisan', label: 'Artisan console', href: '/command/artisan', icon: Terminal },
  { key: 'environment', label: 'Environment', href: '/command/environment', icon: Layers },
  { key: 'sql', label: 'SQL console', href: '/command/sql', icon: Database },
  { key: 'logs', label: 'Logs', href: '/command/logs', icon: FileText },
  { key: 'files', label: 'File editor', href: '/command/files', icon: BookMarked },
  { key: 'git', label: 'Deploys', href: '/command/git', icon: Hammer },
  { key: 'backups', label: 'DB Backups', href: '/command/backups', icon: Wrench },
  { key: 'queues', label: 'Queues', href: '/command/queues', icon: Activity },
  { key: 'maintenance', label: 'Maintenance', href: '/command/maintenance', icon: Wrench },
  { key: 'system', label: 'System info', href: '/command/system', icon: Shield },
]
</script>
