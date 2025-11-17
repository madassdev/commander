<script setup>
import { computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  backups: { type: Array, default: () => [] },
})

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'backups' ? page.props.commandFeedback : null))

const createForm = useForm({
  label: '',
  master_password: '',
  confirm: false,
})

const restoreForm = useForm({
  file: props.backups[0]?.filename || '',
  master_password: '',
  confirm: false,
})

const deleteForm = useForm({
  file: props.backups[0]?.filename || '',
  master_password: '',
  confirm: false,
})

const downloadForm = useForm({
  file: props.backups[0]?.filename || '',
  master_password: '',
  confirm: false,
})

const submitCreate = () => {
  createForm.post(route('command.backups.run'), {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset('label', 'confirm')
      createForm.master_password = ''
    },
  })
}

const submitRestore = () => {
  restoreForm.post(route('command.backups.restore'), {
    preserveScroll: true,
    onSuccess: () => {
      restoreForm.reset('confirm')
      restoreForm.master_password = ''
    },
  })
}

const submitDelete = () => {
  deleteForm.delete(route('command.backups.delete'), {
    preserveScroll: true,
    onSuccess: () => {
      deleteForm.reset('confirm')
      deleteForm.master_password = ''
    },
  })
}

const submitDownload = () => {
  downloadForm.clearErrors()
  if (!downloadForm.file) {
    downloadForm.setError('file', 'Select a backup')
    return
  }
  if (!downloadForm.confirm) {
    downloadForm.setError('confirm', 'Please confirm download')
    return
  }
  if (!downloadForm.master_password) {
    downloadForm.setError('master_password', 'Master password required')
    return
  }

  const formElement = document.createElement('form')
  formElement.method = 'POST'
  formElement.action = route('command.backups.download')
  formElement.target = '_blank'
  formElement.style.display = 'none'

  const csrfToken =
    document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

  const appendField = (name, value) => {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = name
    input.value = value
    formElement.appendChild(input)
  }

  appendField('_token', csrfToken)
  appendField('file', downloadForm.file)
  appendField('master_password', downloadForm.master_password)
  appendField('confirm', 'on')

  document.body.appendChild(formElement)
  formElement.submit()
  document.body.removeChild(formElement)

  downloadForm.reset('confirm')
  downloadForm.master_password = ''
}

const setDownloadFile = (filename) => {
  downloadForm.file = filename
}

const formatSize = (bytes) => {
  if (!bytes && bytes !== 0) return '—'
  if (bytes > 1e6) return `${(bytes / 1e6).toFixed(2)} MB`
  if (bytes > 1e3) return `${(bytes / 1e3).toFixed(2)} KB`
  return `${bytes} B`
}

const formatDate = (timestamp) => {
  if (!timestamp) return ''
  return new Date(timestamp * 1000).toLocaleString()
}

watch(
  () => props.backups,
  (value) => {
    const latest = value && value.length ? value[0].filename : ''
    if (!value || !value.length) {
      restoreForm.file = ''
      deleteForm.file = ''
      downloadForm.file = ''
      return
    }
    if (!restoreForm.file) restoreForm.file = latest
    if (!deleteForm.file) deleteForm.file = latest
    if (!downloadForm.file) downloadForm.file = latest
  },
  { deep: true, immediate: true },
)
</script>

