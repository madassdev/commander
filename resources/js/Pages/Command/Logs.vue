<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  defaultLines: { type: Number, default: 200 },
})

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'logs' ? page.props.commandFeedback : null))

const form = useForm({
  lines: props.defaultLines,
  master_password: '',
  confirm: false,
})

const submit = () => {
  form.post(route('command.logs.tail'), {
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
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submit">
        <p class="text-sm font-semibold text-white">Tail laravel.log</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Lines</label>
          <input
            v-model.number="form.lines"
            type="number"
            min="1"
            max="500"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
          <p v-if="form.errors.lines" class="mt-1 text-xs text-amber-300">{{ form.errors.lines }}</p>
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="form.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm before exposing log contents.</span>
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
        <button
          type="submit"
          class="w-full rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
          :disabled="form.processing"
        >
          Fetch logs
        </button>
      </form>
      <div class="lg:col-span-2 rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-sm font-semibold text-white">
          Viewing last {{ feedback?.payload?.lines || form.lines }} lines
        </p>
        <pre class="mt-3 max-h-[540px] overflow-y-auto whitespace-pre-wrap rounded-2xl bg-black/40 p-4 text-xs text-amber-200">
{{ feedback?.payload?.content || feedback?.payload?.error || 'Run the tail command to view logs.' }}</pre>
      </div>
    </div>
  </CommandLayout>
</template>
