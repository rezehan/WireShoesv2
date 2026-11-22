<script setup>
import { computed } from 'vue'
import { ComboboxSeparator, useForwardProps } from 'reka-ui'
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
  <ComboboxSeparator
    data-slot="combobox-separator"
    v-bind="forwarded"
    :class="cn(`-mx-3 my-1.5 h-px bg-gray-100 dark:bg-gray-700`, props.class)"
  >
  </ComboboxSeparator>
</template>