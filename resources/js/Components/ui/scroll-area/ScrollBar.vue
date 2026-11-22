<script setup>
import { computed } from 'vue'
import { ScrollAreaScrollbar, ScrollAreaThumb, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  orientation: {
    type: String,
    default: 'vertical',
    validator: (value) => ['vertical', 'horizontal'].includes(value),
  },
  forceMount: {
    type: Boolean,
    default: undefined,
  },
  as: {
    type: [String, Object],
    default: undefined,
  },
  asChild: {
    type: Boolean,
    default: false,
  },
  class: {
    type: [String, Array, Object],
    default: undefined,
  },
})

const delegated = computed(() => {
  const { class: _class, ...rest } = props
  return rest
})
const forwarded = useForwardProps(delegated)
</script>

<template>
  <ScrollAreaScrollbar
    data-slot="scroll-area-scrollbar"
    :data-orientation="orientation"
    v-bind="forwarded"
    :class="
      cn(
        `flex touch-none rounded-full bg-gray-100 transition-colors select-none data-[orientation=horizontal]:h-1.5 data-[orientation=horizontal]:w-full data-[orientation=horizontal]:flex-col data-[orientation=vertical]:h-full data-[orientation=vertical]:w-1.5 dark:bg-gray-800`,
        props.class,
      )
    "
  >
    <ScrollAreaThumb
      data-slot="scroll-area-thumb"
      :class="
        cn(
          'relative rounded-full bg-gray-300 transition-colors hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500',
          orientation === 'horizontal' ? 'h-full!' : 'w-full!',
        )
      "
    />
  </ScrollAreaScrollbar>
</template>