<template>
  <CommandLayout>
    <section class="space-y-6 rounded-3xl border border-white/10 bg-white/5 p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold text-white">Existing backups</p>
          <p class="text-xs text-slate-400">Stored securely in the command-backups directory.</p>
        </div>
        <Link
          v-if="feedback?.payload?.file"
          :href="route('command.backups.download', { file: feedback.payload.file })"
          class="rounded-2xl border border-white/10 px-4 py-2 text-xs font-semibold text-amber-300"
        >
          Download last backup
        </Link>
      </div>
      <div class="overflow-x-auto rounded-2xl border border-white/10">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-white/5 text-slate-400 text-xs uppercase">
            <tr>
              <th class="px-4 py-3 font-medium">File</th>
              <th class="px-4 py-3 font-medium">Size</th>
              <th class="px-4 py-3 font-medium">Created</th>
              <th class="px-4 py-3 font-medium">Use</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="backup in props.backups" :key="backup.filename" class="border-b border-white/5 text-slate-200">
              <td class="px-4 py-3 font-semibold">{{ backup.filename }}</td>
              <td class="px-4 py-3">{{ formatSize(backup.size) }}</td>
              <td class="px-4 py-3">{{ formatDate(backup.created_at) }}</td>
              <td class="px-4 py-3">
                <button
                  type="button"
                  class="rounded-xl border border-white/10 px-3 py-1 text-xs text-amber-300"
                  @click="setDownloadFile(backup.filename)"
                >
                  Select
                </button>
              </td>
            </tr>
            <tr v-if="!props.backups.length">
              <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-500">
                No backups yet. Create one below.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitCreate">
        <p class="text-sm font-semibold text-white">Create backup</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Optional label</label>
          <input
            v-model="createForm.label"
            type="text"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
          <p v-if="createForm.errors.label" class="mt-1 text-xs text-amber-300">
            {{ createForm.errors.label }}
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
          <span>Confirm this will capture the production database.</span>
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
        <p v-if="createForm.errors.confirm" class="text-xs text-amber-300">{{ createForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-slate-900 disabled:opacity-40"
          :disabled="createForm.processing"
        >
          Create backup
        </button>
      </form>

      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitRestore">
        <p class="text-sm font-semibold text-white">Restore backup</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Backup file</label>
          <select
            v-model="restoreForm.file"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          >
            <option v-for="backup in props.backups" :key="backup.filename" :value="backup.filename">
              {{ backup.filename }}
            </option>
          </select>
          <p v-if="restoreForm.errors.file" class="mt-1 text-xs text-amber-300">
            {{ restoreForm.errors.file }}
          </p>
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="restoreForm.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm this will overwrite the current database.</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="restoreForm.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="restoreForm.errors.master_password" class="text-xs text-amber-300">
          {{ restoreForm.errors.master_password }}
        </p>
        <p v-if="restoreForm.errors.confirm" class="text-xs text-amber-300">{{ restoreForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
          :disabled="restoreForm.processing || !props.backups.length"
        >
          Restore backup
        </button>
      </form>

      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitDelete">
        <p class="text-sm font-semibold text-white">Delete backup</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Backup file</label>
          <select
            v-model="deleteForm.file"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          >
            <option v-for="backup in props.backups" :key="backup.filename" :value="backup.filename">
              {{ backup.filename }}
            </option>
          </select>
          <p v-if="deleteForm.errors.file" class="mt-1 text-xs text-amber-300">
            {{ deleteForm.errors.file }}
          </p>
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="deleteForm.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm permanent deletion.</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="deleteForm.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="deleteForm.errors.master_password" class="text-xs text-amber-300">
          {{ deleteForm.errors.master_password }}
        </p>
        <p v-if="deleteForm.errors.confirm" class="text-xs text-amber-300">{{ deleteForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-red-500/80 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
          :disabled="deleteForm.processing || !props.backups.length"
        >
          Delete backup
        </button>
      </form>
      <form autocomplete="off" class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="submitDownload">
        <p class="text-sm font-semibold text-white">Download backup</p>
        <div>
          <label class="text-xs font-semibold text-slate-300">Backup file</label>
          <select
            v-model="downloadForm.file"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          >
            <option v-for="backup in props.backups" :key="backup.filename" :value="backup.filename">
              {{ backup.filename }}
            </option>
          </select>
          <p v-if="downloadForm.errors.file" class="mt-1 text-xs text-amber-300">
            {{ downloadForm.errors.file }}
          </p>
        </div>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="downloadForm.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm download (opens in new tab).</span>
        </label>
        <div>
          <label class="text-xs font-semibold text-slate-300">Master password</label>
          <input
            v-model="downloadForm.master_password"
            type="password"
            autocomplete="off"
            class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          />
        </div>
        <p v-if="downloadForm.errors.master_password" class="text-xs text-amber-300">
          {{ downloadForm.errors.master_password }}
        </p>
        <p v-if="downloadForm.errors.confirm" class="text-xs text-amber-300">{{ downloadForm.errors.confirm }}</p>
        <button
          type="submit"
          class="w-full rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
          :disabled="downloadForm.processing || !props.backups.length"
        >
          Download selected
        </button>
      </form>
    </div>
  </CommandLayout>
</template>
