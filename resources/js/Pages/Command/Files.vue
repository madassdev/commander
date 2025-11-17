<script setup>
import { computed, watch } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import CommandLayout from '@/Layouts/CommandLayout.vue'

const props = defineProps({
  filesIndex: { type: Array, default: () => [] },
  selectedPath: { type: String, default: '' },
  resolvedPath: { type: String, default: '' },
  fileContent: { type: String, default: '' },
})

const page = usePage()
const feedback = computed(() => (page.props.commandFeedback?.context === 'files' ? page.props.commandFeedback : null))

const form = useForm({
  path: props.selectedPath,
  content: props.fileContent,
  master_password: '',
  confirm: false,
})

watch(
  () => props.selectedPath,
  () => {
    form.path = props.selectedPath
    form.content = props.fileContent
    form.reset('confirm')
    form.master_password = ''
  },
)

const submit = () => {
  form.post(route('command.files.save'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('confirm')
      form.master_password = ''
    },
  })
}

const handleSelect = (event) => {
  const value = event.target.value
  if (!value) {
    router.visit(route('command.files'), { preserveScroll: true })
    return
  }
  router.visit(route('command.files', { path: value }), { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <CommandLayout>
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <div class="lg:col-span-4 rounded-3xl border border-white/10 bg-white/5 p-6">
        <label class="text-xs font-semibold text-slate-300">Choose file</label>
        <select
          :value="props.selectedPath"
          autocomplete="off"
          class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
          @change="handleSelect"
        >
          <option value="">Select file to edit</option>
          <optgroup v-for="group in props.filesIndex" :key="group.group" :label="group.group">
            <option v-for="file in group.files" :key="file.path" :value="file.relative">
              {{ file.relative }}
            </option>
          </optgroup>
        </select>
        <p class="mt-3 text-xs text-slate-400">
          Only files inside the approved directories are available. Use the environment manager for .env changes.
        </p>
      </div>

      <form
        autocomplete="off"
        class="lg:col-span-8 space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6"
        @submit.prevent="submit"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-white">
              {{ props.resolvedPath || 'Select a file to begin editing' }}
            </p>
            <p class="text-xs text-slate-400" v-if="props.resolvedPath">Changes apply immediately on save.</p>
          </div>
          <button
            type="submit"
            class="rounded-2xl bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 disabled:opacity-40"
            :disabled="!props.resolvedPath || form.processing"
          >
            Save file
          </button>
        </div>
        <textarea
          v-model="form.content"
          :disabled="!props.resolvedPath"
          rows="20"
          autocomplete="off"
          class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 font-mono text-xs text-white focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40 disabled:opacity-40"
        ></textarea>
        <label class="flex items-start gap-2 rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-xs text-slate-200">
          <input
            v-model="form.confirm"
            type="checkbox"
            :true-value="true"
            :false-value="false"
            class="mt-1 rounded border-slate-400 text-amber-400 focus:ring-amber-400"
          />
          <span>Confirm that you want to overwrite this file with the contents above.</span>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="text-xs font-semibold text-slate-300">Relative path</label>
            <input
              v-model="form.path"
              type="text"
              readonly
              autocomplete="off"
              class="mt-1 w-full rounded-2xl border border-white/10 bg-slate-900/40 px-4 py-3 text-xs text-slate-400"
            />
          </div>
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
        <p v-if="form.errors.content" class="text-xs text-amber-300">{{ form.errors.content }}</p>
        <p v-if="feedback" class="text-xs text-amber-200">
          {{ feedback?.payload?.path || form.path }} saved successfully.
        </p>
      </form>
    </section>
  </CommandLayout>
</template>
