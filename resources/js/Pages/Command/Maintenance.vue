<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const tasks = [
  { key: 'cache-clear', label: 'Cache clear', description: 'Flush application cache' },
  { key: 'config-clear', label: 'Config clear', description: 'Reload config from files' },
  { key: 'config-cache', label: 'Config cache', description: 'Cache config for performance' },
  { key: 'queue-restart', label: 'Queue restart', description: 'Restart workers gracefully' },
  { key: 'schedule-run', label: 'Run scheduler', description: 'Execute scheduled commands' },
  { key: 'down', label: 'Maintenance mode', description: 'Put application into maintenance' },
  { key: 'up', label: 'Resume mode', description: 'Bring app back up' },
]

const page = usePage()
const feedback = computed(() =>
  page.props.commandFeedback?.context === 'maintenance' ? page.props.commandFeedback : null,
)

const form = useForm({
  task: '',
  master_password: '',
  confirm: false,
})

const submit = (task) => {
  form.task = task
  form.post(route('command.maintenance.run'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('confirm')
      form.master_password = ''
    },
  })
}
</script>

<template>
  <CommandLayout>
    <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6">
      <div class="grid gap-4 md:grid-cols-2">
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="form.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>I understand these commands may interrupt live traffic.</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="form.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
      </div>
      <p v-if="form.errors.master_password" class="text-xs text-amber-300">
        {{ form.errors.master_password }}
      </p>
      <p v-if="form.errors.confirm" class="text-xs text-amber-300">{{ form.errors.confirm }}</p>
      <div class="grid gap-4 md:grid-cols-2">
        <button
          v-for="task in tasks"
          :key="task.key"
          type="button"
          class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-left text-sm text-white hover:bg-white/10 disabled:opacity-40"
          :disabled="form.processing"
          @click="submit(task.key)"
        >
          <p class="font-semibold">{{ task.label }}</p>
          <p class="text-xs text-slate-400">{{ task.description }}</p>
        </button>
      </div>
    </form>
    <div v-if="feedback" class="mt-6 rounded-3xl border border-white/10 bg-slate-900/60 p-4 text-xs text-amber-200">
      {{ feedback?.payload?.task }}: {{ feedback?.payload?.output || feedback?.payload?.error || 'Executed' }}
    </div>
  </CommandLayout>
</template>
