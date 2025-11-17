<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'
import { CheckCircle2, AlertTriangle, Info } from 'lucide-vue-next'

const props = defineProps({
  open: { type: Boolean, default: false },
  type: { type: String, default: 'success' }, // success | error | info
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  summary: { type: Array, default: () => [] },
})
const emit = defineEmits(['close'])

const handleClose = () => emit('close')

let listenerAttached = false
const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    handleClose()
  }
}

const attachKeyListener = () => {
  if (!listenerAttached) {
    document.addEventListener('keydown', handleKeydown)
    listenerAttached = true
  }
}

const detachKeyListener = () => {
  if (listenerAttached) {
    document.removeEventListener('keydown', handleKeydown)
    listenerAttached = false
  }
}

watch(
  () => props.open,
  (value) => {
    if (value) {
      attachKeyListener()
    } else {
      detachKeyListener()
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  detachKeyListener()
})

const icon = computed(() => {
  if (props.type === 'error') return AlertTriangle
  if (props.type === 'info') return Info
  return CheckCircle2
})

const accentClass = computed(() => {
  switch (props.type) {
    case 'error':
      return 'text-red-600'
    case 'info':
      return 'text-sky-600'
    default:
      return 'text-emerald-600'
  }
})

const glowClass = computed(() => {
  switch (props.type) {
    case 'error':
      return 'from-red-100 to-transparent'
    case 'info':
      return 'from-sky-100 to-transparent'
    default:
      return 'from-emerald-100 to-transparent'
  }
})
</script>

<template>
  <teleport to="body">
    <transition name="fade" appear>
      <div
        v-if="open"
        class="fixed inset-0 z-[150] flex items-center justify-center px-4 py-8 sm:py-12"
        @click.self="handleClose"
      >
        <div class="absolute inset-0 bg-black/50" />
        <transition name="scale" appear>
          <div
            class="relative w-full max-w-md max-h-[85vh] overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-2xl"
          >
            <div :class="['absolute inset-0 bg-gradient-to-b opacity-60 pointer-events-none', glowClass]" />
            <div class="relative z-10 flex max-h-[85vh] flex-col space-y-4 overflow-hidden px-6 py-6 text-center">
              <component :is="icon" class="mx-auto h-12 w-12" :class="accentClass" />
              <div>
                <h3 class="text-xl font-semibold text-gray-900">{{ title }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ message }}</p>
              </div>
              <div
                v-if="props.summary?.length"
                class="max-h-56 overflow-y-auto rounded-2xl border border-gray-100 bg-white/80 p-4 text-left"
              >
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Summary</p>
                <ul class="divide-y divide-gray-100 text-sm">
                  <li
                    v-for="(item, index) in props.summary"
                    :key="`${item.label}-${index}`"
                    class="flex items-center justify-between py-1.5"
                  >
                    <span class="mr-3 text-gray-500">{{ item.label }}</span>
                    <span class="max-w-[60%] truncate font-medium text-gray-900">{{ item.value }}</span>
                  </li>
                </ul>
              </div>
              <div class="pt-2">
                <slot name="actions" :close="handleClose">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-gray-800"
                    @click="handleClose"
                  >
                    Close
                  </button>
                </slot>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease;
}
</style>
