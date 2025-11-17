<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'sql' ? page.props.commandFeedback : null))

const form = useForm({
  statement: '',
  master_password: '',
  confirm: false,
})

const submit = () => {
  form.post(route('command.sql.run'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('confirm')
      form.master_password = ''
    },
  })
}

const formattedResult = computed(() => {
  if (!feedback.value?.payload) return ''
  if (feedback.value.payload.error) return feedback.value.payload.error
  return JSON.stringify(feedback.value.payload.result, null, 2)
})
</script>

<template>
  <CommandLayout>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submit">
        <div>
          <label class="text-sm font-semibold text-slate-200">SQL statement</label>
          <textarea
            v-model="form.statement"
            rows="10"
            autocomplete="off"
            placeholder="select count(*) from users;"
            class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm font-mono text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          ></textarea>
          <p v-if="form.errors.statement" class="mt-1 text-xs text-amber-300">
            {{ form.errors.statement }}
          </p>
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="form.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Query runs directly on the primary database. Use readonly statements whenever possible.</span>
        </label>
        <div>
          <label class="text-sm font-semibold text-slate-200">Master password</label>
          <input
            v-model="form.master_password"
            type="password"
            autocomplete="off"
            class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="form.errors.master_password" class="text-xs text-amber-300">{{ form.errors.master_password }}</p>
        <p v-if="form.errors.confirm" class="text-xs text-amber-300">{{ form.errors.confirm }}</p>
        <button
          type="submit"
          class="rounded-2xl bg-amber-500 px-6 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
          :disabled="form.processing"
        >
          Run query
        </button>
      </form>
      <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-sm font-semibold text-white">Result</p>
        <pre class="mt-3 max-h-[500px] overflow-y-auto whitespace-pre-wrap rounded-2xl bg-black/40 p-4 text-xs text-amber-200">
{{ formattedResult || 'Awaiting execution...' }}</pre>
      </div>
    </div>
  </CommandLayout>
</template>
