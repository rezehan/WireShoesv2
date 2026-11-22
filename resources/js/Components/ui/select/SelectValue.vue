<script setup>
import { computed } from 'vue'
import { SelectValue, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  placeholder: { type: String, default: undefined },
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
  <SelectValue
    data-slot="select-value"
    v-bind="forwarded"
    :class="
      cn(
        `flex flex-1 items-center gap-2 truncate text-start text-gray-800 group-data-placeholder/select-trigger:text-gray-400 dark:text-white/90 dark:group-data-placeholder/select-trigger:text-gray-500`,
        props.class,
      )
    "
  >
    <slot />
  </SelectValue>
</template>