<script setup>
import { computed } from 'vue'
import { DialogDescription, useForwardProps } from 'reka-ui'
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
  <DialogDescription
    data-slot="dialog-description"
    v-bind="forwarded"
    :class="cn(`text-sm text-gray-500 dark:text-gray-400`, props.class)"
  >
    <slot />
  </DialogDescription>
</template>