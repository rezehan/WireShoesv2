<script setup>
import { computed } from 'vue'
import { DialogTitle, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
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
  <DialogTitle
    data-slot="dialog-title"
    v-bind="forwarded"
    :class="cn(`text-lg font-semibold text-gray-800 dark:text-white/90`, props.class)"
  >
    <slot />
  </DialogTitle>
</template>