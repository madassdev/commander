<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
})

const page = usePage()
const feedback = computed(() =>
  page.props.commandFeedback?.context === 'environment' ? page.props.commandFeedback : null,
)

const updateForm = useForm({
  key: props.entries[0]?.key || '',
  value: props.entries[0]?.value || '',
  master_password: '',
  confirm: false,
})

const createForm = useForm({
  key: '',
  value: '',
  master_password: '',
  confirm: false,
})

const submitUpdate = () => {
  updateForm.post(route('command.environment.upsert'), {
    preserveScroll: true,
    onSuccess: () => {
      updateForm.reset('confirm')
      updateForm.master_password = ''
    },
  })
}

const submitCreate = () => {
  createForm.post(route('command.environment.upsert'), {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset('key', 'value', 'confirm')
      createForm.master_password = ''
    },
  })
}

const applyEntry = (entry) => {
  updateForm.key = entry.key
  updateForm.value = entry.value
}
</script>

<template>
  <CommandLayout>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <section class="xl:col-span-2 space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6">
        <header class="flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-white">Existing variables</p>
            <p class="text-xs text-slate-400">Click a row to load the value into the editor.</p>
          </div>
        </header>
        <div class="max-h-[480px] overflow-y-auto rounded-2xl border border-white/5">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-white/5 text-slate-400">
              <tr>
                <th class="px-4 py-3 font-medium">Key</th>
                <th class="px-4 py-3 font-medium">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in props.entries"
                :key="entry.key"
                class="cursor-pointer border-b border-white/5 text-slate-200 hover:bg-white/5"
                @click="applyEntry(entry)"
              >
                <td class="px-4 py-2 font-semibold">{{ entry.key }}</td>
                <td class="px-4 py-2">
                  <span class="truncate block">{{ entry.value }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-8">
        <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitUpdate">
          <p class="text-sm font-semibold text-white">Edit variable</p>
          <div>
            <label class="text-xs font-semibold text-slate-300">Key</label>
            <select
              v-model="updateForm.key"
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            >
              <option v-for="entry in props.entries" :key="entry.key" :value="entry.key">
                {{ entry.key }}
              </option>
            </select>
            <p v-if="updateForm.errors.key" class="mt-1 text-xs text-amber-300">
              {{ updateForm.errors.key }}
            </p>
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-300">Value</label>
            <textarea
              v-model="updateForm.value"
              rows="3"
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            ></textarea>
            <p v-if="updateForm.errors.value" class="mt-1 text-xs text-amber-300">
              {{ updateForm.errors.value }}
            </p>
          </div>
          <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
            <input
              v-model="updateForm.confirm"
              type="checkbox"
              :true-value="true"
              :false-value="false"
              class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
            />
            <span>Writing to .env happens immediately and may require cache clears.</span>
          </label>
          <div>
            <label class="text-xs font-semibold text-slate-300">Master password</label>
            <input
              v-model="updateForm.master_password"
              type="password"
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            />
          </div>
          <p v-if="updateForm.errors.master_password" class="text-xs text-amber-300">
            {{ updateForm.errors.master_password }}
          </p>
          <p v-if="updateForm.errors.confirm" class="text-xs text-amber-300">
            {{ updateForm.errors.confirm }}
          </p>
          <button
            type="submit"
            class="w-full rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
            :disabled="updateForm.processing"
          >
            Save variable
          </button>
        </form>

        <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitCreate">
          <p class="text-sm font-semibold text-white">Add new variable</p>
          <div>
            <label class="text-xs font-semibold text-slate-300">Key</label>
            <input
              v-model="createForm.key"
              type="text"
              autocomplete="off"
              placeholder="NEW_KEY"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white uppercase focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            />
            <p v-if="createForm.errors.key" class="mt-1 text-xs text-amber-300">
              {{ createForm.errors.key }}
            </p>
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-300">Value</label>
            <textarea
              v-model="createForm.value"
              rows="2"
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            ></textarea>
            <p v-if="createForm.errors.value" class="mt-1 text-xs text-amber-300">
              {{ createForm.errors.value }}
            </p>
          </div>
          <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
            <input
              v-model="createForm.confirm"
              type="checkbox"
              :true-value="true"
              :false-value="false"
              class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
            />
            <span>Confirm creation of a new variable.</span>
          </label>
          <div>
            <label class="text-xs font-semibold text-slate-300">Master password</label>
            <input
              v-model="createForm.master_password"
              type="password"
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
            />
          </div>
          <p v-if="createForm.errors.master_password" class="text-xs text-amber-300">
            {{ createForm.errors.master_password }}
          </p>
          <p v-if="createForm.errors.confirm" class="text-xs text-amber-300">
            {{ createForm.errors.confirm }}
          </p>
          <button
            type="submit"
            class="w-full rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white hover:bg-white/20 disabled:opacity-40"
            :disabled="createForm.processing"
          >
            Create variable
          </button>
        </form>
        <div v-if="feedback" class="rounded-3xl border border-white/10 bg-slate-900/60 p-4 text-xs text-amber-200">
          Last action: {{ feedback?.payload?.key }} = {{ feedback?.payload?.value ?? '' }}
        </div>
      </section>
    </div>
  </CommandLayout>
</template>
