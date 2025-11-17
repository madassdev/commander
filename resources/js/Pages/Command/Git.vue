<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  git: { type: Object, default: () => ({}) },
  branch: { type: String, default: 'main' },
})

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'git' ? page.props.commandFeedback : null))

const form = useForm({
  action: 'status',
  branch: props.branch,
  master_password: '',
  confirm: false,
})

const submit = (action) => {
  form.action = action
  form.post(route('command.git.action'), {
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
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs font-semibold text-slate-400">Current branch</p>
        <p class="mt-2 text-2xl font-semibold text-white">{{ props.git.branch || 'Unknown' }}</p>
        <p class="text-xs text-slate-400 mt-1">HEAD: {{ props.git.head }}</p>
        <p class="text-xs text-slate-400 mt-1">Latest: {{ props.git.latest }}</p>
      </div>
      <div class="lg:col-span-2 rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-sm font-semibold text-white">Status</p>
        <pre class="mt-2 whitespace-pre-wrap text-xs text-amber-200">
{{ props.git.status || props.git.error || 'git status unavailable' }}</pre>
      </div>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-sm font-semibold text-white">Deploy actions</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Target branch</label>
          <input
            v-model="form.branch"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="form.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm before running git commands on the server.</span>
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
        <p v-if="form.errors.master_password" class="text-xs text-amber-300">{{ form.errors.master_password }}</p>
        <p v-if="form.errors.confirm" class="text-xs text-amber-300">{{ form.errors.confirm }}</p>
        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="flex-1 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white hover:bg-white/20 disabled:opacity-40"
            :disabled="form.processing"
            @click="submit('status')"
          >
            Refresh status
          </button>
          <button
            type="button"
            class="flex-1 rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
            :disabled="form.processing"
            @click="submit('fetch')"
          >
            Fetch
          </button>
          <button
            type="button"
            class="flex-1 rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
            :disabled="form.processing"
            @click="submit('pull')"
          >
            Pull origin/{{ form.branch || 'main' }}
          </button>
        </div>
      </form>
      <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-sm font-semibold text-white">Last output</p>
        <pre class="mt-2 max-h-72 overflow-y-auto whitespace-pre-wrap rounded-2xl bg-black/40 p-4 text-xs text-amber-200">
{{ feedback?.payload?.output || feedback?.payload?.error || 'Run a git action to view log.' }}</pre>
      </div>
    </section>
  </CommandLayout>
</template>
