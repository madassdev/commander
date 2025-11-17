<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  failedJobs: { type: Array, default: () => [] },
})

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'queues' ? page.props.commandFeedback : null))

const flushFailedForm = useForm({
  master_password: '',
  confirm: false,
})

const clearPendingForm = useForm({
  master_password: '',
  confirm: false,
})

const submitFlushFailed = () => {
  flushFailedForm.post(route('command.queues.flush-failed'), {
    preserveScroll: true,
    onSuccess: () => {
      flushFailedForm.reset('confirm')
      flushFailedForm.master_password = ''
    },
  })
}

const submitClearPending = () => {
  clearPendingForm.post(route('command.queues.clear-pending'), {
    preserveScroll: true,
    onSuccess: () => {
      clearPendingForm.reset('confirm')
      clearPendingForm.master_password = ''
    },
  })
}
</script>

<template>
  <CommandLayout>
    <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Queued jobs</p>
        <p class="mt-2 text-4xl font-semibold text-white">{{ props.stats.queued ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Jobs waiting inside the default table</p>
      </article>
      <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Failed jobs</p>
        <p class="mt-2 text-4xl font-semibold text-white">{{ props.stats.failed ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Latest 10 failures listed below</p>
      </article>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitFlushFailed">
        <p class="text-sm font-semibold text-white">Flush failed jobs</p>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="flushFailedForm.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm removal of every failed job record.</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="flushFailedForm.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="flushFailedForm.errors.master_password" class="text-xs text-amber-300">
          {{ flushFailedForm.errors.master_password }}
        </p>
        <p v-if="flushFailedForm.errors.confirm" class="text-xs text-amber-300">{{ flushFailedForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-red-500/80 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
          :disabled="flushFailedForm.processing"
        >
          Flush failed jobs
        </button>
      </form>

      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitClearPending">
        <p class="text-sm font-semibold text-white">Clear pending jobs</p>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="clearPendingForm.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm truncating the jobs table.</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="clearPendingForm.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="clearPendingForm.errors.master_password" class="text-xs text-amber-300">
          {{ clearPendingForm.errors.master_password }}
        </p>
        <p v-if="clearPendingForm.errors.confirm" class="text-xs text-amber-300">{{ clearPendingForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
          :disabled="clearPendingForm.processing"
        >
          Clear pending jobs
        </button>
      </form>
    </section>

    <section class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-6">
      <p class="text-sm font-semibold text-white">Recent failed jobs</p>
      <div class="mt-4 overflow-x-auto rounded-2xl border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-white/5 text-xs uppercase text-slate-400">
            <tr>
              <th class="px-4 py-3 font-medium">ID</th>
              <th class="px-4 py-3 font-medium">Queue</th>
              <th class="px-4 py-3 font-medium">Connection</th>
              <th class="px-4 py-3 font-medium">Failed at</th>
              <th class="px-4 py-3 font-medium">Exception</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="job in props.failedJobs"
              :key="job.id"
              class="border-b border-white/5 text-slate-200"
            >
              <td class="px-4 py-3">{{ job.id }}</td>
              <td class="px-4 py-3">{{ job.queue }}</td>
              <td class="px-4 py-3">{{ job.connection }}</td>
              <td class="px-4 py-3">{{ job.failed_at }}</td>
              <td class="px-4 py-3 text-xs text-slate-400">
                {{ job.exception }}
              </td>
            </tr>
            <tr v-if="!props.failedJobs.length">
              <td colspan="5" class="px-4 py-6 text-center text-xs text-slate-400">
                No failed jobs recorded recently.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="feedback" class="mt-4 text-xs text-amber-200">
        {{ feedback.payload?.message ?? 'Queue action completed.' }}
      </p>
    </section>
  </CommandLayout>
</template>
