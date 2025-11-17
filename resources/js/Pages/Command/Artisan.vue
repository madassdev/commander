<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const page = usePage()
const feedback = computed(() => page.props.commandFeedback?.context === 'artisan' ? page.props.commandFeedback : null)

const form = useForm({
  command: '',
  master_password: '',
  confirm: false,
})

const submit = () => {
  form.post(route('command.artisan.run'), {
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
    <section class="max-w-3xl space-y-6">
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submit">
        <div>
          <label class="text-sm font-semibold text-slate-200">Artisan command</label>
          <input
            v-model="form.command"
            type="text"
            autocomplete="off"
            placeholder="migrate --force"
            class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/40"
          />
          <p v-if="form.errors.command" class="mt-1 text-xs text-amber-300">
            {{ form.errors.command }}
          </p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
            <input
              v-model="form.confirm"
              type="checkbox"
              :true-value="true"
              :false-value="false"
              class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
            />
            <span>I confirm this command will execute against the live environment.</span>
          </label>
          <div>
            <label class="text-sm font-semibold text-slate-200">Master password</label>
            <input
              v-model="form.master_password"
              type="password"
              autocomplete="off"
              class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/40"
            />
            <p v-if="form.errors.master_password" class="mt-1 text-xs text-amber-300">
              {{ form.errors.master_password }}
            </p>
            <p v-if="form.errors.confirm" class="mt-1 text-xs text-amber-300">
              {{ form.errors.confirm }}
            </p>
          </div>
        </div>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-2xl bg-amber-500 px-6 py-3 text-sm font-semibold text-slate-900 shadow hover:bg-amber-400 disabled:opacity-40"
          :disabled="form.processing"
        >
          Execute
        </button>
      </form>

      <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <div class="flex items-center justify-between">
          <p class="text-sm font-semibold text-slate-200">Last command output</p>
          <Link href="/command/sql" class="text-xs text-amber-300">Need SQL instead?</Link>
        </div>
        <pre class="mt-3 max-h-64 overflow-y-auto whitespace-pre-wrap rounded-2xl bg-black/40 p-4 text-xs text-amber-200">
{{ feedback?.payload?.output || feedback?.payload?.error || 'No command executed yet.' }}</pre>
      </div>
    </section>
  </CommandLayout>
</template>
