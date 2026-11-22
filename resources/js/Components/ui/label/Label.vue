<script setup>
import { computed } from 'vue'
import { Label, useForwardProps } from 'reka-ui'
import { cn } from '@/utils/cn'

const props = defineProps({
  for: {
    type: String,
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
  <Label
    data-slot="label"
    v-bind="forwarded"
    :class="
      cn(
        'flex items-center gap-2 text-sm leading-none font-medium text-[#364153] select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 dark:text-[#99A1AF]',
        props.class,
      )
    "
  >
    <slot />
  </Label>
</template>