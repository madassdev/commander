<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="flex min-h-screen flex-col lg:flex-row">
      <Transition name="fade">
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="sidebarOpen = false" />
      </Transition>
      <Transition name="slide">
        <div
          v-if="sidebarOpen"
          class="fixed inset-y-0 left-0 z-50 w-72 lg:hidden"
        >
          <CommandSidebar :current="commandMeta.current" @navigate="sidebarOpen = false" />
        </div>
      </Transition>
      <div class="hidden lg:block">
        <CommandSidebar :current="commandMeta.current" />
      </div>

      <div class="flex-1 flex flex-col">
        <header class="border-b border-white/5 bg-slate-950/90 backdrop-blur px-4 py-4 sm:px-6">
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="inline-flex lg:hidden h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-slate-200"
                @click="sidebarOpen = true"
              >
                <Menu class="h-5 w-5" />
              </button>
              <div>
                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Command Center</p>
                <h1 class="mt-1 text-2xl font-semibold text-white">{{ pageTitle }}</h1>
                <p class="text-sm text-slate-400" v-if="pageDescription">{{ pageDescription }}</p>
              </div>
            </div>
            <slot name="header-actions" />
          </div>
        </header>
        <main class="flex-1 overflow-y-auto bg-slate-950/95 px-4 py-8 sm:px-6 lg:px-8">
          <slot />
        </main>
      </div>
    </div>
    <FormFeedbackModal
      :open="showFeedbackModal"
      :type="feedbackModal.type"
      :title="feedbackModal.title"
      :message="feedbackModal.message"
      :summary="feedbackModal.summary"
      @close="showFeedbackModal = false"
    />
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import CommandSidebar from '@/Components/command/CommandSidebar.vue'
import FormFeedbackModal from '@/Components/ui/FormFeedbackModal.vue'
import { Menu } from 'lucide-vue-next'

const page = usePage()

const commandMeta = computed(() => page.props.meta || { current: 'overview' })
const pageTitle = computed(() => page.props.meta?.title || 'Command Center')
const pageDescription = computed(() => page.props.meta?.description || '')

const sidebarOpen = ref(false)

const flash = computed(() => page.props.flash ?? {})
const commandFeedback = computed(() => page.props.commandFeedback ?? null)
const showFeedbackModal = ref(false)
const feedbackModal = reactive({
  type: 'success',
  title: '',
  message: '',
  summary: [],
})

const contextTitles = {
  artisan: 'Artisan console',
  environment: 'Environment manager',
  sql: 'SQL console',
  logs: 'Log viewer',
  files: 'File editor',
  git: 'Git deploys',
  maintenance: 'Maintenance task',
  backups: 'Database backup',
  queues: 'Queues',
}

const buildSummary = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return []
  }
  return Object.entries(payload)
    .slice(0, 6)
    .map(([label, value]) => ({
      label,
      value: Array.isArray(value) || typeof value === 'object' ? JSON.stringify(value) : String(value ?? ''),
    }))
}

watch(
  () => ({ flash: flash.value, feedback: commandFeedback.value }),
  ({ flash, feedback }) => {
    if (flash?.success || flash?.error) {
      feedbackModal.type = feedback?.type ?? (flash.error ? 'error' : 'success')
      feedbackModal.title =
        contextTitles[feedback?.context] ?? (flash.error ? 'Action failed' : 'Action successful')
      feedbackModal.message = flash.success || flash.error
      feedbackModal.summary = buildSummary(feedback?.payload)
      showFeedbackModal.value = true
      return
    }
    showFeedbackModal.value = false
  },
  { immediate: true },
)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}
</style>
