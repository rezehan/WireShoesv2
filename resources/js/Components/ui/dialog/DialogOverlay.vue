<script setup>
import { computed } from 'vue'
import { DialogOverlay, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  forceMount: { type: Boolean, default: undefined },
  as: { type: [String, Object], default: undefined },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: undefined },
})

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardProps(delegated)
</script>

<template>
  <DialogOverlay
    data-slot="dialog-overlay"
    v-bind="forwarded"
    :class="
      cn(
        `fixed inset-0 isolate z-100 bg-black/50 duration-100 data-closed:animate-out data-closed:fade-out-0 data-open:animate-in data-open:fade-in-0 supports-backdrop-filter:backdrop-blur-xs dark:bg-black/70`,
        props.class,
      )
    "
  >
    <slot />
  </DialogOverlay>
</template>